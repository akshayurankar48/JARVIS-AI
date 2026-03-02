<?php
/**
 * Scheduled Tasks REST Controller.
 *
 * Provides endpoints for the admin Schedules page to list,
 * pause, resume, and delete scheduled tasks.
 *
 * @package WPAgent\REST
 * @since   1.1.0
 */

namespace WPAgent\REST;

use WPAgent\Actions\Manage_Scheduled_Tasks;

defined( 'ABSPATH' ) || exit;

class Schedules_Controller extends \WP_REST_Controller {

	protected $namespace = 'wp-agent/v1';
	protected $rest_base = 'schedules';

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

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

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
				'wp_agent_schedule_error',
				$result['message'],
				array( 'status' => 400 )
			);
		}

		return rest_ensure_response( $result );
	}
}
