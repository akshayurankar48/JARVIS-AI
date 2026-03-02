<?php
/**
 * WooCommerce Manage Categories Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Categories implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_categories';
	}

	public function get_description(): string {
		return 'Manage WooCommerce product categories. List, create, update, or delete product categories.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation'   => array(
					'type' => 'string',
					'enum' => array( 'list', 'create', 'update', 'delete' ),
				),
				'category_id' => array( 'type' => 'integer' ),
				'name'        => array( 'type' => 'string' ),
				'slug'        => array( 'type' => 'string' ),
				'description' => array( 'type' => 'string' ),
				'parent'      => array(
					'type'        => 'integer',
					'description' => 'Parent category ID.',
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
			case 'list':
				$terms = get_terms(
					array(
						'taxonomy'   => 'product_cat',
						'hide_empty' => false,
					)
				);
				$list  = array();
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$list[] = array(
							'id'     => $term->term_id,
							'name'   => $term->name,
							'slug'   => $term->slug,
							'count'  => $term->count,
							'parent' => $term->parent,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => array( 'categories' => $list ),
					'message' => sprintf( __( '%d category(ies).', 'wp-agent' ), count( $list ) ),
				);

			case 'create':
				$name = sanitize_text_field( $params['name'] ?? '' );
				if ( empty( $name ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'name is required.', 'wp-agent' ),
					);
				}
				$args = array();
				if ( isset( $params['slug'] ) ) {
					$args['slug'] = sanitize_title( $params['slug'] );
				}
				if ( isset( $params['description'] ) ) {
					$args['description'] = sanitize_textarea_field( $params['description'] );
				}
				if ( isset( $params['parent'] ) ) {
					$args['parent'] = absint( $params['parent'] );
				}
				$result = wp_insert_term( $name, 'product_cat', $args );
				if ( is_wp_error( $result ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => $result->get_error_message(),
					);
				}
				return array(
					'success' => true,
					'data'    => array( 'category_id' => $result['term_id'] ),
					'message' => sprintf( __( 'Category "%s" created.', 'wp-agent' ), $name ),
				);

			case 'update':
				$id = absint( $params['category_id'] ?? 0 );
				if ( ! $id ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'category_id required.', 'wp-agent' ),
					);
				}
				$args = array();
				if ( isset( $params['name'] ) ) {
					$args['name'] = sanitize_text_field( $params['name'] );
				}
				if ( isset( $params['slug'] ) ) {
					$args['slug'] = sanitize_title( $params['slug'] );
				}
				if ( isset( $params['description'] ) ) {
					$args['description'] = sanitize_textarea_field( $params['description'] );
				}
				$result = wp_update_term( $id, 'product_cat', $args );
				if ( is_wp_error( $result ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => $result->get_error_message(),
					);
				}
				return array(
					'success' => true,
					'data'    => array( 'category_id' => $id ),
					'message' => sprintf( __( 'Category #%d updated.', 'wp-agent' ), $id ),
				);

			case 'delete':
				$id = absint( $params['category_id'] ?? 0 );
				if ( ! $id ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => __( 'category_id required.', 'wp-agent' ),
					);
				}
				$result = wp_delete_term( $id, 'product_cat' );
				if ( is_wp_error( $result ) ) {
					return array(
						'success' => false,
						'data'    => null,
						'message' => $result->get_error_message(),
					);
				}
				return array(
					'success' => true,
					'data'    => array( 'category_id' => $id ),
					'message' => sprintf( __( 'Category #%d deleted.', 'wp-agent' ), $id ),
				);

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				);
		}
	}
}
