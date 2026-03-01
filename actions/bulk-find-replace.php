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
		return [
			'type'       => 'object',
			'properties' => [
				'operation' => [
					'type'        => 'string',
					'enum'        => [ 'preview', 'execute' ],
					'description' => '"preview" for dry run, "execute" to apply changes.',
				],
				'find'      => [
					'type'        => 'string',
					'description' => 'Text to find. Required.',
				],
				'replace'   => [
					'type'        => 'string',
					'description' => 'Replacement text. Required.',
				],
				'scope'     => [
					'type'        => 'array',
					'items'       => [
						'type' => 'string',
						'enum' => [ 'post_content', 'post_title', 'post_excerpt', 'meta' ],
					],
					'description' => 'Where to search. Defaults to ["post_content"].',
				],
				'post_type' => [
					'type'        => 'string',
					'description' => 'Limit to a specific post type. Defaults to all public types.',
				],
			],
			'required'   => [ 'operation', 'find', 'replace' ],
		];
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
		$scope     = isset( $params['scope'] ) && is_array( $params['scope'] ) ? $params['scope'] : [ 'post_content' ];
		$post_type = isset( $params['post_type'] ) ? sanitize_text_field( $params['post_type'] ) : '';

		if ( empty( $find ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'find text is required.', 'wp-agent' ),
			];
		}

		if ( $find === $replace ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'find and replace texts are identical.', 'wp-agent' ),
			];
		}

		$dry_run = 'preview' === $operation;

		return $this->find_and_replace( $find, $replace, $scope, $post_type, $dry_run );
	}

	private function find_and_replace( string $find, string $replace, array $scope, string $post_type, bool $dry_run ) {
		global $wpdb;

		$post_types = [];
		if ( ! empty( $post_type ) ) {
			$post_types = [ $post_type ];
		} else {
			$post_types = get_post_types( [ 'public' => true ] );
		}

		$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		$affected  = [];
		$total     = 0;

		// Search in post fields.
		$post_fields = array_intersect( $scope, [ 'post_content', 'post_title', 'post_excerpt' ] );

		if ( ! empty( $post_fields ) ) {
			$conditions = [];
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
					...array_merge( $post_types, [ self::MAX_POSTS ] )
				),
				ARRAY_A
			);

			if ( ! empty( $posts ) ) {
				foreach ( $posts as $post ) {
					$affected[] = [
						'id'    => (int) $post['ID'],
						'title' => $post['post_title'],
						'type'  => $post['post_type'],
					];

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
				$wpdb->prepare(
					"SELECT DISTINCT p.ID, p.post_title, p.post_type
					FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
					WHERE p.post_type IN ({$type_placeholders})
					AND pm.meta_value LIKE %s
					LIMIT %d",
					...array_merge( $post_types, [ '%' . $wpdb->esc_like( $find ) . '%', self::MAX_POSTS ] )
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
						$affected[] = [
							'id'    => (int) $post['ID'],
							'title' => $post['post_title'],
							'type'  => $post['post_type'],
						];
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

		return [
			'success' => true,
			'data'    => [
				'mode'          => $dry_run ? 'preview' : 'executed',
				'find'          => $find,
				'replace'       => $replace,
				'scope'         => $scope,
				'affected_count' => count( $affected ),
				'affected_posts' => array_slice( $affected, 0, 50 ),
			],
			'message' => sprintf(
				/* translators: 1: mode, 2: count, 3: find, 4: replace */
				__( '%1$s: %2$d post(s) affected. Find: "%3$s" -> Replace: "%4$s".', 'wp-agent' ),
				$mode,
				count( $affected ),
				$find,
				$replace
			),
		];
	}
}
