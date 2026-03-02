<?php
/**
 * Manage Redirects Action.
 *
 * Adds, lists, deletes, and tests URL redirects stored in wp_options.
 * Hooks into template_redirect to perform actual 301/302 redirects.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Redirects
 *
 * @since 1.1.0
 */
class Manage_Redirects implements Action_Interface {

	/**
	 * Option key for storing redirects.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp_agent_redirects';

	/**
	 * Maximum number of redirects.
	 *
	 * @var int
	 */
	const MAX_REDIRECTS = 500;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_redirects';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage URL redirects. Add, list, delete, or test redirects. '
			. 'Supports 301 (permanent) and 302 (temporary) redirects. '
			. 'Redirects are stored in the database and processed on each request.';
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
				'operation'   => array(
					'type'        => 'string',
					'enum'        => array( 'add', 'list', 'delete', 'test' ),
					'description' => 'Operation: "add" a redirect, "list" all, "delete" by source path, "test" a URL.',
				),
				'source'      => array(
					'type'        => 'string',
					'description' => 'Source URL path (e.g., "/old-page"). Required for add, delete, test.',
				),
				'target'      => array(
					'type'        => 'string',
					'description' => 'Target URL (e.g., "/new-page" or full URL). Required for add.',
				),
				'status_code' => array(
					'type'        => 'integer',
					'enum'        => array( 301, 302 ),
					'description' => 'HTTP status code. 301 = permanent, 302 = temporary. Defaults to 301.',
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
			case 'add':
				return $this->add_redirect( $params );
			case 'list':
				return $this->list_redirects();
			case 'delete':
				return $this->delete_redirect( $params );
			case 'test':
				return $this->test_redirect( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "add", "list", "delete", or "test".', 'wp-agent' ),
				);
		}
	}

	/**
	 * Add a redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function add_redirect( array $params ) {
		$source      = isset( $params['source'] ) ? sanitize_text_field( wp_unslash( $params['source'] ) ) : '';
		$target      = isset( $params['target'] ) ? esc_url_raw( $params['target'] ) : '';
		$status_code = isset( $params['status_code'] ) ? absint( $params['status_code'] ) : 301;

		if ( empty( $source ) || empty( $target ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Both source and target are required.', 'wp-agent' ),
			);
		}

		if ( ! in_array( $status_code, array( 301, 302 ), true ) ) {
			$status_code = 301;
		}

		// Normalize source to path only.
		$source = wp_parse_url( $source, PHP_URL_PATH );
		if ( empty( $source ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid source URL path.', 'wp-agent' ),
			);
		}

		$redirects = get_option( self::OPTION_KEY, array() );

		if ( count( $redirects ) >= self::MAX_REDIRECTS ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: max redirects */
					__( 'Maximum of %d redirects reached.', 'wp-agent' ),
					self::MAX_REDIRECTS
				),
			);
		}

		$redirects[ $source ] = array(
			'target'      => $target,
			'status_code' => $status_code,
			'created_at'  => current_time( 'mysql', true ),
		);

		update_option( self::OPTION_KEY, $redirects );

		return array(
			'success' => true,
			'data'    => array(
				'source'      => $source,
				'target'      => $target,
				'status_code' => $status_code,
			),
			'message' => sprintf(
				/* translators: 1: source, 2: target, 3: status code */
				__( 'Redirect added: %1$s -> %2$s (%3$d).', 'wp-agent' ),
				$source,
				$target,
				$status_code
			),
		);
	}

	/**
	 * List all redirects.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function list_redirects() {
		$redirects = get_option( self::OPTION_KEY, array() );

		$list = array();
		foreach ( $redirects as $source => $data ) {
			$list[] = array(
				'source'      => $source,
				'target'      => $data['target'],
				'status_code' => $data['status_code'],
				'created_at'  => $data['created_at'] ?? '',
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'count'     => count( $list ),
				'redirects' => $list,
			),
			'message' => sprintf(
				/* translators: %d: redirect count */
				__( '%d redirect(s) configured.', 'wp-agent' ),
				count( $list )
			),
		);
	}

	/**
	 * Delete a redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function delete_redirect( array $params ) {
		$source = isset( $params['source'] ) ? sanitize_text_field( wp_unslash( $params['source'] ) ) : '';

		if ( empty( $source ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Source path is required for delete.', 'wp-agent' ),
			);
		}

		$source    = wp_parse_url( $source, PHP_URL_PATH );
		$redirects = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $redirects[ $source ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: source path */
					__( 'No redirect found for "%s".', 'wp-agent' ),
					$source
				),
			);
		}

		unset( $redirects[ $source ] );
		update_option( self::OPTION_KEY, $redirects );

		return array(
			'success' => true,
			'data'    => array( 'deleted_source' => $source ),
			'message' => sprintf(
				/* translators: %s: source path */
				__( 'Redirect for "%s" deleted.', 'wp-agent' ),
				$source
			),
		);
	}

	/**
	 * Test if a URL matches a redirect.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function test_redirect( array $params ) {
		$source = isset( $params['source'] ) ? sanitize_text_field( wp_unslash( $params['source'] ) ) : '';

		if ( empty( $source ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Source path is required for test.', 'wp-agent' ),
			);
		}

		$source    = wp_parse_url( $source, PHP_URL_PATH );
		$redirects = get_option( self::OPTION_KEY, array() );

		if ( isset( $redirects[ $source ] ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'match'       => true,
					'source'      => $source,
					'target'      => $redirects[ $source ]['target'],
					'status_code' => $redirects[ $source ]['status_code'],
				),
				'message' => sprintf(
					/* translators: 1: source, 2: target, 3: status code */
					__( 'Match found: %1$s -> %2$s (%3$d).', 'wp-agent' ),
					$source,
					$redirects[ $source ]['target'],
					$redirects[ $source ]['status_code']
				),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'match'  => false,
				'source' => $source,
			),
			'message' => sprintf(
				/* translators: %s: source path */
				__( 'No redirect matches "%s".', 'wp-agent' ),
				$source
			),
		);
	}

	/**
	 * Process redirects on template_redirect.
	 *
	 * Called via add_action('template_redirect', ...) in plugin-loader.php.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function process_redirects() {
		if ( is_admin() ) {
			return;
		}

		$redirects = get_option( self::OPTION_KEY, array() );

		if ( empty( $redirects ) ) {
			return;
		}

		$current_path = isset( $_SERVER['REQUEST_URI'] )
			? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
			: '';

		if ( empty( $current_path ) ) {
			return;
		}

		if ( isset( $redirects[ $current_path ] ) ) {
			$target      = $redirects[ $current_path ]['target'];
			$status_code = (int) $redirects[ $current_path ]['status_code'];

			wp_safe_redirect( $target, $status_code );
			exit;
		}
	}
}
