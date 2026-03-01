<?php
/**
 * Manage Options Bulk Action.
 *
 * Gets, sets, deletes, and searches WordPress options with a blocklist
 * for sensitive options (auth keys, DB credentials, etc.).
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Options_Bulk
 *
 * @since 1.1.0
 */
class Manage_Options_Bulk implements Action_Interface {

	/**
	 * Options that must never be read or modified.
	 *
	 * @var string[]
	 */
	const BLOCKLIST = [
		'auth_key',
		'secure_auth_key',
		'logged_in_key',
		'nonce_key',
		'auth_salt',
		'secure_auth_salt',
		'logged_in_salt',
		'nonce_salt',
		'db_password',
		'wp_agent_openrouter_key',
		'wp_agent_tavily_key',
	];

	/**
	 * Patterns to block.
	 *
	 * @var string[]
	 */
	const BLOCKLIST_PATTERNS = [
		'_key',
		'_secret',
		'_password',
		'_token',
		'_api_key',
	];

	public function get_name(): string {
		return 'manage_options_bulk';
	}

	public function get_description(): string {
		return 'Get, set, delete, or search WordPress options. Sensitive options (API keys, passwords, salts) '
			. 'are blocked for security. Use "search" to find options by name pattern.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation' => [
					'type'        => 'string',
					'enum'        => [ 'get', 'set', 'delete', 'search' ],
					'description' => 'Operation to perform.',
				],
				'options'   => [
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'name'  => [ 'type' => 'string', 'description' => 'Option name.' ],
							'value' => [ 'description' => 'Option value (for set).' ],
						],
						'required' => [ 'name' ],
					],
					'description' => 'Array of options to get/set/delete. Required for get, set, delete.',
				],
				'search'    => [
					'type'        => 'string',
					'description' => 'Search pattern for option names (e.g., "wp_agent_"). Required for search.',
				],
			],
			'required'   => [ 'operation' ],
		];
	}

	public function get_capabilities_required(): string {
		return 'manage_options';
	}

	public function is_reversible(): bool {
		return true;
	}

	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'get':
				return $this->get_options( $params );
			case 'set':
				return $this->set_options( $params );
			case 'delete':
				return $this->delete_options( $params );
			case 'search':
				return $this->search_options( $params );
			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "get", "set", "delete", or "search".', 'wp-agent' ),
				];
		}
	}

	private function is_blocked( string $name ) {
		if ( in_array( $name, self::BLOCKLIST, true ) ) {
			return true;
		}

		foreach ( self::BLOCKLIST_PATTERNS as $pattern ) {
			if ( str_ends_with( $name, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	private function get_options( array $params ) {
		$options = isset( $params['options'] ) && is_array( $params['options'] ) ? $params['options'] : [];

		if ( empty( $options ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'options array is required.', 'wp-agent' ),
			];
		}

		$results = [];
		$blocked = [];

		foreach ( $options as $opt ) {
			$name = sanitize_text_field( $opt['name'] ?? '' );
			if ( empty( $name ) ) {
				continue;
			}

			if ( $this->is_blocked( $name ) ) {
				$blocked[] = $name;
				continue;
			}

			$value = get_option( $name, null );
			$results[ $name ] = $value;
		}

		return [
			'success' => true,
			'data'    => [
				'options' => $results,
				'blocked' => $blocked,
			],
			'message' => sprintf(
				/* translators: 1: retrieved count, 2: blocked count */
				__( 'Retrieved %1$d option(s). %2$d blocked.', 'wp-agent' ),
				count( $results ),
				count( $blocked )
			),
		];
	}

	private function set_options( array $params ) {
		$options = isset( $params['options'] ) && is_array( $params['options'] ) ? $params['options'] : [];

		if ( empty( $options ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'options array is required.', 'wp-agent' ),
			];
		}

		$updated = [];
		$blocked = [];

		foreach ( $options as $opt ) {
			$name = sanitize_text_field( $opt['name'] ?? '' );
			if ( empty( $name ) || ! isset( $opt['value'] ) ) {
				continue;
			}

			if ( $this->is_blocked( $name ) ) {
				$blocked[] = $name;
				continue;
			}

			$value = $opt['value'];
			if ( is_string( $value ) ) {
				$value = sanitize_text_field( $value );
			}

			update_option( $name, $value );
			$updated[] = $name;
		}

		return [
			'success' => true,
			'data'    => [
				'updated' => $updated,
				'blocked' => $blocked,
			],
			'message' => sprintf(
				/* translators: 1: count, 2: blocked count */
				__( 'Updated %1$d option(s). %2$d blocked.', 'wp-agent' ),
				count( $updated ),
				count( $blocked )
			),
		];
	}

	private function delete_options( array $params ) {
		$options = isset( $params['options'] ) && is_array( $params['options'] ) ? $params['options'] : [];

		if ( empty( $options ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'options array is required.', 'wp-agent' ),
			];
		}

		$deleted = [];
		$blocked = [];

		foreach ( $options as $opt ) {
			$name = sanitize_text_field( $opt['name'] ?? '' );
			if ( empty( $name ) ) {
				continue;
			}

			if ( $this->is_blocked( $name ) ) {
				$blocked[] = $name;
				continue;
			}

			delete_option( $name );
			$deleted[] = $name;
		}

		return [
			'success' => true,
			'data'    => [
				'deleted' => $deleted,
				'blocked' => $blocked,
			],
			'message' => sprintf(
				/* translators: 1: count, 2: blocked count */
				__( 'Deleted %1$d option(s). %2$d blocked.', 'wp-agent' ),
				count( $deleted ),
				count( $blocked )
			),
		];
	}

	private function search_options( array $params ) {
		global $wpdb;

		$search = sanitize_text_field( $params['search'] ?? '' );

		if ( empty( $search ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'search pattern is required.', 'wp-agent' ),
			];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, LENGTH(option_value) AS value_length, autoload
				FROM {$wpdb->options}
				WHERE option_name LIKE %s
				ORDER BY option_name
				LIMIT 100",
				'%' . $wpdb->esc_like( $search ) . '%'
			),
			ARRAY_A
		);

		$options = [];
		foreach ( $results as $row ) {
			if ( $this->is_blocked( $row['option_name'] ) ) {
				continue;
			}
			$options[] = [
				'name'         => $row['option_name'],
				'value_length' => (int) $row['value_length'],
				'autoload'     => $row['autoload'],
			];
		}

		return [
			'success' => true,
			'data'    => [
				'pattern' => $search,
				'count'   => count( $options ),
				'options' => $options,
			],
			'message' => sprintf(
				/* translators: 1: count, 2: pattern */
				__( 'Found %1$d option(s) matching "%2$s".', 'wp-agent' ),
				count( $options ),
				$search
			),
		];
	}
}
