<?php
/**
 * WooCommerce Manage Settings Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Settings implements Action_Interface {

	const SECTIONS = [ 'general', 'products', 'tax', 'shipping', 'payments', 'accounts', 'emails' ];

	public function get_name(): string {
		return 'woo_manage_settings';
	}

	public function get_description(): string {
		return 'Get or update WooCommerce settings. Sections: general, products, tax, shipping, payments, accounts, emails.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation' => [ 'type' => 'string', 'enum' => [ 'get', 'update' ] ],
				'section'   => [ 'type' => 'string', 'enum' => self::SECTIONS ],
				'settings'  => [
					'type' => 'object',
					'description' => 'Key-value pairs of settings to update (option_name: value).',
				],
			],
			'required'   => [ 'operation', 'section' ],
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
		$section   = sanitize_text_field( $params['section'] ?? '' );

		if ( ! in_array( $section, self::SECTIONS, true ) ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'Invalid section.', 'wp-agent' ) ];
		}

		if ( 'get' === $operation ) {
			return $this->get_settings( $section );
		}

		if ( 'update' === $operation ) {
			$settings = isset( $params['settings'] ) && is_array( $params['settings'] ) ? $params['settings'] : [];
			if ( empty( $settings ) ) {
				return [ 'success' => false, 'data' => null, 'message' => __( 'settings object required.', 'wp-agent' ) ];
			}
			return $this->update_settings( $section, $settings );
		}

		return [ 'success' => false, 'data' => null, 'message' => __( 'Invalid operation.', 'wp-agent' ) ];
	}

	private function get_settings( string $section ) {
		$option_map = $this->get_section_options( $section );
		$values     = [];

		foreach ( $option_map as $key => $label ) {
			$values[ $key ] = get_option( $key, '' );
		}

		return [
			'success' => true,
			'data'    => [ 'section' => $section, 'settings' => $values ],
			'message' => sprintf( __( 'WooCommerce %s settings.', 'wp-agent' ), $section ),
		];
	}

	private function update_settings( string $section, array $settings ) {
		$option_map = $this->get_section_options( $section );
		$updated    = [];

		foreach ( $settings as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! array_key_exists( $key, $option_map ) ) {
				continue;
			}
			update_option( $key, sanitize_text_field( $value ) );
			$updated[] = $key;
		}

		return [
			'success' => true,
			'data'    => [ 'section' => $section, 'updated' => $updated ],
			'message' => sprintf( __( 'Updated %d WooCommerce %s setting(s).', 'wp-agent' ), count( $updated ), $section ),
		];
	}

	private function get_section_options( string $section ) {
		switch ( $section ) {
			case 'general':
				return [
					'woocommerce_store_address'     => 'Store address',
					'woocommerce_store_city'        => 'Store city',
					'woocommerce_default_country'   => 'Default country',
					'woocommerce_store_postcode'    => 'Store postcode',
					'woocommerce_currency'          => 'Currency',
					'woocommerce_price_thousand_sep' => 'Thousand separator',
					'woocommerce_price_decimal_sep' => 'Decimal separator',
					'woocommerce_price_num_decimals' => 'Number of decimals',
				];
			case 'products':
				return [
					'woocommerce_weight_unit'       => 'Weight unit',
					'woocommerce_dimension_unit'    => 'Dimension unit',
					'woocommerce_enable_reviews'    => 'Enable reviews',
					'woocommerce_manage_stock'      => 'Manage stock',
					'woocommerce_notify_low_stock'  => 'Low stock notification',
					'woocommerce_stock_email_recipient' => 'Stock email recipient',
				];
			case 'tax':
				return [
					'woocommerce_calc_taxes'        => 'Enable taxes',
					'woocommerce_prices_include_tax' => 'Prices include tax',
					'woocommerce_tax_based_on'      => 'Tax based on',
					'woocommerce_tax_round_at_subtotal' => 'Round at subtotal',
					'woocommerce_tax_display_shop'  => 'Display in shop',
					'woocommerce_tax_display_cart'  => 'Display in cart',
				];
			default:
				return [];
		}
	}
}
