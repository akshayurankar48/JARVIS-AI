<?php
/**
 * Scheduled Tasks REST Controller.
 *
 * Provides endpoints for the admin Schedules page to list,
 * pause, resume, and delete scheduled tasks.
 *
 * @package JarvisAI\REST
 * @since   1.1.0
 */

namespace JarvisAI\REST;

use JarvisAI\Actions\Manage_Scheduled_Tasks;

defined( 'ABSPATH' ) || exit;

/**
 * Class Schedules_Controller
 *
 * Manages scheduled task operations via REST API.
 *
 * @package WP_Agent
 * @since   1.1.0
 */
class Schedules_Controller extends \WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'jarvis-ai/v1';

	/**
	 * REST route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'schedules';

	/**
	 * Register REST routes for scheduled tasks.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_tasks' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<task_id>[a-zA-Z0-9_]+)/(?P<action>pause|resume|delete)',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_task' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'task_id' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_key',
						),
						'action'  => array(
							'required'          => true,
							'type'              => 'string',
							'enum'              => array( 'pause', 'resume', 'delete' ),
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);
	}

	/**
	 * Check if the current user can manage scheduled tasks.
	 *
	 * @return bool True if user has manage_options capability.
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /jarvis-ai/v1/schedules
	 *
	 * Returns all scheduled tasks with their status and next run time.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_tasks() {
		$tasks  = get_option( Manage_Scheduled_Tasks::OPTION_KEY, array() );
		$result = array();

		foreach ( $tasks as $task ) {
			$hook_name = Manage_Scheduled_Tasks::HOOK_PREFIX . $task['id'];
			$next_cron = wp_next_scheduled( $hook_name );

			$result[] = array(
				'id'           => $task['id'],
				'name'         => $task['name'],
				'schedule'     => $task['schedule'],
				'action_count' => count( $task['action_chain'] ),
				'actions'      => array_column( $task['action_chain'], 'action' ),
				'status'       => $task['status'],
				'next_run'     => $next_cron ? gmdate( 'Y-m-d H:i:s', $next_cron ) : null,
				'last_run'     => $task['last_run'] ?? null,
				'created_at'   => $task['created_at'],
			);
		}

		return rest_ensure_response( $result );
	}

	/**
	 * POST /jarvis-ai/v1/schedules/{task_id}/{action}
	 *
	 * Pause, resume, or delete a scheduled task.
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_task( $request ) {
		$task_id = $request->get_param( 'task_id' );
		$action  = $request->get_param( 'action' );

		$handler = new Manage_Scheduled_Tasks();
		$result  = $handler->execute(
			array(
				'operation' => $action,
				'task_id'   => $task_id,
			)
		);

		if ( ! $result['success'] ) {
			return new \WP_Error(
				'jarvis_ai_schedule_error',
				$result['message'],
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}
}
