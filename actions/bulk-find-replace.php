<?php
/**
 * Bulk Find & Replace Action.
 *
 * Searches and replaces text across post content, titles, excerpts, and meta.
 * Includes a preview (dry run) mode and a 500-post safety limit.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Bulk_Find_Replace
 *
 * @since 1.1.0
 */
class Bulk_Find_Replace implements Action_Interface {

	const MAX_POSTS = 500;

	public function get_name(): string {
		return 'bulk_find_replace';
	}

	public function get_description(): string {
		return 'Find and replace text across multiple posts. Supports post_content, post_title, '
			. 'post_excerpt, and post meta. Use "preview" for a dry run first, then "execute" to apply. '
			. 'Maximum 500 posts per run.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation' => array(
					'type'        => 'string',
					'enum'        => array( 'preview', 'execute' ),
					'description' => '"preview" for dry run, "execute" to apply changes.',
				),
				'find'      => array(
					'type'        => 'string',
					'description' => 'Text to find. Required.',
				),
				'replace'   => array(
					'type'        => 'string',
					'description' => 'Replacement text. Required.',
				),
				'scope'     => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'post_content', 'post_title', 'post_excerpt', 'meta' ),
					),
					'description' => 'Where to search. Defaults to ["post_content"].',
				),
				'post_type' => array(
					'type'        => 'string',
					'description' => 'Limit to a specific post type. Defaults to all public types.',
				),
			),
			'required'   => array( 'operation', 'find', 'replace' ),
		);
	}

	public function get_capabilities_required(): string {
		return 'manage_options';
	}

	public function is_reversible(): bool {
		return true;
	}

	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';
		$find      = $params['find'] ?? '';
		$replace   = $params['replace'] ?? '';
		$scope     = isset( $params['scope'] ) && is_array( $params['scope'] ) ? $params['scope'] : array( 'post_content' );
		$post_type = isset( $params['post_type'] ) ? sanitize_text_field( $params['post_type'] ) : '';

		if ( empty( $find ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'find text is required.', 'wp-agent' ),
			);
		}

		if ( $find === $replace ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'find and replace texts are identical.', 'wp-agent' ),
			);
		}

		$dry_run = 'preview' === $operation;

		return $this->find_and_replace( $find, $replace, $scope, $post_type, $dry_run );
	}

	private function find_and_replace( string $find, string $replace, array $scope, string $post_type, bool $dry_run ) {
		global $wpdb;

		$post_types = array();
		if ( ! empty( $post_type ) ) {
			$post_types = array( $post_type );
		} else {
			$post_types = get_post_types( array( 'public' => true ) );
		}

		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$affected = array();
		$total    = 0;

		// Search in post fields.
		$post_fields = array_intersect( $scope, array( 'post_content', 'post_title', 'post_excerpt' ) );

		if ( ! empty( $post_fields ) ) {
			$conditions = array();
			foreach ( $post_fields as $field ) {
				$safe_field   = sanitize_key( $field );
				$conditions[] = $wpdb->prepare( "{$safe_field} LIKE %s", '%' . $wpdb->esc_like( $find ) . '%' );
			}

			$where_conditions = implode( ' OR ', $conditions );

			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_type FROM {$wpdb->posts}
					WHERE post_type IN ({$type_placeholders})
					AND post_status IN ('publish', 'draft', 'private', 'pending')
					AND ({$where_conditions})
					LIMIT %d",
					...array_merge( $post_types, array( self::MAX_POSTS ) )
				),
				ARRAY_A
			);

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$affected[] = array(
						'id'    => (int) $post['ID'],
						'title' => $post['post_title'],
						'type'  => $post['post_type'],
					);

					if ( ! $dry_run ) {
						foreach ( $post_fields as $field ) {
							$safe_field = sanitize_key( $field );
							$wpdb->query(
								$wpdb->prepare(
									"UPDATE {$wpdb->posts} SET {$safe_field} = REPLACE({$safe_field}, %s, %s) WHERE ID = %d",
									$find,
									$replace,
									$post['ID']
								)
							);
						}
						clean_post_cache( $post['ID'] );
					}
				}
				$total += count( $posts );
			}
		}

		// Search in meta.
		if ( in_array( 'meta', $scope, true ) ) {
			$meta_posts = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Dynamic IN() placeholders built from $post_types.
				$wpdb->prepare(
					"SELECT DISTINCT p.ID, p.post_title, p.post_type
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE p.post_type IN ({$type_placeholders})
					AND pm.meta_value LIKE %s
					LIMIT %d",
					...array_merge( $post_types, array( '%' . $wpdb->esc_like( $find ) . '%', self::MAX_POSTS ) )
				),
				ARRAY_A
			);

			if ( ! empty( $meta_posts ) ) {
				foreach ( $meta_posts as $post ) {
					$already = false;
					foreach ( $affected as $a ) {
						if ( $a['id'] === (int) $post['ID'] ) {
							$already = true;
							break;
						}
					}
					if ( ! $already ) {
						$affected[] = array(
							'id'    => (int) $post['ID'],
							'title' => $post['post_title'],
							'type'  => $post['post_type'],
						);
					}

					if ( ! $dry_run ) {
						$wpdb->query(
							$wpdb->prepare(
								"UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s) WHERE post_id = %d AND meta_value LIKE %s",
								$find,
								$replace,
								$post['ID'],
								'%' . $wpdb->esc_like( $find ) . '%'
							)
						);
					}
				}
				$total += count( $meta_posts );
			}
		}

		// phpcs:enable

		$mode = $dry_run ? 'Preview' : 'Executed';

		return array(
			'success' => true,
			'data'    => array(
				'mode'           => $dry_run ? 'preview' : 'executed',
				'find'           => $find,
				'replace'        => $replace,
				'scope'          => $scope,
				'affected_count' => count( $affected ),
				'affected_posts' => array_slice( $affected, 0, 50 ),
			),
			'message' => sprintf(
				/* translators: 1: mode, 2: count, 3: find, 4: replace */
				__( '%1$s: %2$d post(s) affected. Find: "%3$s" -> Replace: "%4$s".', 'wp-agent' ),
				$mode,
				count( $affected ),
				$find,
				$replace
			),
		);
	}
}
