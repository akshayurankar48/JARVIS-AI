<?php
/**
 * Manage Transients Action.
 *
 * Lists, retrieves, deletes, and cleans up WordPress transients
 * stored in the options table. Useful for cache management and
 * debugging.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Transients
 *
 * @since 1.1.0
 */
class Manage_Transients implements Action_Interface {

	/**
	 * Maximum transients to return in list.
	 *
	 * @var int
	 */
	const MAX_LIST = 50;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_transients';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage WordPress transients (cached data). Operations: "list" shows transients with expiry info, '
			. '"get" retrieves a specific transient value, "delete" removes a transient by name, '
			. '"delete_expired" cleans up all expired transients. Use for cache management and debugging.';
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
					'type'        => 'string',
					'enum'        => array( 'list', 'get', 'delete', 'delete_expired' ),
					'description' => 'Operation to perform.',
				),
				'name'      => array(
					'type'        => 'string',
					'description' => 'Transient name (without _transient_ prefix). Required for "get" and "delete".',
				),
				'search'    => array(
					'type'        => 'string',
					'description' => 'Filter transients by name pattern (for "list").',
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
		return 'manage_options';
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

		switch ( $operation ) {
			case 'list':
				return $this->list_transients( $params );

			case 'get':
				return $this->get_transient( $params );

			case 'delete':
				return $this->delete_transient( $params );

			case 'delete_expired':
				return $this->delete_expired();

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "get", "delete", or "delete_expired".', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * List transients from the database.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function list_transients( array $params ) {
		global $wpdb;

		$search = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

		$where = 'WHERE option_name LIKE %s';
		$args  = array( $wpdb->esc_like( '_transient_' ) . '%' );

		if ( $search ) {
			$where .= ' AND option_name LIKE %s';
			$args[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		// Exclude timeout entries from the main list.
		$where .= ' AND option_name NOT LIKE %s';
		$args[] = $wpdb->esc_like( '_transient_timeout_' ) . '%';

		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $where built dynamically with %s placeholders.
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} {$where}",
				...$args
			)
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} {$where} ORDER BY option_name ASC LIMIT %d",
				...array_merge( $args, array( self::MAX_LIST ) )
			)
		);

		$transients = array();
		$now        = time();

		foreach ( $results as $row ) {
			// Strip the _transient_ prefix.
			$name = str_replace( '_transient_', '', $row->option_name );

			// Check expiration.
			$timeout = get_option( '_transient_timeout_' . $name );
			$expires = $timeout ? (int) $timeout : 0;

			$transients[] = array(
				'name'    => $name,
				'size'    => strlen( $row->option_value ),
				'expires' => $expires > 0 ? gmdate( 'Y-m-d H:i:s', $expires ) : 'never',
				'expired' => $expires > 0 && $expires < $now,
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'transients' => $transients,
				'shown'      => count( $transients ),
				'total'      => $total,
			),
			'message' => sprintf(
				/* translators: 1: shown count, 2: total count */
				__( 'Showing %1$d of %2$d transient(s).', 'jarvis-ai' ),
				count( $transients ),
				$total
			),
		);
	}

	/**
	 * Get a specific transient value.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function get_transient( array $params ) {
		$name = ! empty( $params['name'] ) ? sanitize_key( $params['name'] ) : '';

		if ( empty( $name ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Transient name is required.', 'jarvis-ai' ),
			);
		}

		$value = get_transient( $name );

		if ( false === $value ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: transient name */
					__( 'Transient "%s" not found or expired.', 'jarvis-ai' ),
					$name
				),
			);
		}

		$timeout = get_option( '_transient_timeout_' . $name );

		// Truncate large values for display.
		$display_value = $value;
		if ( is_string( $value ) && strlen( $value ) > 2000 ) {
			$display_value = substr( $value, 0, 2000 ) . '... [truncated]';
		} elseif ( is_array( $value ) || is_object( $value ) ) {
			$json = wp_json_encode( $value );
			if ( strlen( $json ) > 2000 ) {
				$display_value = substr( $json, 0, 2000 ) . '... [truncated]';
			} else {
				$display_value = $json;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'name'    => $name,
				'value'   => $display_value,
				'type'    => gettype( $value ),
				'size'    => strlen( maybe_serialize( $value ) ),
				'expires' => $timeout ? gmdate( 'Y-m-d H:i:s', (int) $timeout ) : 'never',
			),
			'message' => sprintf(
				/* translators: %s: transient name */
				__( 'Retrieved transient "%s".', 'jarvis-ai' ),
				$name
			),
		);
	}

	/**
	 * Delete a specific transient.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function delete_transient( array $params ) {
		$name = ! empty( $params['name'] ) ? sanitize_key( $params['name'] ) : '';

		if ( empty( $name ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Transient name is required.', 'jarvis-ai' ),
			);
		}

		$existed = false !== get_transient( $name );
		$result  = delete_transient( $name );

		return array(
			'success' => $result || ! $existed,
			'data'    => array(
				'name'    => $name,
				'deleted' => $result,
			),
			'message' => $result
				? sprintf(
					/* translators: %s: transient name */
					__( 'Transient "%s" deleted.', 'jarvis-ai' ),
					$name
				)
				: sprintf(
					/* translators: %s: transient name */
					__( 'Transient "%s" not found or already deleted.', 'jarvis-ai' ),
					$name
				),
		);
	}

	/**
	 * Delete all expired transients.
	 *
	 * @since 1.1.0
	 *
	 * @return array Execution result.
	 */
	private function delete_expired() {
		global $wpdb;

		$now = time();

		// Find expired transient timeouts.
		$expired = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options}
			WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$now
			)
		);

		$deleted = 0;

		foreach ( $expired as $timeout_key ) {
			$transient_name = str_replace( '_transient_timeout_', '', $timeout_key );
			if ( delete_transient( $transient_name ) ) {
				++$deleted;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'expired_found' => count( $expired ),
				'deleted'       => $deleted,
			),
			'message' => sprintf(
				/* translators: 1: deleted count, 2: found count */
				__( 'Deleted %1$d of %2$d expired transient(s).', 'jarvis-ai' ),
				$deleted,
				count( $expired )
			),
		);
	}
}
