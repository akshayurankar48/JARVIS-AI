<?php
/**
 * WooCommerce Manage Settings Action.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Woo_Manage_Settings
 *
 * Handles retrieval and updating of WooCommerce store settings by section.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Woo_Manage_Settings implements Action_Interface {

	/**
	 * Allowed WooCommerce settings sections.
	 *
	 * @var array
	 */
	const SECTIONS = array( 'general', 'products', 'tax', 'shipping', 'payments', 'accounts', 'emails' );

	/**
	 * Get the action identifier.
	 *
	 * @since  1.1.0
	 * @return string Action identifier.
	 */
	public function get_name(): string {
		return 'woo_manage_settings';
	}

	/**
	 * Get the human-readable description.
	 *
	 * @since  1.1.0
	 * @return string Human-readable description.
	 */
	public function get_description(): string {
		return 'Get or update WooCommerce settings. Sections: general, products, tax, shipping, payments, accounts, emails.';
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
				'operation' => array(
					'type' => 'string',
					'enum' => array( 'get', 'update' ),
				),
				'section'   => array(
					'type' => 'string',
					'enum' => self::SECTIONS,
				),
				'settings'  => array(
					'type'        => 'object',
					'description' => 'Key-value pairs of settings to update (option_name: value).',
				),
			),
			'required'   => array( 'operation', 'section' ),
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
	 * Execute the settings management action.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters.
	 * @return array Result with success status, data, and message.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';
		$section   = sanitize_text_field( $params['section'] ?? '' );

		if ( ! in_array( $section, self::SECTIONS, true ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid section.', 'jarvis-ai' ),
			);
		}

		if ( 'get' === $operation ) {
			return $this->get_settings( $section );
		}

		if ( 'update' === $operation ) {
			$settings = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : array();
			if ( empty( $settings ) ) {
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'settings object required.', 'jarvis-ai' ),
				);
			}
			return $this->update_settings( $section, $settings );
		}

		return array(
			'success' => false,
			'data'    => null,
			'message' => __( 'Invalid operation.', 'jarvis-ai' ),
		);
	}

	/**
	 * Retrieve all WooCommerce settings for a given section.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $section The settings section to retrieve.
	 * @return array Result with section settings key-value pairs.
	 */
	private function get_settings( string $section ) {
		$option_map = $this->get_section_options( $section );
		$values     = array();

		foreach ( $option_map as $key => $label ) {
			$values[ $key ] = get_option( $key, '' );
		}

		return array(
			'success' => true,
			'data'    => array(
				'section'  => $section,
				'settings' => $values,
			),
			'message' => sprintf( __( 'WooCommerce %s settings.', 'jarvis-ai' ), $section ),
		);
	}

	/**
	 * Update WooCommerce settings for a given section.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $section  The settings section to update.
	 * @param  array  $settings Key-value pairs of settings to update.
	 * @return array Result with list of updated setting keys.
	 */
	private function update_settings( string $section, array $settings ) {
		$option_map = $this->get_section_options( $section );
		$updated    = array();

		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! array_key_exists( $key, $option_map ) ) {
				continue;
			}
			update_option( $key, sanitize_text_field( $value ) );
			$updated[] = $key;
		}

		return array(
			'success' => true,
			'data'    => array(
				'section' => $section,
				'updated' => $updated,
			),
			'message' => sprintf( __( 'Updated %1$d WooCommerce %2$s setting(s).', 'jarvis-ai' ), count( $updated ), $section ),
		);
	}

	/**
	 * Get the allowed option keys and labels for a settings section.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $section The settings section name.
	 * @return array Associative array of option_key => label pairs.
	 */
	private function get_section_options( string $section ) {
		switch ( $section ) {
			case 'general':
				return array(
					'woocommerce_store_address'      => 'Store address',
					'woocommerce_store_city'         => 'Store city',
					'woocommerce_default_country'    => 'Default country',
					'woocommerce_store_postcode'     => 'Store postcode',
					'woocommerce_currency'           => 'Currency',
					'woocommerce_price_thousand_sep' => 'Thousand separator',
					'woocommerce_price_decimal_sep'  => 'Decimal separator',
					'woocommerce_price_num_decimals' => 'Number of decimals',
				);
			case 'products':
				return array(
					'woocommerce_weight_unit'           => 'Weight unit',
					'woocommerce_dimension_unit'        => 'Dimension unit',
					'woocommerce_enable_reviews'        => 'Enable reviews',
					'woocommerce_manage_stock'          => 'Manage stock',
					'woocommerce_notify_low_stock'      => 'Low stock notification',
					'woocommerce_stock_email_recipient' => 'Stock email recipient',
				);
			case 'tax':
				return array(
					'woocommerce_calc_taxes'            => 'Enable taxes',
					'woocommerce_prices_include_tax'    => 'Prices include tax',
					'woocommerce_tax_based_on'          => 'Tax based on',
					'woocommerce_tax_round_at_subtotal' => 'Round at subtotal',
					'woocommerce_tax_display_shop'      => 'Display in shop',
					'woocommerce_tax_display_cart'      => 'Display in cart',
				);
			default:
				return array();
		}
	}
}
