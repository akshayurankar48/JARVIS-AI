<?php
/**
 * WooCommerce Manage Shipping Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Shipping implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_shipping';
	}

	public function get_description(): string {
		return 'Manage WooCommerce shipping zones and methods. List zones, list methods in a zone, add zones, and add shipping methods.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'   => [ 'type' => 'string', 'enum' => [ 'list_zones', 'list_methods', 'add_zone', 'add_method', 'update_method' ] ],
				'zone_id'     => [ 'type' => 'integer' ],
				'zone_name'   => [ 'type' => 'string' ],
				'method_type' => [ 'type' => 'string', 'enum' => [ 'flat_rate', 'free_shipping', 'local_pickup' ] ],
				'instance_id' => [ 'type' => 'integer' ],
				'settings'    => [ 'type' => 'object', 'description' => 'Method settings (e.g., {"cost": "10.00", "title": "Standard"}).' ],
			],
			'required'   => [ 'operation' ],
		];
	}

	public function get_capabilities_required(): string {
		return 'manage_woocommerce';
	}

	public function is_reversible(): bool {
		return true;
	}

	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'list_zones':
				$zones  = \WC_Shipping_Zones::get_zones();
				$list   = [];
				foreach ( $zones as $zone_data ) {
					$zone   = new \WC_Shipping_Zone( $zone_data['id'] );
					$list[] = [
						'id'      => $zone->get_id(),
						'name'    => $zone->get_zone_name(),
						'methods' => count( $zone->get_shipping_methods() ),
					];
				}
				return [ 'success' => true, 'data' => [ 'zones' => $list ], 'message' => sprintf( __( '%d shipping zone(s).', 'wp-agent' ), count( $list ) ) ];

			case 'list_methods':
				$zone_id = absint( $params['zone_id'] ?? 0 );
				$zone    = new \WC_Shipping_Zone( $zone_id );
				$methods = [];
				foreach ( $zone->get_shipping_methods() as $method ) {
					$methods[] = [
						'instance_id' => $method->get_instance_id(),
						'method_id'   => $method->id,
						'title'       => $method->get_title(),
						'enabled'     => $method->is_enabled(),
					];
				}
				return [ 'success' => true, 'data' => [ 'zone_id' => $zone_id, 'methods' => $methods ], 'message' => sprintf( __( '%d method(s) in zone.', 'wp-agent' ), count( $methods ) ) ];

			case 'add_zone':
				$name = sanitize_text_field( $params['zone_name'] ?? '' );
				if ( empty( $name ) ) return [ 'success' => false, 'data' => null, 'message' => __( 'zone_name required.', 'wp-agent' ) ];
				$zone = new \WC_Shipping_Zone();
				$zone->set_zone_name( $name );
				$zone->save();
				return [ 'success' => true, 'data' => [ 'zone_id' => $zone->get_id() ], 'message' => sprintf( __( 'Zone "%s" created.', 'wp-agent' ), $name ) ];

			case 'add_method':
				$zone_id = absint( $params['zone_id'] ?? 0 );
				$type    = sanitize_text_field( $params['method_type'] ?? '' );
				if ( empty( $type ) ) return [ 'success' => false, 'data' => null, 'message' => __( 'method_type required.', 'wp-agent' ) ];
				$zone        = new \WC_Shipping_Zone( $zone_id );
				$instance_id = $zone->add_shipping_method( $type );
				return [ 'success' => true, 'data' => [ 'instance_id' => $instance_id ], 'message' => sprintf( __( 'Method "%s" added to zone #%d.', 'wp-agent' ), $type, $zone_id ) ];

			case 'update_method':
				$instance_id = absint( $params['instance_id'] ?? 0 );
				$settings    = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : [];
				if ( ! $instance_id || empty( $settings ) ) return [ 'success' => false, 'data' => null, 'message' => __( 'instance_id and settings required.', 'wp-agent' ) ];
				foreach ( $settings as $key => $value ) {
					update_option( 'woocommerce_' . sanitize_key( $key ) . '_' . $instance_id . '_settings', $value );
				}
				return [ 'success' => true, 'data' => [ 'instance_id' => $instance_id ], 'message' => sprintf( __( 'Shipping method #%d updated.', 'wp-agent' ), $instance_id ) ];

			default:
				return [ 'success' => false, 'data' => null, 'message' => __( 'Invalid operation.', 'wp-agent' ) ];
		}
	}
}
