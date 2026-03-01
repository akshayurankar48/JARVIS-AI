<?php
/**
 * Manage Scheduled Tasks Action.
 *
 * Creates, lists, pauses, resumes, and deletes custom scheduled tasks
 * that execute chains of WP Agent actions on a recurring schedule.
 * Uses WordPress cron system for scheduling.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Scheduled_Tasks
 *
 * @since 1.1.0
 */
class Manage_Scheduled_Tasks implements Action_Interface {

	/**
	 * Option key for storing scheduled tasks.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'wp_agent_scheduled_tasks';

	/**
	 * Hook prefix for scheduled events.
	 *
	 * @var string
	 */
	const HOOK_PREFIX = 'wp_agent_execute_scheduled_';

	/**
	 * Maximum tasks allowed.
	 *
	 * @var int
	 */
	const MAX_TASKS = 50;

	/**
	 * Allowed schedule intervals.
	 *
	 * @var string[]
	 */
	const ALLOWED_SCHEDULES = [ 'hourly', 'twicedaily', 'daily', 'weekly' ];

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_scheduled_tasks';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage recurring scheduled tasks that execute action chains. Operations: "create" registers a new scheduled task, '
			. '"list" shows all tasks, "delete" removes a task, "pause" temporarily stops a task, "resume" reactivates a paused task. '
			. 'Each task runs a chain of WP Agent actions on a schedule (hourly, twicedaily, daily, weekly).';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'    => [
					'type'        => 'string',
					'enum'        => [ 'create', 'list', 'delete', 'pause', 'resume' ],
					'description' => 'Operation to perform.',
				],
				'task_id'      => [
					'type'        => 'string',
					'description' => 'Task ID. Required for delete, pause, and resume.',
				],
				'name'         => [
					'type'        => 'string',
					'description' => 'Task name (for "create").',
				],
				'action_chain' => [
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'action' => [ 'type' => 'string', 'description' => 'Action name to execute.' ],
							'params' => [ 'type' => 'object', 'description' => 'Parameters for the action.' ],
						],
						'required' => [ 'action' ],
					],
					'description' => 'Array of actions to execute in order (for "create").',
				],
				'schedule'     => [
					'type'        => 'string',
					'enum'        => [ 'hourly', 'twicedaily', 'daily', 'weekly' ],
					'description' => 'Schedule interval (for "create"). Defaults to "daily".',
				],
			],
			'required'   => [ 'operation' ],
		];
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
				return $this->create_task( $params );

			case 'list':
				return $this->list_tasks();

			case 'delete':
				return $this->delete_task( $params );

			case 'pause':
				return $this->pause_task( $params );

			case 'resume':
				return $this->resume_task( $params );

			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				];
		}
	}

	/**
	 * Create a new scheduled task.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function create_task( array $params ) {
		$name         = ! empty( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$action_chain = isset( $params['action_chain'] ) && is_array( $params['action_chain'] ) ? $params['action_chain'] : [];
		$schedule     = ! empty( $params['schedule'] ) ? sanitize_key( $params['schedule'] ) : 'daily';

		if ( empty( $name ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task name is required.', 'wp-agent' ),
			];
		}

		if ( empty( $action_chain ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'At least one action is required in the action chain.', 'wp-agent' ),
			];
		}

		if ( ! in_array( $schedule, self::ALLOWED_SCHEDULES, true ) ) {
			$schedule = 'daily';
		}

		$tasks = get_option( self::OPTION_KEY, [] );

		if ( count( $tasks ) >= self::MAX_TASKS ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: max tasks */
					__( 'Maximum of %d scheduled tasks reached.', 'wp-agent' ),
					self::MAX_TASKS
				),
			];
		}

		$task_id = 'task_' . wp_generate_password( 8, false );

		// Sanitize the action chain.
		$sanitized_chain = [];
		foreach ( $action_chain as $step ) {
			if ( empty( $step['action'] ) ) {
				continue;
			}
			$sanitized_chain[] = [
				'action' => sanitize_key( $step['action'] ),
				'params' => isset( $step['params'] ) && is_array( $step['params'] ) ? $step['params'] : [],
			];
		}

		if ( empty( $sanitized_chain ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'No valid actions in the action chain.', 'wp-agent' ),
			];
		}

		$current_user = wp_get_current_user();
		$hook_name    = self::HOOK_PREFIX . $task_id;

		$task = [
			'id'           => $task_id,
			'name'         => $name,
			'action_chain' => $sanitized_chain,
			'schedule'     => $schedule,
			'next_run'     => current_time( 'mysql' ),
			'last_run'     => null,
			'status'       => 'active',
			'created_by'   => $current_user->ID,
			'created_at'   => current_time( 'mysql' ),
		];

		$tasks[ $task_id ] = $task;
		update_option( self::OPTION_KEY, $tasks, false );

		// Register the cron event.
		if ( ! wp_next_scheduled( $hook_name ) ) {
			wp_schedule_event( time(), $schedule, $hook_name );
		}

		return [
			'success' => true,
			'data'    => $task,
			'message' => sprintf(
				/* translators: 1: task name, 2: schedule, 3: action count */
				__( 'Scheduled task "%1$s" created (%2$s, %3$d action(s)).', 'wp-agent' ),
				$name,
				$schedule,
				count( $sanitized_chain )
			),
		];
	}

	/**
	 * List all scheduled tasks.
	 *
	 * @since 1.1.0
	 *
	 * @return array Execution result.
	 */
	private function list_tasks() {
		$tasks  = get_option( self::OPTION_KEY, [] );
		$result = [];

		foreach ( $tasks as $task ) {
			$hook_name  = self::HOOK_PREFIX . $task['id'];
			$next_cron  = wp_next_scheduled( $hook_name );

			$result[] = [
				'id'            => $task['id'],
				'name'          => $task['name'],
				'schedule'      => $task['schedule'],
				'action_count'  => count( $task['action_chain'] ),
				'actions'       => array_column( $task['action_chain'], 'action' ),
				'status'        => $task['status'],
				'next_run'      => $next_cron ? gmdate( 'Y-m-d H:i:s', $next_cron ) : null,
				'last_run'      => $task['last_run'],
				'created_at'    => $task['created_at'],
			];
		}

		$active = count( array_filter( $tasks, function ( $t ) {
			return 'active' === $t['status'];
		} ) );

		return [
			'success' => true,
			'data'    => [
				'tasks'  => $result,
				'total'  => count( $result ),
				'active' => $active,
			],
			'message' => sprintf(
				/* translators: 1: total tasks, 2: active tasks */
				__( '%1$d task(s) total, %2$d active.', 'wp-agent' ),
				count( $result ),
				$active
			),
		];
	}

	/**
	 * Delete a scheduled task.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function delete_task( array $params ) {
		$task_id = ! empty( $params['task_id'] ) ? sanitize_key( $params['task_id'] ) : '';

		if ( empty( $task_id ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task ID is required.', 'wp-agent' ),
			];
		}

		$tasks = get_option( self::OPTION_KEY, [] );

		if ( ! isset( $tasks[ $task_id ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task not found.', 'wp-agent' ),
			];
		}

		$name      = $tasks[ $task_id ]['name'];
		$hook_name = self::HOOK_PREFIX . $task_id;

		// Unschedule the cron event.
		$timestamp = wp_next_scheduled( $hook_name );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook_name );
		}

		unset( $tasks[ $task_id ] );
		update_option( self::OPTION_KEY, $tasks, false );

		return [
			'success' => true,
			'data'    => [ 'task_id' => $task_id ],
			'message' => sprintf(
				/* translators: %s: task name */
				__( 'Scheduled task "%s" deleted.', 'wp-agent' ),
				$name
			),
		];
	}

	/**
	 * Pause a scheduled task.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function pause_task( array $params ) {
		$task_id = ! empty( $params['task_id'] ) ? sanitize_key( $params['task_id'] ) : '';

		if ( empty( $task_id ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task ID is required.', 'wp-agent' ),
			];
		}

		$tasks = get_option( self::OPTION_KEY, [] );

		if ( ! isset( $tasks[ $task_id ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task not found.', 'wp-agent' ),
			];
		}

		if ( 'paused' === $tasks[ $task_id ]['status'] ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task is already paused.', 'wp-agent' ),
			];
		}

		// Unschedule the cron event.
		$hook_name = self::HOOK_PREFIX . $task_id;
		$timestamp = wp_next_scheduled( $hook_name );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook_name );
		}

		$tasks[ $task_id ]['status'] = 'paused';
		update_option( self::OPTION_KEY, $tasks, false );

		return [
			'success' => true,
			'data'    => [ 'task_id' => $task_id, 'status' => 'paused' ],
			'message' => sprintf(
				/* translators: %s: task name */
				__( 'Scheduled task "%s" paused.', 'wp-agent' ),
				$tasks[ $task_id ]['name']
			),
		];
	}

	/**
	 * Resume a paused scheduled task.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function resume_task( array $params ) {
		$task_id = ! empty( $params['task_id'] ) ? sanitize_key( $params['task_id'] ) : '';

		if ( empty( $task_id ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task ID is required.', 'wp-agent' ),
			];
		}

		$tasks = get_option( self::OPTION_KEY, [] );

		if ( ! isset( $tasks[ $task_id ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task not found.', 'wp-agent' ),
			];
		}

		if ( 'active' === $tasks[ $task_id ]['status'] ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Task is already active.', 'wp-agent' ),
			];
		}

		$hook_name = self::HOOK_PREFIX . $task_id;
		$schedule  = $tasks[ $task_id ]['schedule'];

		// Re-register the cron event.
		if ( ! wp_next_scheduled( $hook_name ) ) {
			wp_schedule_event( time(), $schedule, $hook_name );
		}

		$tasks[ $task_id ]['status'] = 'active';
		update_option( self::OPTION_KEY, $tasks, false );

		return [
			'success' => true,
			'data'    => [ 'task_id' => $task_id, 'status' => 'active' ],
			'message' => sprintf(
				/* translators: %s: task name */
				__( 'Scheduled task "%s" resumed.', 'wp-agent' ),
				$tasks[ $task_id ]['name']
			),
		];
	}
}
