<?php
/**
 * WooCommerce Manage Shipping Action.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Woo_Manage_Shipping
 *
 * Handles WooCommerce shipping zone and method management operations.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Woo_Manage_Shipping implements Action_Interface {

	/**
	 * Get the action identifier.
	 *
	 * @since  1.1.0
	 * @return string Action identifier.
	 */
	public function get_name(): string {
		return 'woo_manage_shipping';
	}

	/**
	 * Get the human-readable description.
	 *
	 * @since  1.1.0
	 * @return string Human-readable description.
	 */
	public function get_description(): string {
		return 'Manage WooCommerce shipping zones and methods. List zones, list methods in a zone, add zones, and add shipping methods.';
	}

	/**
	 * Get the JSON Schema definition for action parameters.
	 *
	 * @since  1.1.0
	 * @return array JSON Schema definition for action parameters.
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation'   => array(
					'type' => 'string',
					'enum' => array( 'list_zones', 'list_methods', 'add_zone', 'add_method', 'update_method' ),
				),
				'zone_id'     => array( 'type' => 'integer' ),
				'zone_name'   => array( 'type' => 'string' ),
				'method_type' => array(
					'type' => 'string',
					'enum' => array( 'flat_rate', 'free_shipping', 'local_pickup' ),
				),
				'instance_id' => array( 'type' => 'integer' ),
				'settings'    => array(
					'type'        => 'object',
					'description' => 'Method settings (e.g., {"cost": "10.00", "title": "Standard"}).',
				),
			),
			'required'   => array( 'operation' ),
		);
	}

	/**
	 * Get the required WordPress capability.
	 *
	 * @since  1.1.0
	 * @return string Required capability.
	 */
	public function get_capabilities_required(): string {
		return 'manage_woocommerce';
	}

	/**
	 * Check whether this action is reversible.
	 *
	 * @since  1.1.0
	 * @return bool True if reversible.
	 */
	public function is_reversible(): bool {
		return true;
	}

	/**
	 * Execute the shipping management action.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters.
	 * @return array Result with success status, data, and message.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'list_zones':
				$zones = \WC_Shipping_Zones::get_zones();
				$list  = array();
				foreach ( $zones as $zone_data ) {
					$zone   = new \WC_Shipping_Zone( $zone_data['id'] );
					$list[] = array(
						'id'      => $zone->get_id(),
						'name'    => $zone->get_zone_name(),
						'methods' => count( $zone->get_shipping_methods() ),
					);
				}
				return array(
					'success' => true,
					'data'    => array( 'zones' => $list ),
					'message' => sprintf( __( '%d shipping zone(s).', 'jarvis-ai' ), count( $list ) ),
				);

			case 'list_methods':
				$zone_id = absint( $params['zone_id'] ?? 0 );
				$zone    = new \WC_Shipping_Zone( $zone_id );
				$methods = array();
				foreach ( $zone->get_shipping_methods() as $method ) {
					$methods[] = array(
						'instance_id' => $method->get_instance_id(),
						'method_id'   => $method->id,
						'title'       => $method->get_title(),
						'enabled'     => $method->is_enabled(),
					);
				}
				return array(
					'success' => true,
					'data'    => array(
						'zone_id' => $zone_id,
						'methods' => $methods,
					),
					'message' => sprintf( __( '%d method(s) in zone.', 'jarvis-ai' ), count( $methods ) ),
				);

			case 'add_zone':
				$name = sanitize_text_field( $params['zone_name'] ?? '' );
				if ( empty( $name ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'zone_name required.', 'jarvis-ai' ),
					);
				}
				$zone = new \WC_Shipping_Zone();
				$zone->set_zone_name( $name );
				$zone->save();
				return array(
					'success' => true,
					'data'    => array( 'zone_id' => $zone->get_id() ),
					'message' => sprintf( __( 'Zone "%s" created.', 'jarvis-ai' ), $name ),
				);

			case 'add_method':
				$zone_id = absint( $params['zone_id'] ?? 0 );
				$type    = sanitize_text_field( $params['method_type'] ?? '' );
				if ( empty( $type ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'method_type required.', 'jarvis-ai' ),
					);
				}
				$zone        = new \WC_Shipping_Zone( $zone_id );
				$instance_id = $zone->add_shipping_method( $type );
				return array(
					'success' => true,
					'data'    => array( 'instance_id' => $instance_id ),
					'message' => sprintf( __( 'Method "%1$s" added to zone #%2$d.', 'jarvis-ai' ), $type, $zone_id ),
				);

			case 'update_method':
				$instance_id = absint( $params['instance_id'] ?? 0 );
				$settings    = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : array();
				if ( ! $instance_id || empty( $settings ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'instance_id and settings required.', 'jarvis-ai' ),
					);
				}
				foreach ( $settings as $key => $value ) {
					update_option( 'woocommerce_' . sanitize_key( $key ) . '_' . $instance_id . '_settings', $value );
				}
				return array(
					'success' => true,
					'data'    => array( 'instance_id' => $instance_id ),
					'message' => sprintf( __( 'Shipping method #%d updated.', 'jarvis-ai' ), $instance_id ),
				);

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'jarvis-ai' ),
				);
		}
	}
}
