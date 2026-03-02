<?php
/**
 * Chat REST Controller.
 *
 * Non-streaming chat endpoint. Receives a message, calls the orchestrator,
 * and returns the full response synchronously.
 *
 * @package JarvisAI\REST
 * @since   1.0.0
 */

namespace JarvisAI\REST;

use JarvisAI\AI\Orchestrator;

defined( 'ABSPATH' ) || exit;

/**
 * Class Chat_Controller
 *
 * @since 1.0.0
 */
class Chat_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'jarvis-ai/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	const ROUTE = '/chat';

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_chat' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_args(),
			)
		);
	}

	/**
	 * Permission check — edit_posts required.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return bool|\WP_Error
	 */
	public function check_permissions( $request ) {
		if ( ! current_user_can( 'edit_posts' ) || ! REST_Permissions::current_user_has_allowed_role() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to use the chat.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * POST /jarvis-ai/v1/chat
	 *
	 * Sends a message to the AI orchestrator and returns the response.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function handle_chat( $request ) {
		$message         = $request->get_param( 'message' );
		$conversation_id = $request->get_param( 'conversation_id' );
		$user_id         = get_current_user_id();

		$options = array();

		if ( $request->has_param( 'post_id' ) ) {
			$options['post_id'] = (int) $request->get_param( 'post_id' );
		}

		if ( $request->has_param( 'model' ) ) {
			$options['model'] = sanitize_text_field( $request->get_param( 'model' ) );
		}

		$result = Orchestrator::get_instance()->handle(
			$message,
			$user_id,
			$conversation_id ? (int) $conversation_id : null,
			$options
		);

		if ( is_wp_error( $result ) ) {
			$status = $result->get_error_data() && isset( $result->get_error_data()['status'] )
				? $result->get_error_data()['status']
				: 500;

			return new \WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => $status )
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Define argument schema for POST /chat.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_args() {
		return array(
			'message'         => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'validate_callback' => function ( $value ) {
					if ( empty( trim( $value ) ) ) {
						return new \WP_Error(
							'empty_message',
							__( 'Message cannot be empty.', 'jarvis-ai' ),
							array( 'status' => 400 )
						);
					}
					return true;
				},
			),
			'conversation_id' => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'post_id'         => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'model'           => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
