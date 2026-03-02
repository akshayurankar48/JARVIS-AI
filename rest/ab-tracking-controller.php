<?php
/**
 * A/B Testing Tracking REST Controller.
 *
 * Public endpoint for recording A/B test impressions and clicks.
 * Rate limited to prevent abuse.
 *
 * @package WPAgent\REST
 * @since   1.1.0
 */

namespace WPAgent\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ab_Tracking_Controller
 *
 * Handles A/B test event tracking via REST API.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Ab_Tracking_Controller extends \WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wp-agent/v1';

	/**
	 * REST route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ab-track';

	/**
	 * Register REST routes for A/B test tracking.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'track_event' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'test_id' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'event'   => array(
							'required'          => true,
							'type'              => 'string',
							'enum'              => array( 'impression', 'click' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'variant' => array(
							'required'          => true,
							'type'              => 'string',
							'enum'              => array( 'a', 'b' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
	}

	/**
	 * POST /wp-agent/v1/ab-track
	 *
	 * Record an A/B test impression or click event.
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response
	 */
	public function track_event( $request ) {
		$test_id = sanitize_key( $request->get_param( 'test_id' ) );
		$event   = $request->get_param( 'event' );
		$variant = $request->get_param( 'variant' );

		// Validate test_id format (alphanumeric + underscores only, max 64 chars).
		if ( empty( $test_id ) || strlen( $test_id ) > 64 ) {
			return new \WP_REST_Response( array( 'success' => false ), 400 );
		}

		// Rate limit: max 30 events per IP per minute.
		$ip            = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$transient_key = 'wp_agent_ab_rate_' . md5( $ip );
		$count         = (int) get_transient( $transient_key );

		if ( $count >= 30 ) {
			return new \WP_REST_Response( array( 'success' => false ), 429 );
		}

		set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );

		// Verify the test exists and is active before writing.
		$tests    = get_option( 'wp_agent_ab_tests', array() );
		$found    = false;

		foreach ( $tests as &$test ) {
			if ( (string) $test['id'] !== $test_id ) {
				continue;
			}
			if ( 'active' !== ( $test['status'] ?? '' ) ) {
				return new \WP_REST_Response( array( 'success' => false ), 404 );
			}

			$found = true;
			$key   = $variant . '_' . $event . 's'; // e.g. a_impressions, b_clicks.
			if ( ! isset( $test[ $key ] ) ) {
				$test[ $key ] = 0;
			}
			++$test[ $key ];
			break;
		}
		unset( $test );

		if ( ! $found ) {
			return new \WP_REST_Response( array( 'success' => false ), 404 );
		}

		update_option( 'wp_agent_ab_tests', $tests );

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}
}
