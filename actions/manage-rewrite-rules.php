<?php
/**
 * Manage Rewrite Rules Action.
 *
 * Lists, flushes, and tests WordPress rewrite rules. Useful for
 * debugging permalink issues and verifying URL routing.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Rewrite_Rules
 *
 * @since 1.1.0
 */
class Manage_Rewrite_Rules implements Action_Interface {

	/**
	 * Maximum rules to return in list.
	 *
	 * @var int
	 */
	const MAX_RULES = 50;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_rewrite_rules';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage WordPress rewrite rules. Operations: "list" shows current rules (max 50), '
			. '"flush" regenerates all rewrite rules, "test_url" checks what a URL resolves to. '
			. 'Use for debugging permalink issues and URL routing problems.';
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
					'enum'        => array( 'list', 'flush', 'test_url' ),
					'description' => 'Operation to perform.',
				),
				'url'       => array(
					'type'        => 'string',
					'description' => 'URL to test resolution (for "test_url" operation).',
				),
				'search'    => array(
					'type'        => 'string',
					'description' => 'Filter rules containing this string (for "list").',
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
				return $this->list_rules( $params );

			case 'flush':
				return $this->flush_rules();

			case 'test_url':
				return $this->test_url( $params );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "flush", or "test_url".', 'wp-agent' ),
				);
		}
	}

	/**
	 * List current rewrite rules.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function list_rules( array $params ) {
		global $wp_rewrite;

		$rules  = $wp_rewrite->wp_rewrite_rules();
		$search = ! empty( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'rules'     => array(),
					'total'     => 0,
					'structure' => get_option( 'permalink_structure', '' ),
				),
				'message' => __( 'No rewrite rules found. Permalinks may be set to "Plain".', 'wp-agent' ),
			);
		}

		$total     = count( $rules );
		$formatted = array();
		$count     = 0;

		foreach ( $rules as $pattern => $rewrite ) {
			if ( $count >= self::MAX_RULES ) {
				break;
			}

			if ( $search && false === strpos( $pattern, $search ) && false === strpos( $rewrite, $search ) ) {
				continue;
			}

			$formatted[] = array(
				'pattern' => $pattern,
				'rewrite' => $rewrite,
			);
			++$count;
		}

		return array(
			'success' => true,
			'data'    => array(
				'rules'     => $formatted,
				'shown'     => count( $formatted ),
				'total'     => $total,
				'structure' => get_option( 'permalink_structure', '' ),
			),
			'message' => sprintf(
				/* translators: 1: shown count, 2: total count */
				__( 'Showing %1$d of %2$d rewrite rules.', 'wp-agent' ),
				count( $formatted ),
				$total
			),
		);
	}

	/**
	 * Flush and regenerate rewrite rules.
	 *
	 * @since 1.1.0
	 *
	 * @return array Execution result.
	 */
	private function flush_rules() {
		flush_rewrite_rules();

		global $wp_rewrite;
		$rules = $wp_rewrite->wp_rewrite_rules();
		$count = is_array( $rules ) ? count( $rules ) : 0;

		return array(
			'success' => true,
			'data'    => array(
				'rule_count' => $count,
				'structure'  => get_option( 'permalink_structure', '' ),
			),
			'message' => sprintf(
				/* translators: %d: rule count */
				__( 'Rewrite rules flushed. %d rule(s) regenerated.', 'wp-agent' ),
				$count
			),
		);
	}

	/**
	 * Test URL resolution.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function test_url( array $params ) {
		$url = ! empty( $params['url'] ) ? esc_url_raw( trim( $params['url'] ) ) : '';

		if ( empty( $url ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'URL is required for testing.', 'wp-agent' ),
			);
		}

		$post_id = url_to_postid( $url );

		$result = array(
			'url'     => $url,
			'post_id' => $post_id,
		);

		if ( $post_id ) {
			$post               = get_post( $post_id );
			$result['title']    = $post ? $post->post_title : '';
			$result['type']     = $post ? $post->post_type : '';
			$result['status']   = $post ? $post->post_status : '';
			$result['resolves'] = true;
		} else {
			$result['resolves'] = false;
		}

		return array(
			'success' => true,
			'data'    => $result,
			'message' => $post_id
				? sprintf(
					/* translators: 1: URL, 2: post title, 3: post ID */
					__( 'URL "%1$s" resolves to "%2$s" (ID: %3$d).', 'wp-agent' ),
					$url,
					$result['title'],
					$post_id
				)
				: sprintf(
					/* translators: %s: URL */
					__( 'URL "%s" does not resolve to any post or page.', 'wp-agent' ),
					$url
				),
		);
	}
}
