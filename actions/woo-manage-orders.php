<?php
/**
 * WooCommerce Manage Orders Action.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Woo_Manage_Orders
 *
 * Handles WooCommerce order management including listing, details, status updates, and notes.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Woo_Manage_Orders implements Action_Interface {

	/**
	 * Get the action identifier.
	 *
	 * @since  1.1.0
	 * @return string Action identifier.
	 */
	public function get_name(): string {
		return 'woo_manage_orders';
	}

	/**
	 * Get the human-readable description.
	 *
	 * @since  1.1.0
	 * @return string Human-readable description.
	 */
	public function get_description(): string {
		return 'Manage WooCommerce orders. List recent orders, get order details, update order status, or add order notes.';
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
	 * Execute the order management action.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters.
	 * @return array Result with success status, data, and message.
	 */
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
					'message' => __( 'Invalid operation.', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * List recent WooCommerce orders with optional status filtering.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters including per_page, page, and filter_status.
	 * @return array Result with paginated list of orders.
	 */
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
			'message' => sprintf( __( '%d order(s) found.', 'jarvis-ai' ), count( $list ) ),
		);
	}

	/**
	 * Get detailed information for a single order.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters including order_id.
	 * @return array Result with order details including items, billing, and shipping.
	 */
	private function get_order( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		if ( ! $order_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id is required.', 'jarvis-ai' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'jarvis-ai' ),
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
			'message' => sprintf( __( 'Order #%d details.', 'jarvis-ai' ), $order_id ),
		);
	}

	/**
	 * Update the status of a WooCommerce order.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters including order_id and status.
	 * @return array Result with old and new status values.
	 */
	private function update_status( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		$status   = sanitize_text_field( $params['status'] ?? '' );

		if ( ! $order_id || empty( $status ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id and status are required.', 'jarvis-ai' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'jarvis-ai' ),
			);
		}

		$old_status = $order->get_status();
		$order->update_status( $status, __( 'Status updated via JARVIS AI.', 'jarvis-ai' ) );

		return array(
			'success' => true,
			'data'    => array(
				'order_id'   => $order_id,
				'old_status' => $old_status,
				'new_status' => $status,
			),
			'message' => sprintf( __( 'Order #%1$d status: %2$s -> %3$s.', 'jarvis-ai' ), $order_id, $old_status, $status ),
		);
	}

	/**
	 * Add a note to a WooCommerce order.
	 *
	 * @since  1.1.0
	 *
	 * @param  array $params Action parameters including order_id and note text.
	 * @return array Result with order_id and note_id.
	 */
	private function add_note( array $params ) {
		$order_id = absint( $params['order_id'] ?? 0 );
		$note     = sanitize_textarea_field( $params['note'] ?? '' );

		if ( ! $order_id || empty( $note ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'order_id and note are required.', 'jarvis-ai' ),
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Order not found.', 'jarvis-ai' ),
			);
		}

		$note_id = $order->add_order_note( $note );

		return array(
			'success' => true,
			'data'    => array(
				'order_id' => $order_id,
				'note_id'  => $note_id,
			),
			'message' => sprintf( __( 'Note added to order #%d.', 'jarvis-ai' ), $order_id ),
		);
	}

	/**
	 * Mask an email address for privacy by hiding part of the local portion.
	 *
	 * @since  1.1.0
	 *
	 * @param  string $email The email address to mask.
	 * @return string Masked email address.
	 */
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
