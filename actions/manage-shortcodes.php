<?php
/**
 * Manage Shortcodes Action.
 *
 * Lists all registered shortcodes, previews their output, and finds
 * which posts contain specific shortcodes. Read-only.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Shortcodes
 *
 * @since 1.1.0
 */
class Manage_Shortcodes implements Action_Interface {

	public function get_name(): string {
		return 'manage_shortcodes';
	}

	public function get_description(): string {
		return 'List all registered shortcodes, preview a shortcode\'s rendered output, '
			. 'or find which posts use a specific shortcode. Read-only — does not modify content.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'  => [
					'type'        => 'string',
					'enum'        => [ 'list', 'preview', 'find_usage' ],
					'description' => '"list" all shortcodes, "preview" renders one, "find_usage" searches posts.',
				],
				'shortcode'  => [
					'type'        => 'string',
					'description' => 'Shortcode tag name (without brackets). Required for preview and find_usage.',
				],
				'attributes' => [
					'type'        => 'object',
					'description' => 'Attributes to pass when previewing (e.g., {"id": "123"}).',
				],
				'content'    => [
					'type'        => 'string',
					'description' => 'Content between shortcode tags for preview.',
				],
			],
			'required'   => [ 'operation' ],
		];
	}

	public function get_capabilities_required(): string {
		return 'edit_posts';
	}

	public function is_reversible(): bool {
		return false;
	}

	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'list':
				return $this->list_shortcodes();
			case 'preview':
				return $this->preview_shortcode( $params );
			case 'find_usage':
				return $this->find_usage( $params );
			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				];
		}
	}

	private function list_shortcodes() {
		global $shortcode_tags;

		$shortcodes = [];
		foreach ( $shortcode_tags as $tag => $callback ) {
			$source = 'unknown';
			if ( is_string( $callback ) ) {
				$source = $callback;
			} elseif ( is_array( $callback ) && isset( $callback[0] ) ) {
				$source = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
			}

			$shortcodes[] = [
				'tag'    => $tag,
				'source' => $source,
			];
		}

		usort( $shortcodes, function ( $a, $b ) {
			return strcmp( $a['tag'], $b['tag'] );
		} );

		return [
			'success' => true,
			'data'    => [
				'count'      => count( $shortcodes ),
				'shortcodes' => $shortcodes,
			],
			'message' => sprintf(
				/* translators: %d: count */
				__( '%d shortcode(s) registered.', 'wp-agent' ),
				count( $shortcodes )
			),
		];
	}

	private function preview_shortcode( array $params ) {
		$tag        = sanitize_text_field( $params['shortcode'] ?? '' );
		$attributes = isset( $params['attributes'] ) && is_array( $params['attributes'] ) ? $params['attributes'] : [];
		$content    = isset( $params['content'] ) ? sanitize_text_field( $params['content'] ) : '';

		if ( empty( $tag ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'shortcode tag is required.', 'wp-agent' ),
			];
		}

		if ( ! shortcode_exists( $tag ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: shortcode tag */
					__( 'Shortcode [%s] is not registered.', 'wp-agent' ),
					$tag
				),
			];
		}

		// Build shortcode string.
		$shortcode = '[' . $tag;
		foreach ( $attributes as $key => $value ) {
			$shortcode .= ' ' . sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
		}
		if ( ! empty( $content ) ) {
			$shortcode .= ']' . $content . '[/' . $tag . ']';
		} else {
			$shortcode .= ']';
		}

		$output = do_shortcode( $shortcode );

		return [
			'success' => true,
			'data'    => [
				'shortcode'   => $shortcode,
				'html_output' => wp_kses_post( $output ),
				'text_output' => wp_strip_all_tags( $output ),
			],
			'message' => sprintf(
				/* translators: %s: shortcode */
				__( 'Preview of %s rendered.', 'wp-agent' ),
				$shortcode
			),
		];
	}

	private function find_usage( array $params ) {
		global $wpdb;

		$tag = sanitize_text_field( $params['shortcode'] ?? '' );

		if ( empty( $tag ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'shortcode tag is required.', 'wp-agent' ),
			];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type, post_status
				FROM {$wpdb->posts}
				WHERE post_content LIKE %s
				AND post_status IN ('publish', 'draft', 'private', 'pending')
				ORDER BY post_date DESC
				LIMIT 100",
				'%' . $wpdb->esc_like( '[' . $tag ) . '%'
			),
			ARRAY_A
		);

		$results = [];
		foreach ( $posts as $post ) {
			$results[] = [
				'id'     => (int) $post['ID'],
				'title'  => $post['post_title'],
				'type'   => $post['post_type'],
				'status' => $post['post_status'],
			];
		}

		return [
			'success' => true,
			'data'    => [
				'shortcode' => $tag,
				'count'     => count( $results ),
				'posts'     => $results,
			],
			'message' => sprintf(
				/* translators: 1: count, 2: shortcode */
				__( 'Found [%2$s] in %1$d post(s).', 'wp-agent' ),
				count( $results ),
				$tag
			),
		];
	}
}
