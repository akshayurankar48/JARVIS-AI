<?php
/**
 * Read Debug Log Action.
 *
 * Reads, tails, or clears the WordPress debug.log file.
 * Uses SplFileObject for memory-efficient tail reading on large logs.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Read_Debug_Log
 *
 * @since 1.0.0
 */
class Read_Debug_Log implements Action_Interface {

	/**
	 * Maximum lines allowed for tail operation.
	 *
	 * @var int
	 */
	const MAX_LINES = 200;

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'read_debug_log';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Read the WordPress debug.log file. Operations: "read" returns the full log content (truncated if large), '
			. '"tail" returns the last N lines (default 50, max 200), '
			. '"clear" empties the log file. '
			. 'Requires WP_DEBUG_LOG to be enabled in wp-config.php.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation' => [
					'type'        => 'string',
					'enum'        => [ 'read', 'tail', 'clear' ],
					'description' => 'Operation to perform.',
				],
				'lines'     => [
					'type'        => 'integer',
					'description' => 'Number of lines for "tail" operation. Default 50, max 200.',
				],
			],
			'required'   => [ 'operation' ],
		];
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'manage_options';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
	}

	/**
	 * Get the debug log file path.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_log_path() {
		// WP_DEBUG_LOG can be a path string or boolean.
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
			return WP_DEBUG_LOG;
		}
		return WP_CONTENT_DIR . '/debug.log';
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';
		$log_path  = $this->get_log_path();

		switch ( $operation ) {
			case 'read':
				return $this->read_log( $log_path );

			case 'tail':
				$lines = isset( $params['lines'] ) ? absint( $params['lines'] ) : 50;
				$lines = min( $lines, self::MAX_LINES );
				$lines = max( $lines, 1 );
				return $this->tail_log( $log_path, $lines );

			case 'clear':
				return $this->clear_log( $log_path );

			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "read", "tail", or "clear".', 'wp-agent' ),
				];
		}
	}

	/**
	 * Read the full debug log.
	 *
	 * @since 1.0.0
	 *
	 * @param string $log_path Path to log file.
	 * @return array Execution result.
	 */
	private function read_log( $log_path ) {
		if ( ! file_exists( $log_path ) ) {
			return [
				'success' => true,
				'data'    => [ 'content' => '', 'size' => 0 ],
				'message' => __( 'Debug log file does not exist. WP_DEBUG_LOG may not be enabled.', 'wp-agent' ),
			];
		}

		$size = filesize( $log_path );

		// If file is larger than 100KB, only return the last portion.
		$max_read = 100 * 1024;
		if ( $size > $max_read ) {
			$content   = file_get_contents( $log_path, false, null, $size - $max_read );
			$truncated = true;
		} else {
			$content   = file_get_contents( $log_path );
			$truncated = false;
		}

		return [
			'success' => true,
			'data'    => [
				'content'   => $content,
				'size'      => $size,
				'truncated' => $truncated,
				'path'      => $log_path,
			],
			'message' => sprintf(
				/* translators: 1: file size */
				$truncated
					? __( 'Debug log (%1$s). Showing last 100KB of content.', 'wp-agent' )
					: __( 'Debug log (%1$s).', 'wp-agent' ),
				size_format( $size )
			),
		];
	}

	/**
	 * Read the last N lines of the debug log efficiently.
	 *
	 * @since 1.0.0
	 *
	 * @param string $log_path Path to log file.
	 * @param int    $lines    Number of lines to return.
	 * @return array Execution result.
	 */
	private function tail_log( $log_path, $lines ) {
		if ( ! file_exists( $log_path ) ) {
			return [
				'success' => true,
				'data'    => [ 'content' => '', 'lines' => 0 ],
				'message' => __( 'Debug log file does not exist. WP_DEBUG_LOG may not be enabled.', 'wp-agent' ),
			];
		}

		try {
			$file    = new \SplFileObject( $log_path, 'r' );
			$file->seek( PHP_INT_MAX );
			$total_lines = $file->key();

			if ( 0 === $total_lines ) {
				return [
					'success' => true,
					'data'    => [ 'content' => '', 'lines' => 0 ],
					'message' => __( 'Debug log is empty.', 'wp-agent' ),
				];
			}

			$start = max( 0, $total_lines - $lines );
			$file->seek( $start );

			$output = [];
			while ( ! $file->eof() ) {
				$line = $file->fgets();
				if ( '' !== trim( $line ) ) {
					$output[] = rtrim( $line );
				}
			}
		} catch ( \Exception $e ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to read debug log: %s', 'wp-agent' ),
					$e->getMessage()
				),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'content'     => implode( "\n", $output ),
				'lines'       => count( $output ),
				'total_lines' => $total_lines,
			],
			'message' => sprintf(
				/* translators: 1: returned lines, 2: total lines */
				__( 'Showing last %1$d lines of debug log (%2$d total).', 'wp-agent' ),
				count( $output ),
				$total_lines
			),
		];
	}

	/**
	 * Clear the debug log file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $log_path Path to log file.
	 * @return array Execution result.
	 */
	private function clear_log( $log_path ) {
		if ( ! file_exists( $log_path ) ) {
			return [
				'success' => true,
				'data'    => null,
				'message' => __( 'Debug log does not exist. Nothing to clear.', 'wp-agent' ),
			];
		}

		$old_size = filesize( $log_path );
		$result   = file_put_contents( $log_path, '' );

		if ( false === $result ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to clear debug log. Check filesystem permissions.', 'wp-agent' ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'previous_size' => $old_size,
				'path'          => $log_path,
			],
			'message' => sprintf(
				/* translators: %s: previous file size */
				__( 'Debug log cleared (%s freed).', 'wp-agent' ),
				size_format( $old_size )
			),
		];
	}
}
