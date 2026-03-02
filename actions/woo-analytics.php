<?php
/**
 * WooCommerce Analytics Action.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Woo_Analytics
 *
 * Provides WooCommerce analytics including sales summaries, top products, and customer statistics.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Woo_Analytics implements Action_Interface {

	/**
	 * Get the action identifier.
	 *
	 * @since  1.1.0
	 * @return string Action identifier.
	 */
	public function get_name(): string {
		return 'woo_analytics';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Get WooCommerce analytics. Sales summary, top products, order statistics, and customer stats with date range filtering.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation' => array(
					'type' => 'string',
					'enum' => array( 'sales_summary', 'top_products', 'order_stats', 'customer_stats' ),
				),
				'date_from' => array(
					'type'        => 'string',
					'description' => 'Start date (YYYY-MM-DD). Defaults to 30 days ago.',
				),
				'date_to'   => array(
					'type'        => 'string',
					'description' => 'End date (YYYY-MM-DD). Defaults to today.',
				),
				'limit'     => array(
					'type'        => 'integer',
					'description' => 'Number of results for top_products. Default 10.',
				),
			),
			'required'   => array( 'operation' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'view_woocommerce_reports';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';
		$date_from = sanitize_text_field( $params['date_from'] ?? gmdate( 'Y-m-d', strtotime( '-30 days' ) ) );
		$date_to   = sanitize_text_field( $params['date_to'] ?? gmdate( 'Y-m-d' ) );

		switch ( $operation ) {
			case 'sales_summary':
				return $this->sales_summary( $date_from, $date_to );
			case 'top_products':
				$limit = isset( $params['limit'] ) ? min( absint( $params['limit'] ), 50 ) : 10;
				return $this->top_products( $date_from, $date_to, $limit );
			case 'order_stats':
				return $this->order_stats( $date_from, $date_to );
			case 'customer_stats':
				return $this->customer_stats();
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * Get sales summary for a date range.
	 *
	 * @since 1.1.0
	 *
	 * @param string $from Start date in YYYY-MM-DD format.
	 * @param string $to   End date in YYYY-MM-DD format.
	 * @return array Execution result.
	 */
	private function sales_summary( string $from, string $to ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Using WC order meta (HPOS-compatible fallback).
		$results = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COUNT(*) as order_count,
					COALESCE(SUM(pm_total.meta_value), 0) as total_sales,
					COALESCE(AVG(pm_total.meta_value), 0) as avg_order_value
				FROM {$wpdb->posts} p
				LEFT JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
				WHERE p.post_type = 'shop_order'
				AND p.post_status IN ('wc-completed', 'wc-processing')
				AND p.post_date >= %s
				AND p.post_date <= %s",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$refunds = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(pm.meta_value), 0)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_order_total'
				WHERE p.post_type = 'shop_order'
				AND p.post_status = 'wc-refunded'
				AND p.post_date >= %s AND p.post_date <= %s",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			)
		);

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array(
				'period'          => "$from to $to",
				'order_count'     => (int) ( $results['order_count'] ?? 0 ),
				'total_sales'     => round( (float) ( $results['total_sales'] ?? 0 ), 2 ),
				'avg_order_value' => round( (float) ( $results['avg_order_value'] ?? 0 ), 2 ),
				'total_refunds'   => round( (float) $refunds, 2 ),
				'net_sales'       => round( (float) ( $results['total_sales'] ?? 0 ) - (float) $refunds, 2 ),
				'currency'        => get_woocommerce_currency(),
			),
			'message' => sprintf( __( 'Sales summary for %1$s to %2$s.', 'jarvis-ai' ), $from, $to ),
		);
	}

	/**
	 * Get top-selling products by revenue for a date range.
	 *
	 * @since 1.1.0
	 *
	 * @param string $from  Start date in YYYY-MM-DD format.
	 * @param string $to    End date in YYYY-MM-DD format.
	 * @param int    $limit Maximum number of products to return.
	 * @return array Execution result.
	 */
	private function top_products( string $from, string $to, int $limit ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$products = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT oi.order_item_name as name,
					SUM(oim_qty.meta_value) as total_qty,
					SUM(oim_total.meta_value) as total_revenue
				FROM {$wpdb->prefix}woocommerce_order_items oi
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty ON oi.order_item_id = oim_qty.order_item_id AND oim_qty.meta_key = '_qty'
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total ON oi.order_item_id = oim_total.order_item_id AND oim_total.meta_key = '_line_total'
				INNER JOIN {$wpdb->posts} p ON oi.order_id = p.ID
				WHERE p.post_type = 'shop_order'
				AND p.post_status IN ('wc-completed', 'wc-processing')
				AND p.post_date >= %s AND p.post_date <= %s
				AND oi.order_item_type = 'line_item'
				GROUP BY oi.order_item_name
				ORDER BY total_revenue DESC
				LIMIT %d",
				$from . ' 00:00:00',
				$to . ' 23:59:59',
				$limit
			),
			ARRAY_A
		);

		$list = array();
		foreach ( $products as $p ) {
			$list[] = array(
				'name'     => $p['name'],
				'quantity' => (int) $p['total_qty'],
				'revenue'  => round( (float) $p['total_revenue'], 2 ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'top_products' => $list,
				'period'       => "$from to $to",
			),
			'message' => sprintf( __( 'Top %d products by revenue.', 'jarvis-ai' ), count( $list ) ),
		);
	}

	/**
	 * Get order statistics grouped by status for a date range.
	 *
	 * @since 1.1.0
	 *
	 * @param string $from Start date in YYYY-MM-DD format.
	 * @param string $to   End date in YYYY-MM-DD format.
	 * @return array Execution result.
	 */
	private function order_stats( string $from, string $to ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_status as status, COUNT(*) as count
				FROM {$wpdb->posts}
				WHERE post_type = 'shop_order'
				AND post_date >= %s AND post_date <= %s
				GROUP BY post_status",
				$from . ' 00:00:00',
				$to . ' 23:59:59'
			),
			ARRAY_A
		);

		$status_map = array();
		foreach ( $stats as $row ) {
			$label                = str_replace( 'wc-', '', $row['status'] );
			$status_map[ $label ] = (int) $row['count'];
		}

		return array(
			'success' => true,
			'data'    => array(
				'order_stats' => $status_map,
				'period'      => "$from to $to",
			),
			'message' => __( 'Order statistics by status.', 'jarvis-ai' ),
		);
	}

	/**
	 * Get customer statistics including total and repeat customer counts.
	 *
	 * @since 1.1.0
	 * @return array Execution result.
	 */
	private function customer_stats() {
		$total_customers = count(
			get_users(
				array(
					'role'   => 'customer',
					'fields' => 'ID',
				)
			)
		);

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$repeat = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_customer_user' AND pm.meta_value > 0
			AND p.post_type = 'shop_order' AND p.post_status IN ('wc-completed', 'wc-processing')
			GROUP BY pm.meta_value HAVING COUNT(*) > 1"
		);

		return array(
			'success' => true,
			'data'    => array(
				'total_customers'  => $total_customers,
				'repeat_customers' => (int) $repeat,
			),
			'message' => sprintf( __( '%1$d total customers, %2$d repeat.', 'jarvis-ai' ), $total_customers, (int) $repeat ),
		);
	}
}
