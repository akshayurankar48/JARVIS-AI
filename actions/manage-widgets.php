<?php
/**
 * Manage Widgets Action.
 *
 * Lists widget areas, lists widgets, adds, removes, and reorders widgets
 * using the WordPress sidebars/widgets API.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Widgets
 *
 * @since 1.1.0
 */
class Manage_Widgets implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_widgets';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage WordPress widgets. List available widget areas (sidebars), list widgets in a sidebar, '
			. 'add a widget to a sidebar, remove a widget, or reorder widgets within a sidebar.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'  => [
					'type'        => 'string',
					'enum'        => [ 'list_areas', 'list_widgets', 'add', 'remove', 'reorder' ],
					'description' => 'Operation to perform.',
				],
				'sidebar_id' => [
					'type'        => 'string',
					'description' => 'Sidebar/widget area ID. Required for list_widgets, add, remove, reorder.',
				],
				'widget_type' => [
					'type'        => 'string',
					'description' => 'Widget type ID (e.g., "text", "search", "recent-posts"). Required for add.',
				],
				'widget_settings' => [
					'type'        => 'object',
					'description' => 'Widget settings (e.g., {"title": "My Widget", "text": "Hello"}). For add operation.',
				],
				'widget_id'  => [
					'type'        => 'string',
					'description' => 'Full widget ID (e.g., "text-2"). Required for remove.',
				],
				'order'      => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => 'Array of widget IDs in desired order. Required for reorder.',
				],
			],
			'required'   => [ 'operation' ],
		];
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_theme_options';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return true;
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'list_areas':
				return $this->list_areas();
			case 'list_widgets':
				return $this->list_widgets( $params );
			case 'add':
				return $this->add_widget( $params );
			case 'remove':
				return $this->remove_widget( $params );
			case 'reorder':
				return $this->reorder_widgets( $params );
			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list_areas", "list_widgets", "add", "remove", or "reorder".', 'wp-agent' ),
				];
		}
	}

	/**
	 * List all registered widget areas.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function list_areas() {
		global $wp_registered_sidebars;

		$areas = [];
		foreach ( $wp_registered_sidebars as $id => $sidebar ) {
			$widgets     = wp_get_sidebars_widgets();
			$widget_ids  = isset( $widgets[ $id ] ) ? $widgets[ $id ] : [];

			$areas[] = [
				'id'           => $id,
				'name'         => $sidebar['name'],
				'description'  => $sidebar['description'] ?? '',
				'widget_count' => count( $widget_ids ),
			];
		}

		return [
			'success' => true,
			'data'    => [ 'areas' => $areas ],
			'message' => sprintf(
				/* translators: %d: area count */
				__( '%d widget area(s) registered.', 'wp-agent' ),
				count( $areas )
			),
		];
	}

	/**
	 * List widgets in a sidebar.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function list_widgets( array $params ) {
		$sidebar_id = sanitize_text_field( $params['sidebar_id'] ?? '' );

		if ( empty( $sidebar_id ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'sidebar_id is required.', 'wp-agent' ),
			];
		}

		$sidebars   = wp_get_sidebars_widgets();
		$widget_ids = isset( $sidebars[ $sidebar_id ] ) ? $sidebars[ $sidebar_id ] : null;

		if ( null === $widget_ids ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: sidebar ID */
					__( 'Widget area "%s" not found.', 'wp-agent' ),
					$sidebar_id
				),
			];
		}

		global $wp_registered_widgets;

		$widgets = [];
		foreach ( $widget_ids as $widget_id ) {
			$widget_info = [
				'id'   => $widget_id,
				'name' => isset( $wp_registered_widgets[ $widget_id ]['name'] )
					? $wp_registered_widgets[ $widget_id ]['name']
					: $widget_id,
			];
			$widgets[] = $widget_info;
		}

		return [
			'success' => true,
			'data'    => [
				'sidebar_id' => $sidebar_id,
				'widgets'    => $widgets,
			],
			'message' => sprintf(
				/* translators: 1: count, 2: sidebar ID */
				__( '%1$d widget(s) in "%2$s".', 'wp-agent' ),
				count( $widgets ),
				$sidebar_id
			),
		];
	}

	/**
	 * Add a widget to a sidebar.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function add_widget( array $params ) {
		$sidebar_id      = sanitize_text_field( $params['sidebar_id'] ?? '' );
		$widget_type     = sanitize_text_field( $params['widget_type'] ?? '' );
		$widget_settings = isset( $params['widget_settings'] ) && is_array( $params['widget_settings'] )
			? $params['widget_settings']
			: [];

		if ( empty( $sidebar_id ) || empty( $widget_type ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'sidebar_id and widget_type are required.', 'wp-agent' ),
			];
		}

		// Get current widget instances for this type.
		$option_name = 'widget_' . $widget_type;
		$instances   = get_option( $option_name, [] );

		// Find next available instance number.
		$next_number = empty( $instances ) ? 2 : max( array_keys( $instances ) ) + 1;

		// Sanitize settings.
		$clean_settings = [];
		foreach ( $widget_settings as $key => $value ) {
			$clean_settings[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		$instances[ $next_number ] = $clean_settings;
		update_option( $option_name, $instances );

		// Add to sidebar.
		$widget_id = $widget_type . '-' . $next_number;
		$sidebars  = wp_get_sidebars_widgets();

		if ( ! isset( $sidebars[ $sidebar_id ] ) ) {
			$sidebars[ $sidebar_id ] = [];
		}

		$sidebars[ $sidebar_id ][] = $widget_id;
		wp_set_sidebars_widgets( $sidebars );

		return [
			'success' => true,
			'data'    => [
				'widget_id'   => $widget_id,
				'sidebar_id'  => $sidebar_id,
				'widget_type' => $widget_type,
			],
			'message' => sprintf(
				/* translators: 1: widget ID, 2: sidebar ID */
				__( 'Widget "%1$s" added to "%2$s".', 'wp-agent' ),
				$widget_id,
				$sidebar_id
			),
		];
	}

	/**
	 * Remove a widget from its sidebar.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function remove_widget( array $params ) {
		$widget_id  = sanitize_text_field( $params['widget_id'] ?? '' );
		$sidebar_id = sanitize_text_field( $params['sidebar_id'] ?? '' );

		if ( empty( $widget_id ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'widget_id is required.', 'wp-agent' ),
			];
		}

		$sidebars = wp_get_sidebars_widgets();
		$found    = false;

		if ( ! empty( $sidebar_id ) && isset( $sidebars[ $sidebar_id ] ) ) {
			$key = array_search( $widget_id, $sidebars[ $sidebar_id ], true );
			if ( false !== $key ) {
				unset( $sidebars[ $sidebar_id ][ $key ] );
				$sidebars[ $sidebar_id ] = array_values( $sidebars[ $sidebar_id ] );
				$found = true;
			}
		} else {
			foreach ( $sidebars as $sid => &$widgets ) {
				$key = array_search( $widget_id, $widgets, true );
				if ( false !== $key ) {
					unset( $widgets[ $key ] );
					$widgets    = array_values( $widgets );
					$sidebar_id = $sid;
					$found      = true;
					break;
				}
			}
			unset( $widgets );
		}

		if ( ! $found ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: widget ID */
					__( 'Widget "%s" not found in any sidebar.', 'wp-agent' ),
					$widget_id
				),
			];
		}

		wp_set_sidebars_widgets( $sidebars );

		return [
			'success' => true,
			'data'    => [
				'widget_id'  => $widget_id,
				'sidebar_id' => $sidebar_id,
			],
			'message' => sprintf(
				/* translators: 1: widget ID, 2: sidebar ID */
				__( 'Widget "%1$s" removed from "%2$s".', 'wp-agent' ),
				$widget_id,
				$sidebar_id
			),
		];
	}

	/**
	 * Reorder widgets in a sidebar.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function reorder_widgets( array $params ) {
		$sidebar_id = sanitize_text_field( $params['sidebar_id'] ?? '' );
		$order      = isset( $params['order'] ) && is_array( $params['order'] ) ? $params['order'] : [];

		if ( empty( $sidebar_id ) || empty( $order ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'sidebar_id and order are required.', 'wp-agent' ),
			];
		}

		$sidebars = wp_get_sidebars_widgets();

		if ( ! isset( $sidebars[ $sidebar_id ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: sidebar ID */
					__( 'Widget area "%s" not found.', 'wp-agent' ),
					$sidebar_id
				),
			];
		}

		$sanitized_order = array_map( 'sanitize_text_field', $order );
		$sidebars[ $sidebar_id ] = $sanitized_order;
		wp_set_sidebars_widgets( $sidebars );

		return [
			'success' => true,
			'data'    => [
				'sidebar_id' => $sidebar_id,
				'order'      => $sanitized_order,
			],
			'message' => sprintf(
				/* translators: %s: sidebar ID */
				__( 'Widgets reordered in "%s".', 'wp-agent' ),
				$sidebar_id
			),
		];
	}
}
