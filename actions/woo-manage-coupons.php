<?php
/**
 * WooCommerce Manage Coupons Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Coupons implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_coupons';
	}

	public function get_description(): string {
		return 'Manage WooCommerce coupons. Create, list, update, or delete coupons with discount types, amounts, expiry dates, and usage limits.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation'   => array(
					'type' => 'string',
					'enum' => array( 'create', 'list', 'update', 'delete' ),
				),
				'coupon_id'   => array( 'type' => 'integer' ),
				'code'        => array(
					'type'        => 'string',
					'description' => 'Coupon code.',
				),
				'type'        => array(
					'type' => 'string',
					'enum' => array( 'percent', 'fixed_cart', 'fixed_product' ),
				),
				'amount'      => array(
					'type'        => 'string',
					'description' => 'Discount amount.',
				),
				'expiry'      => array(
					'type'        => 'string',
					'description' => 'Expiry date (YYYY-MM-DD).',
				),
				'usage_limit' => array( 'type' => 'integer' ),
				'min_amount'  => array(
					'type'        => 'string',
					'description' => 'Minimum order amount.',
				),
				'products'    => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => 'Product IDs.',
				),
			),
			'required'   => array( 'operation' ),
		);
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
			case 'create':
				return $this->create_coupon( $params );
			case 'list':
				return $this->list_coupons();
			case 'update':
				return $this->update_coupon( $params );
			case 'delete':
				return $this->delete_coupon( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				);
		}
	}

	private function create_coupon( array $params ) {
		$code = sanitize_text_field( $params['code'] ?? '' );
		if ( empty( $code ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Coupon code is required.', 'wp-agent' ),
			);
		}

		$coupon = new \WC_Coupon();
		$coupon->set_code( $code );
		if ( isset( $params['type'] ) ) {
			$coupon->set_discount_type( sanitize_text_field( $params['type'] ) );
		}
		if ( isset( $params['amount'] ) ) {
			$coupon->set_amount( sanitize_text_field( $params['amount'] ) );
		}
		if ( isset( $params['expiry'] ) ) {
			$coupon->set_date_expires( sanitize_text_field( $params['expiry'] ) );
		}
		if ( isset( $params['usage_limit'] ) ) {
			$coupon->set_usage_limit( absint( $params['usage_limit'] ) );
		}
		if ( isset( $params['min_amount'] ) ) {
			$coupon->set_minimum_amount( sanitize_text_field( $params['min_amount'] ) );
		}
		if ( isset( $params['products'] ) ) {
			$coupon->set_product_ids( array_map( 'absint', $params['products'] ) );
		}

		$coupon_id = $coupon->save();

		return array(
			'success' => true,
			'data'    => array(
				'coupon_id' => $coupon_id,
				'code'      => $code,
			),
			'message' => sprintf( __( 'Coupon "%1$s" created (ID: %2$d).', 'wp-agent' ), $code, $coupon_id ),
		);
	}

	private function list_coupons() {
		$args    = array(
			'posts_per_page' => 50,
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
		);
		$coupons = get_posts( $args );
		$list    = array();

		foreach ( $coupons as $post ) {
			$coupon = new \WC_Coupon( $post->ID );
			$list[] = array(
				'id'          => $coupon->get_id(),
				'code'        => $coupon->get_code(),
				'type'        => $coupon->get_discount_type(),
				'amount'      => $coupon->get_amount(),
				'usage_count' => $coupon->get_usage_count(),
				'usage_limit' => $coupon->get_usage_limit(),
				'expiry'      => $coupon->get_date_expires() ? $coupon->get_date_expires()->date( 'Y-m-d' ) : null,
			);
		}

		return array(
			'success' => true,
			'data'    => array( 'coupons' => $list ),
			'message' => sprintf( __( '%d coupon(s) found.', 'wp-agent' ), count( $list ) ),
		);
	}

	private function update_coupon( array $params ) {
		$coupon_id = absint( $params['coupon_id'] ?? 0 );
		if ( ! $coupon_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'coupon_id is required.', 'wp-agent' ),
			);
		}

		$coupon = new \WC_Coupon( $coupon_id );
		if ( ! $coupon->get_id() ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Coupon not found.', 'wp-agent' ),
			);
		}

		if ( isset( $params['amount'] ) ) {
			$coupon->set_amount( sanitize_text_field( $params['amount'] ) );
		}
		if ( isset( $params['type'] ) ) {
			$coupon->set_discount_type( sanitize_text_field( $params['type'] ) );
		}
		if ( isset( $params['expiry'] ) ) {
			$coupon->set_date_expires( sanitize_text_field( $params['expiry'] ) );
		}
		if ( isset( $params['usage_limit'] ) ) {
			$coupon->set_usage_limit( absint( $params['usage_limit'] ) );
		}

		$coupon->save();

		return array(
			'success' => true,
			'data'    => array( 'coupon_id' => $coupon_id ),
			'message' => sprintf( __( 'Coupon #%d updated.', 'wp-agent' ), $coupon_id ),
		);
	}

	private function delete_coupon( array $params ) {
		$coupon_id = absint( $params['coupon_id'] ?? 0 );
		if ( ! $coupon_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'coupon_id is required.', 'wp-agent' ),
			);
		}

		$coupon = new \WC_Coupon( $coupon_id );
		if ( ! $coupon->get_id() ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Coupon not found.', 'wp-agent' ),
			);
		}

		$coupon->delete( true );

		return array(
			'success' => true,
			'data'    => array( 'coupon_id' => $coupon_id ),
			'message' => sprintf( __( 'Coupon #%d deleted.', 'wp-agent' ), $coupon_id ),
		);
	}
}
