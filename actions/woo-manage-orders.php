<?php
/**
 * WooCommerce Manage Orders Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Manage_Orders implements Action_Interface {

	public function get_name(): string {
		return 'woo_manage_orders';
	}

	public function get_description(): string {
		return 'Manage WooCommerce orders. List recent orders, get order details, update order status, or add order notes.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation'     => array(
					'type' => 'string',
					'enum' => array( 'list', 'get', 'update_status', 'add_note' ),
				),
				'order_id'      => array(
					'type'        => 'integer',
					'description' => 'Order ID.',
				),
				'status'        => array(
					'type' => 'string',
					'enum' => array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' ),
				),
				'note'          => array(
					'type'        => 'string',
					'description' => 'Note text for add_note.',
				),
				'per_page'      => array( 'type' => 'integer' ),
				'page'          => array( 'type' => 'integer' ),
				'filter_status' => array(
					'type'        => 'string',
					'description' => 'Filter orders by status for list.',
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
				return $this->list_orders( $params );
			case 'get':
				return $this->get_order( $params );
			case 'update_status':
				return $this->update_status( $params );
			case 'add_note':
				return $this->add_note( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				);
		}
	}

	private function list_orders( array $params ) {
		$per_page = isset( $params['per_page'] ) ? min( absint( $params['per_page'] ), 50 ) : 20;
		$page     = isset( $params['page'] ) ? absint( $params['page'] ) : 1;

		$args = array(
			'limit'   => $per_page,
			'page'    => $page,
			'orderby' => 'date',
			'order'   => 'DESC',
		);

		if ( ! empty( $params['filter_status'] ) ) {
			$args['status'] = sanitize_text_field( $params['filter_status'] );
		}

		$orders = wc_get_orders( $args );
		$list   = array();

		foreach ( $orders as $order ) {
			$list[] = array(
				'id'       => $order->get_id(),
				'status'   => $order->get_status(),
				'total'    => $order->get_total(),
				'currency' => $order->get_currency(),
				'customer' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'items'    => $order->get_item_count(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'orders' => $list,
				'page'   => $page,
			),
			'message' => sprintf( __( '%d order(s) found.', 'wp-agent' ), count( $list ) ),
		);
	}

	private function get_order( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		if ( ! $order_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id is required.', 'wp-agent' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'wp-agent' ),
			);
		}

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$items[] = array(
				'name'     => $item->get_name(),
				'quantity' => $item->get_quantity(),
				'total'    => $item->get_total(),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'id'              => $order->get_id(),
				'status'          => $order->get_status(),
				'total'           => $order->get_total(),
				'currency'        => $order->get_currency(),
				'payment_method'  => $order->get_payment_method_title(),
				'billing_name'    => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'billing_email'   => $this->mask_email( $order->get_billing_email() ),
				'shipping_method' => $order->get_shipping_method(),
				'items'           => $items,
				'date_created'    => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			),
			'message' => sprintf( __( 'Order #%d details.', 'wp-agent' ), $order_id ),
		);
	}

	private function update_status( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		$status   = sanitize_text_field( $params['status'] ?? '' );

		if ( ! $order_id || empty( $status ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id and status are required.', 'wp-agent' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'wp-agent' ),
			);
		}

		$old_status = $order->get_status();
		$order->update_status( $status, __( 'Status updated via WP Agent.', 'wp-agent' ) );

		return array(
			'success' => true,
			'data'    => array(
				'order_id'   => $order_id,
				'old_status' => $old_status,
				'new_status' => $status,
			),
			'message' => sprintf( __( 'Order #%1$d status: %2$s -> %3$s.', 'wp-agent' ), $order_id, $old_status, $status ),
		);
	}

	private function add_note( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		$note     = sanitize_textarea_field( $params['note'] ?? '' );

		if ( ! $order_id || empty( $note ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id and note are required.', 'wp-agent' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'wp-agent' ),
			);
		}

		$note_id = $order->add_order_note( $note );

		return array(
			'success' => true,
			'data'    => array(
				'order_id' => $order_id,
				'note_id'  => $note_id,
			),
			'message' => sprintf( __( 'Note added to order #%d.', 'wp-agent' ), $order_id ),
		);
	}

	private function mask_email( $email ) {
		if ( empty( $email ) || strpos( $email, '@' ) === false ) {
			return '***';
		}
		$parts  = explode( '@', $email );
		$local  = $parts[0];
		$masked = substr( $local, 0, 2 ) . '***@' . $parts[1];
		return $masked;
	}
}
