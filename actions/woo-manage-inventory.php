<?php
/**
 * WooCommerce Manage Inventory Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Inventory implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_inventory';
	}

	public function get_description(): string {
		return 'Manage WooCommerce product inventory. Check stock levels, update stock quantities, get low stock reports, or bulk update inventory.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'  => [ 'type' => 'string', 'enum' => [ 'check_stock', 'update_stock', 'low_stock_report', 'bulk_update' ] ],
				'product_id' => [ 'type' => 'integer' ],
				'quantity'   => [ 'type' => 'integer', 'description' => 'New stock quantity for update_stock.' ],
				'threshold'  => [ 'type' => 'integer', 'description' => 'Stock threshold for low_stock_report. Default 5.' ],
				'updates'    => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'product_id' => [ 'type' => 'integer' ],
							'quantity'   => [ 'type' => 'integer' ],
						],
					],
					'description' => 'Array of {product_id, quantity} for bulk_update.',
				],
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
			case 'check_stock':
				return $this->check_stock( $params );
			case 'update_stock':
				return $this->update_stock( $params );
			case 'low_stock_report':
				return $this->low_stock_report( $params );
			case 'bulk_update':
				return $this->bulk_update( $params );
			default:
				return [ 'success' => false, 'data' => null, 'message' => __( 'Invalid operation.', 'wp-agent' ) ];
		}
	}

	private function check_stock( array $params ) {
		$product_id = absint( $params['product_id'] ?? 0 );
		if ( ! $product_id ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'product_id required.', 'wp-agent' ) ];
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'Product not found.', 'wp-agent' ) ];
		}

		return [
			'success' => true,
			'data'    => [
				'product_id'    => $product_id,
				'name'          => $product->get_name(),
				'manage_stock'  => $product->get_manage_stock(),
				'stock_quantity' => $product->get_stock_quantity(),
				'stock_status'  => $product->get_stock_status(),
				'backorders'    => $product->get_backorders(),
			],
			'message' => sprintf( __( '%s: %s (qty: %s).', 'wp-agent' ), $product->get_name(), $product->get_stock_status(), $product->get_stock_quantity() ?? 'N/A' ),
		];
	}

	private function update_stock( array $params ) {
		$product_id = absint( $params['product_id'] ?? 0 );
		$quantity   = isset( $params['quantity'] ) ? (int) $params['quantity'] : null;

		if ( ! $product_id || null === $quantity ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'product_id and quantity required.', 'wp-agent' ) ];
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'Product not found.', 'wp-agent' ) ];
		}

		$old_qty = $product->get_stock_quantity();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $quantity );
		$product->save();

		return [
			'success' => true,
			'data'    => [
				'product_id'   => $product_id,
				'old_quantity' => $old_qty,
				'new_quantity' => $quantity,
			],
			'message' => sprintf( __( '%s stock: %s -> %d.', 'wp-agent' ), $product->get_name(), $old_qty ?? 'N/A', $quantity ),
		];
	}

	private function low_stock_report( array $params ) {
		$threshold = isset( $params['threshold'] ) ? absint( $params['threshold'] ) : 5;

		$args = [
			'limit'        => 50,
			'manage_stock' => true,
			'stock_status' => 'instock',
			'orderby'      => 'meta_value_num',
			'meta_key'     => '_stock', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'order'        => 'ASC',
		];

		$products = wc_get_products( $args );
		$low      = [];

		foreach ( $products as $product ) {
			$qty = $product->get_stock_quantity();
			if ( null !== $qty && $qty <= $threshold ) {
				$low[] = [
					'id'       => $product->get_id(),
					'name'     => $product->get_name(),
					'sku'      => $product->get_sku(),
					'stock'    => $qty,
					'status'   => $product->get_stock_status(),
				];
			}
		}

		return [
			'success' => true,
			'data'    => [ 'threshold' => $threshold, 'products' => $low ],
			'message' => sprintf( __( '%d product(s) with stock <= %d.', 'wp-agent' ), count( $low ), $threshold ),
		];
	}

	private function bulk_update( array $params ) {
		$updates = isset( $params['updates'] ) && is_array( $params['updates'] ) ? $params['updates'] : [];

		if ( empty( $updates ) ) {
			return [ 'success' => false, 'data' => null, 'message' => __( 'updates array required.', 'wp-agent' ) ];
		}

		$results = [];
		foreach ( array_slice( $updates, 0, 100 ) as $update ) {
			$pid = absint( $update['product_id'] ?? 0 );
			$qty = isset( $update['quantity'] ) ? (int) $update['quantity'] : null;

			if ( ! $pid || null === $qty ) continue;

			$product = wc_get_product( $pid );
			if ( ! $product ) continue;

			$product->set_manage_stock( true );
			$product->set_stock_quantity( $qty );
			$product->save();

			$results[] = [ 'product_id' => $pid, 'quantity' => $qty ];
		}

		return [
			'success' => true,
			'data'    => [ 'updated' => $results ],
			'message' => sprintf( __( 'Updated stock for %d product(s).', 'wp-agent' ), count( $results ) ),
		];
	}
}
