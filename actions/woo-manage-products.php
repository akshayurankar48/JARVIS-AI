<?php
/**
 * WooCommerce Manage Products Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Products implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_products';
	}

	public function get_description(): string {
		return 'Manage WooCommerce products. List, create, update, or delete products. '
			. 'Supports simple and variable product types with pricing, stock, categories, and images.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation'         => array(
					'type' => 'string',
					'enum' => array( 'list', 'create', 'update', 'delete' ),
				),
				'product_id'        => array(
					'type'        => 'integer',
					'description' => 'Product ID for update/delete.',
				),
				'name'              => array(
					'type'        => 'string',
					'description' => 'Product name.',
				),
				'type'              => array(
					'type' => 'string',
					'enum' => array( 'simple', 'variable', 'grouped', 'external' ),
				),
				'price'             => array(
					'type'        => 'string',
					'description' => 'Regular price.',
				),
				'sale_price'        => array(
					'type'        => 'string',
					'description' => 'Sale price.',
				),
				'sku'               => array( 'type' => 'string' ),
				'stock'             => array(
					'type'        => 'integer',
					'description' => 'Stock quantity.',
				),
				'description'       => array(
					'type'        => 'string',
					'description' => 'Product description.',
				),
				'short_description' => array( 'type' => 'string' ),
				'categories'        => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => 'Category term IDs.',
				),
				'image_url'         => array(
					'type'        => 'string',
					'description' => 'Featured image URL.',
				),
				'status'            => array(
					'type' => 'string',
					'enum' => array( 'publish', 'draft', 'pending' ),
				),
				'per_page'          => array(
					'type'        => 'integer',
					'description' => 'Products per page for list. Default 20.',
				),
				'page'              => array( 'type' => 'integer' ),
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
			case 'list':
				return $this->list_products( $params );
			case 'create':
				return $this->create_product( $params );
			case 'update':
				return $this->update_product( $params );
			case 'delete':
				return $this->delete_product( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				);
		}
	}

	private function list_products( array $params ) {
		$per_page = isset( $params['per_page'] ) ? min( absint( $params['per_page'] ), 50 ) : 20;
		$page     = isset( $params['page'] ) ? absint( $params['page'] ) : 1;

		$args = array(
			'status'  => 'publish',
			'limit'   => $per_page,
			'page'    => $page,
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		$products = wc_get_products( $args );
		$list     = array();

		foreach ( $products as $product ) {
			$list[] = array(
				'id'         => $product->get_id(),
				'name'       => $product->get_name(),
				'type'       => $product->get_type(),
				'price'      => $product->get_price(),
				'sale_price' => $product->get_sale_price(),
				'sku'        => $product->get_sku(),
				'stock'      => $product->get_stock_quantity(),
				'status'     => $product->get_status(),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'products' => $list,
				'page'     => $page,
			),
			'message' => sprintf( __( '%d product(s) found.', 'wp-agent' ), count( $list ) ),
		);
	}

	private function create_product( array $params ) {
		$name = sanitize_text_field( $params['name'] ?? '' );
		if ( empty( $name ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Product name is required.', 'wp-agent' ),
			);
		}

		$type    = sanitize_text_field( $params['type'] ?? 'simple' );
		$product = ( 'variable' === $type ) ? new \WC_Product_Variable() : new \WC_Product_Simple();

		$product->set_name( $name );
		if ( isset( $params['price'] ) ) {
			$product->set_regular_price( sanitize_text_field( $params['price'] ) );
		}
		if ( isset( $params['sale_price'] ) ) {
			$product->set_sale_price( sanitize_text_field( $params['sale_price'] ) );
		}
		if ( isset( $params['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $params['sku'] ) );
		}
		if ( isset( $params['stock'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( absint( $params['stock'] ) );
		}
		if ( isset( $params['description'] ) ) {
			$product->set_description( wp_kses_post( $params['description'] ) );
		}
		if ( isset( $params['short_description'] ) ) {
			$product->set_short_description( wp_kses_post( $params['short_description'] ) );
		}
		if ( isset( $params['categories'] ) ) {
			$product->set_category_ids( array_map( 'absint', $params['categories'] ) );
		}
		$product->set_status( sanitize_text_field( $params['status'] ?? 'publish' ) );

		$product_id = $product->save();

		return array(
			'success' => true,
			'data'    => array(
				'product_id' => $product_id,
				'name'       => $name,
			),
			'message' => sprintf( __( 'Product "%1$s" created (ID: %2$d).', 'wp-agent' ), $name, $product_id ),
		);
	}

	private function update_product( array $params ) {
		$product_id = absint( $params['product_id'] ?? 0 );
		if ( ! $product_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'product_id is required.', 'wp-agent' ),
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Product not found.', 'wp-agent' ),
			);
		}

		$updated = array();
		if ( isset( $params['name'] ) ) {
			$product->set_name( sanitize_text_field( $params['name'] ) );
			$updated[] = 'name'; }
		if ( isset( $params['price'] ) ) {
			$product->set_regular_price( sanitize_text_field( $params['price'] ) );
			$updated[] = 'price'; }
		if ( isset( $params['sale_price'] ) ) {
			$product->set_sale_price( sanitize_text_field( $params['sale_price'] ) );
			$updated[] = 'sale_price'; }
		if ( isset( $params['sku'] ) ) {
			$product->set_sku( sanitize_text_field( $params['sku'] ) );
			$updated[] = 'sku'; }
		if ( isset( $params['stock'] ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( absint( $params['stock'] ) );
			$updated[] = 'stock'; }
		if ( isset( $params['status'] ) ) {
			$product->set_status( sanitize_text_field( $params['status'] ) );
			$updated[] = 'status'; }
		if ( isset( $params['description'] ) ) {
			$product->set_description( wp_kses_post( $params['description'] ) );
			$updated[] = 'description'; }

		$product->save();

		return array(
			'success' => true,
			'data'    => array(
				'product_id' => $product_id,
				'updated'    => $updated,
			),
			'message' => sprintf( __( 'Product #%1$d updated (%2$s).', 'wp-agent' ), $product_id, implode( ', ', $updated ) ),
		);
	}

	private function delete_product( array $params ) {
		$product_id = absint( $params['product_id'] ?? 0 );
		if ( ! $product_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'product_id is required.', 'wp-agent' ),
			);
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Product not found.', 'wp-agent' ),
			);
		}

		$name = $product->get_name();
		$product->delete( true );

		return array(
			'success' => true,
			'data'    => array( 'product_id' => $product_id ),
			'message' => sprintf( __( 'Product "%1$s" (#%2$d) deleted.', 'wp-agent' ), $name, $product_id ),
		);
	}
}
