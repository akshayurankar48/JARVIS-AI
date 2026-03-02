<?php
/**
 * Manage A/B Test Action.
 *
 * Creates and manages A/B tests for post content. Stores test
 * variants, tracks impressions and clicks, and declares winners
 * based on performance data.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Ab_Test
 *
 * @since 1.1.0
 */
class Manage_Ab_Test implements Action_Interface {

	/**
	 * Option key for storing A/B tests.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp_agent_ab_tests';

	/**
	 * Maximum number of active tests.
	 *
	 * @var int
	 */
	const MAX_TESTS = 50;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_ab_test';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage A/B tests for post content. Operations: "create" sets up a new test with variants, '
			. '"list" shows all tests, "results" shows performance data for a test, "end_test" stops a running test, '
			. '"declare_winner" applies the winning variant to the post.';
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
					'enum'        => array( 'create', 'list', 'results', 'end_test', 'declare_winner' ),
					'description' => 'Operation to perform.',
				),
				'test_id'   => array(
					'type'        => 'string',
					'description' => 'Test ID. Required for results, end_test, and declare_winner.',
				),
				'name'      => array(
					'type'        => 'string',
					'description' => 'Test name (for "create" operation).',
				),
				'post_id'   => array(
					'type'        => 'integer',
					'description' => 'Post ID to test (for "create" operation).',
				),
				'variants'  => array(
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'           => array(
								'type'        => 'string',
								'description' => 'Variant identifier (e.g. "a", "b").',
							),
							'content_hash' => array(
								'type'        => 'string',
								'description' => 'Hash or label for this variant\'s content.',
							),
						),
					),
					'description' => 'Array of variants for "create" (min 2).',
				),
				'winner_id' => array(
					'type'        => 'string',
					'description' => 'Winning variant ID (for "declare_winner").',
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
			case 'create':
				return $this->create_test( $params );

			case 'list':
				return $this->list_tests();

			case 'results':
				return $this->get_results( $params );

			case 'end_test':
				return $this->end_test( $params );

			case 'declare_winner':
				return $this->declare_winner( $params );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				);
		}
	}

	/**
	 * Create a new A/B test.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function create_test( array $params ) {
		$name    = ! empty( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( empty( $name ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test name is required.', 'wp-agent' ),
			);
		}

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'A valid post ID is required.', 'wp-agent' ),
			);
		}

		$variants = isset( $params['variants'] ) && is_array( $params['variants'] ) ? $params['variants'] : array();
		if ( count( $variants ) < 2 ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'At least 2 variants are required for an A/B test.', 'wp-agent' ),
			);
		}

		$tests = get_option( self::OPTION_KEY, array() );

		if ( count( $tests ) >= self::MAX_TESTS ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: maximum tests */
					__( 'Maximum of %d tests reached. End existing tests first.', 'wp-agent' ),
					self::MAX_TESTS
				),
			);
		}

		$test_id = 'test_' . wp_generate_password( 8, false );

		$sanitized_variants = array();
		foreach ( $variants as $variant ) {
			$sanitized_variants[] = array(
				'id'           => sanitize_key( $variant['id'] ?? wp_generate_password( 4, false ) ),
				'content_hash' => sanitize_text_field( $variant['content_hash'] ?? '' ),
				'impressions'  => 0,
				'clicks'       => 0,
			);
		}

		$test = array(
			'id'         => $test_id,
			'name'       => $name,
			'post_id'    => $post_id,
			'variants'   => $sanitized_variants,
			'status'     => 'active',
			'created_at' => current_time( 'mysql' ),
			'ended_at'   => null,
			'winner'     => null,
		);

		$tests[ $test_id ] = $test;
		update_option( self::OPTION_KEY, $tests, false );

		return array(
			'success' => true,
			'data'    => $test,
			'message' => sprintf(
				/* translators: 1: test name, 2: variant count */
				__( 'A/B test "%1$s" created with %2$d variants.', 'wp-agent' ),
				$name,
				count( $sanitized_variants )
			),
		);
	}

	/**
	 * List all A/B tests.
	 *
	 * @since 1.1.0
	 *
	 * @return array Execution result.
	 */
	private function list_tests() {
		$tests  = get_option( self::OPTION_KEY, array() );
		$result = array();

		foreach ( $tests as $test ) {
			$result[] = array(
				'id'         => $test['id'],
				'name'       => $test['name'],
				'post_id'    => $test['post_id'],
				'post_title' => get_the_title( $test['post_id'] ),
				'variants'   => count( $test['variants'] ),
				'status'     => $test['status'],
				'created_at' => $test['created_at'],
				'winner'     => $test['winner'],
			);
		}

		$active = count(
			array_filter(
				$tests,
				function ( $t ) {
					return 'active' === $t['status'];
				}
			)
		);

		return array(
			'success' => true,
			'data'    => array(
				'tests'  => $result,
				'total'  => count( $result ),
				'active' => $active,
			),
			'message' => sprintf(
				/* translators: 1: total tests, 2: active tests */
				__( '%1$d test(s) total, %2$d active.', 'wp-agent' ),
				count( $result ),
				$active
			),
		);
	}

	/**
	 * Get results for a specific test.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function get_results( array $params ) {
		$test_id = ! empty( $params['test_id'] ) ? sanitize_key( $params['test_id'] ) : '';

		if ( empty( $test_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test ID is required.', 'wp-agent' ),
			);
		}

		$tests = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $tests[ $test_id ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test not found.', 'wp-agent' ),
			);
		}

		$test    = $tests[ $test_id ];
		$results = array();

		foreach ( $test['variants'] as $variant ) {
			$ctr = $variant['impressions'] > 0
				? round( ( $variant['clicks'] / $variant['impressions'] ) * 100, 2 )
				: 0;

			$results[] = array(
				'id'          => $variant['id'],
				'impressions' => $variant['impressions'],
				'clicks'      => $variant['clicks'],
				'ctr'         => $ctr,
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'test_id'  => $test_id,
				'name'     => $test['name'],
				'status'   => $test['status'],
				'variants' => $results,
				'winner'   => $test['winner'],
			),
			'message' => sprintf(
				/* translators: %s: test name */
				__( 'Results for A/B test "%s".', 'wp-agent' ),
				$test['name']
			),
		);
	}

	/**
	 * End a running test.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function end_test( array $params ) {
		$test_id = ! empty( $params['test_id'] ) ? sanitize_key( $params['test_id'] ) : '';

		if ( empty( $test_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test ID is required.', 'wp-agent' ),
			);
		}

		$tests = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $tests[ $test_id ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test not found.', 'wp-agent' ),
			);
		}

		if ( 'ended' === $tests[ $test_id ]['status'] ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test has already ended.', 'wp-agent' ),
			);
		}

		$tests[ $test_id ]['status']   = 'ended';
		$tests[ $test_id ]['ended_at'] = current_time( 'mysql' );
		update_option( self::OPTION_KEY, $tests, false );

		return array(
			'success' => true,
			'data'    => array(
				'test_id' => $test_id,
				'status'  => 'ended',
			),
			'message' => sprintf(
				/* translators: %s: test name */
				__( 'A/B test "%s" ended.', 'wp-agent' ),
				$tests[ $test_id ]['name']
			),
		);
	}

	/**
	 * Declare a winner for a test.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function declare_winner( array $params ) {
		$test_id   = ! empty( $params['test_id'] ) ? sanitize_key( $params['test_id'] ) : '';
		$winner_id = ! empty( $params['winner_id'] ) ? sanitize_key( $params['winner_id'] ) : '';

		if ( empty( $test_id ) || empty( $winner_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test ID and winner ID are required.', 'wp-agent' ),
			);
		}

		$tests = get_option( self::OPTION_KEY, array() );

		if ( ! isset( $tests[ $test_id ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Test not found.', 'wp-agent' ),
			);
		}

		// Verify winner_id is a valid variant.
		$valid_variant = false;
		foreach ( $tests[ $test_id ]['variants'] as $variant ) {
			if ( $variant['id'] === $winner_id ) {
				$valid_variant = true;
				break;
			}
		}

		if ( ! $valid_variant ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Winner ID does not match any variant in this test.', 'wp-agent' ),
			);
		}

		$tests[ $test_id ]['status']   = 'ended';
		$tests[ $test_id ]['winner']   = $winner_id;
		$tests[ $test_id ]['ended_at'] = current_time( 'mysql' );
		update_option( self::OPTION_KEY, $tests, false );

		return array(
			'success' => true,
			'data'    => array(
				'test_id'   => $test_id,
				'winner_id' => $winner_id,
			),
			'message' => sprintf(
				/* translators: 1: winner ID, 2: test name */
				__( 'Variant "%1$s" declared winner of test "%2$s".', 'wp-agent' ),
				$winner_id,
				$tests[ $test_id ]['name']
			),
		);
	}
}
