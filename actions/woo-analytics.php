<?php
/**
 * WooCommerce Analytics Action.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

class Woo_Analytics implements Action_Interface {

	public function get_name(): string {
		return 'woo_analytics';
	}

	public function get_description(): string {
		return 'Get WooCommerce analytics. Sales summary, top products, order statistics, and customer stats with date range filtering.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'  => [ 'type' => 'string', 'enum' => [ 'sales_summary', 'top_products', 'order_stats', 'customer_stats' ] ],
				'date_from'  => [ 'type' => 'string', 'description' => 'Start date (YYYY-MM-DD). Defaults to 30 days ago.' ],
				'date_to'    => [ 'type' => 'string', 'description' => 'End date (YYYY-MM-DD). Defaults to today.' ],
				'limit'      => [ 'type' => 'integer', 'description' => 'Number of results for top_products. Default 10.' ],
			],
			'required'   => [ 'operation' ],
		];
	}

	public function get_capabilities_required(): string {
		return 'view_woocommerce_reports';
	}

	public function is_reversible(): bool {
		return false;
	}

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
				return [ 'success' => false, 'data' => null, 'message' => __( 'Invalid operation.', 'wp-agent' ) ];
		}
	}

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

		return [
			'success' => true,
			'data'    => [
				'period'          => "$from to $to",
				'order_count'     => (int) ( $results['order_count'] ?? 0 ),
				'total_sales'     => round( (float) ( $results['total_sales'] ?? 0 ), 2 ),
				'avg_order_value' => round( (float) ( $results['avg_order_value'] ?? 0 ), 2 ),
				'total_refunds'   => round( (float) $refunds, 2 ),
				'net_sales'       => round( (float) ( $results['total_sales'] ?? 0 ) - (float) $refunds, 2 ),
				'currency'        => get_woocommerce_currency(),
			],
			'message' => sprintf( __( 'Sales summary for %s to %s.', 'wp-agent' ), $from, $to ),
		];
	}

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

		$list = [];
		foreach ( $products as $p ) {
			$list[] = [
				'name'     => $p['name'],
				'quantity' => (int) $p['total_qty'],
				'revenue'  => round( (float) $p['total_revenue'], 2 ),
			];
		}

		return [
			'success' => true,
			'data'    => [ 'top_products' => $list, 'period' => "$from to $to" ],
			'message' => sprintf( __( 'Top %d products by revenue.', 'wp-agent' ), count( $list ) ),
		];
	}

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

		$status_map = [];
		foreach ( $stats as $row ) {
			$label = str_replace( 'wc-', '', $row['status'] );
			$status_map[ $label ] = (int) $row['count'];
		}

		return [
			'success' => true,
			'data'    => [ 'order_stats' => $status_map, 'period' => "$from to $to" ],
			'message' => __( 'Order statistics by status.', 'wp-agent' ),
		];
	}

	private function customer_stats() {
		$total_customers = count( get_users( [ 'role' => 'customer', 'fields' => 'ID' ] ) );

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$repeat = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.meta_value) FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_customer_user' AND pm.meta_value > 0
			AND p.post_type = 'shop_order' AND p.post_status IN ('wc-completed', 'wc-processing')
			GROUP BY pm.meta_value HAVING COUNT(*) > 1"
		);

		return [
			'success' => true,
			'data'    => [
				'total_customers'  => $total_customers,
				'repeat_customers' => (int) $repeat,
			],
			'message' => sprintf( __( '%d total customers, %d repeat.', 'wp-agent' ), $total_customers, (int) $repeat ),
		];
	}
}
