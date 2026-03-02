<?php
/**
 * Manage Memory Action.
 *
 * Stores and retrieves persistent memories for the AI agent. Memories
 * are key-value pairs with categories, stored in wp_options. Useful
 * for remembering user preferences, project context, and decisions.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Memory
 *
 * @since 1.1.0
 */
class Manage_Memory implements Action_Interface {

	/**
	 * Option key for storing memories.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp_agent_memories';

	/**
	 * Maximum number of memories.
	 *
	 * @var int
	 */
	const MAX_MEMORIES = 200;

	/**
	 * Maximum value length in characters.
	 *
	 * @var int
	 */
	const MAX_VALUE_LENGTH = 2000;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_memory';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage persistent agent memories. Operations: "list" shows stored memories (filterable by category), '
			. '"remember" saves or updates a memory (key-value pair with optional category), '
			. '"forget" removes a memory by key, "search" finds memories matching a query. '
			. 'Use to remember user preferences, project decisions, and important context.';
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
					'enum'        => array( 'list', 'remember', 'forget', 'search' ),
					'description' => 'Operation to perform.',
				),
				'key'       => array(
					'type'        => 'string',
					'description' => 'Memory key. Required for "remember" and "forget".',
				),
				'value'     => array(
					'type'        => 'string',
					'description' => 'Memory value (for "remember"). Max 2000 characters.',
				),
				'category'  => array(
					'type'        => 'string',
					'description' => 'Memory category for organization (for "remember" and "list" filter).',
				),
				'query'     => array(
					'type'        => 'string',
					'description' => 'Search query to find matching memories (for "search").',
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
		return 'edit_posts';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return true;
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
				return $this->list_memories( $params );

			case 'remember':
				return $this->remember( $params );

			case 'forget':
				return $this->forget( $params );

			case 'search':
				return $this->search_memories( $params );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "remember", "forget", or "search".', 'wp-agent' ),
				);
		}
	}

	/**
	 * List stored memories.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function list_memories( array $params ) {
		$memories = get_option( self::OPTION_KEY, array() );
		$category = ! empty( $params['category'] ) ? sanitize_key( $params['category'] ) : '';

		if ( $category ) {
			$memories = array_filter(
				$memories,
				function ( $m ) use ( $category ) {
					return ( $m['category'] ?? '' ) === $category;
				}
			);
		}

		// Get unique categories.
		$all_memories = get_option( self::OPTION_KEY, array() );
		$categories   = array_unique( array_filter( array_column( $all_memories, 'category' ) ) );

		$result = array();
		foreach ( $memories as $memory ) {
			$result[] = array(
				'key'        => $memory['key'],
				'value'      => $memory['value'],
				'category'   => $memory['category'] ?? '',
				'created_at' => $memory['created_at'] ?? '',
				'updated_at' => $memory['updated_at'] ?? '',
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'memories'   => $result,
				'total'      => count( $result ),
				'categories' => array_values( $categories ),
			),
			'message' => sprintf(
				/* translators: %d: memory count */
				__( '%d memory(ies) stored.', 'wp-agent' ),
				count( $result )
			),
		);
	}

	/**
	 * Store or update a memory.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function remember( array $params ) {
		$key      = ! empty( $params['key'] ) ? sanitize_key( $params['key'] ) : '';
		$value    = isset( $params['value'] ) ? sanitize_textarea_field( $params['value'] ) : '';
		$category = ! empty( $params['category'] ) ? sanitize_key( $params['category'] ) : 'general';

		if ( empty( $key ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Memory key is required.', 'wp-agent' ),
			);
		}

		if ( empty( $value ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Memory value is required.', 'wp-agent' ),
			);
		}

		if ( strlen( $value ) > self::MAX_VALUE_LENGTH ) {
			$value = substr( $value, 0, self::MAX_VALUE_LENGTH );
		}

		$memories  = get_option( self::OPTION_KEY, array() );
		$is_update = false;

		// Check if key exists (update) or is new.
		$existing_index = null;
		foreach ( $memories as $index => $memory ) {
			if ( $memory['key'] === $key ) {
				$existing_index = $index;
				$is_update      = true;
				break;
			}
		}

		$now = current_time( 'mysql' );

		if ( $is_update ) {
			$memories[ $existing_index ]['value']      = $value;
			$memories[ $existing_index ]['category']   = $category;
			$memories[ $existing_index ]['updated_at'] = $now;
		} else {
			if ( count( $memories ) >= self::MAX_MEMORIES ) {
				return array(
					'success' => false,
					'data'    => null,
					'message' => sprintf(
						/* translators: %d: max memories */
						__( 'Maximum of %d memories reached. Forget some memories first.', 'wp-agent' ),
						self::MAX_MEMORIES
					),
				);
			}

			$memories[] = array(
				'key'        => $key,
				'value'      => $value,
				'category'   => $category,
				'created_at' => $now,
				'updated_at' => $now,
			);
		}

		update_option( self::OPTION_KEY, $memories, false );

		return array(
			'success' => true,
			'data'    => array(
				'key'      => $key,
				'value'    => $value,
				'category' => $category,
				'action'   => $is_update ? 'updated' : 'created',
			),
			'message' => $is_update
				? sprintf(
					/* translators: %s: memory key */
					__( 'Memory "%s" updated.', 'wp-agent' ),
					$key
				)
				: sprintf(
					/* translators: %s: memory key */
					__( 'Memory "%s" saved.', 'wp-agent' ),
					$key
				),
		);
	}

	/**
	 * Remove a memory by key.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function forget( array $params ) {
		$key = ! empty( $params['key'] ) ? sanitize_key( $params['key'] ) : '';

		if ( empty( $key ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Memory key is required.', 'wp-agent' ),
			);
		}

		$memories = get_option( self::OPTION_KEY, array() );
		$found    = false;

		foreach ( $memories as $index => $memory ) {
			if ( $memory['key'] === $key ) {
				unset( $memories[ $index ] );
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: memory key */
					__( 'Memory "%s" not found.', 'wp-agent' ),
					$key
				),
			);
		}

		// Re-index array.
		$memories = array_values( $memories );
		update_option( self::OPTION_KEY, $memories, false );

		return array(
			'success' => true,
			'data'    => array( 'key' => $key ),
			'message' => sprintf(
				/* translators: %s: memory key */
				__( 'Memory "%s" forgotten.', 'wp-agent' ),
				$key
			),
		);
	}

	/**
	 * Search memories by query string.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function search_memories( array $params ) {
		$query = ! empty( $params['query'] ) ? sanitize_text_field( $params['query'] ) : '';

		if ( empty( $query ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Search query is required.', 'wp-agent' ),
			);
		}

		$memories    = get_option( self::OPTION_KEY, array() );
		$query_lower = strtolower( $query );
		$matches     = array();

		foreach ( $memories as $memory ) {
			$key_match   = false !== strpos( strtolower( $memory['key'] ), $query_lower );
			$value_match = false !== strpos( strtolower( $memory['value'] ), $query_lower );
			$cat_match   = false !== strpos( strtolower( $memory['category'] ?? '' ), $query_lower );

			if ( $key_match || $value_match || $cat_match ) {
				$matches[] = array(
					'key'        => $memory['key'],
					'value'      => $memory['value'],
					'category'   => $memory['category'] ?? '',
					'created_at' => $memory['created_at'] ?? '',
					'updated_at' => $memory['updated_at'] ?? '',
				);
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'query'   => $query,
				'matches' => $matches,
				'total'   => count( $matches ),
			),
			'message' => sprintf(
				/* translators: 1: match count, 2: search query */
				__( 'Found %1$d memory(ies) matching "%2$s".', 'wp-agent' ),
				count( $matches ),
				$query
			),
		);
	}
}
