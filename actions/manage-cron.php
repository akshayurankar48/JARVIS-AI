<?php
/**
 * Manage Cron Action.
 *
 * Lists, adds, deletes, and immediately runs WP-Cron scheduled events.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Cron
 *
 * @since 1.1.0
 */
class Manage_Cron implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_cron';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage WordPress cron (scheduled events). List all cron jobs, '
			. 'add a new scheduled event, delete a cron job, or run one immediately.';
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
				'operation' => [
					'type'        => 'string',
					'enum'        => [ 'list', 'add', 'delete', 'run_now' ],
					'description' => 'Operation to perform.',
				],
				'hook'      => [
					'type'        => 'string',
					'description' => 'Cron hook name. Required for add, delete, run_now.',
				],
				'schedule'  => [
					'type'        => 'string',
					'enum'        => [ 'hourly', 'twicedaily', 'daily', 'weekly' ],
					'description' => 'Recurrence schedule. Required for add.',
				],
				'args'      => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => 'Arguments to pass to the cron hook callback.',
				],
				'timestamp' => [
					'type'        => 'integer',
					'description' => 'Unix timestamp for the event. Used in delete to identify specific event.',
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
			case 'list':
				return $this->list_cron_events();
			case 'add':
				return $this->add_cron_event( $params );
			case 'delete':
				return $this->delete_cron_event( $params );
			case 'run_now':
				return $this->run_cron_event( $params );
			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "add", "delete", or "run_now".', 'wp-agent' ),
				];
		}
	}

	/**
	 * List all cron events.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function list_cron_events() {
		$cron_array = _get_cron_array();

		if ( empty( $cron_array ) ) {
			return [
				'success' => true,
				'data'    => [ 'events' => [], 'count' => 0 ],
				'message' => __( 'No cron events scheduled.', 'wp-agent' ),
			];
		}

		$events = [];
		$now    = time();

		foreach ( $cron_array as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $event_list ) {
				foreach ( $event_list as $key => $event ) {
					$events[] = [
						'hook'      => $hook,
						'timestamp' => $timestamp,
						'next_run'  => gmdate( 'Y-m-d H:i:s', $timestamp ),
						'schedule'  => $event['schedule'] ?: 'one-time',
						'interval'  => isset( $event['interval'] ) ? $event['interval'] : null,
						'overdue'   => $timestamp < $now,
						'args'      => $event['args'],
					];
				}
			}
		}

		// Sort by timestamp.
		usort( $events, function ( $a, $b ) {
			return $a['timestamp'] - $b['timestamp'];
		} );

		return [
			'success' => true,
			'data'    => [
				'count'  => count( $events ),
				'events' => array_slice( $events, 0, 100 ),
			],
			'message' => sprintf(
				/* translators: %d: event count */
				__( '%d cron event(s) scheduled.', 'wp-agent' ),
				count( $events )
			),
		];
	}

	/**
	 * Add a cron event.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function add_cron_event( array $params ) {
		$hook     = sanitize_text_field( $params['hook'] ?? '' );
		$schedule = sanitize_text_field( $params['schedule'] ?? '' );
		$args     = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : [];

		if ( empty( $hook ) || empty( $schedule ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'hook and schedule are required.', 'wp-agent' ),
			];
		}

		$schedules = wp_get_schedules();
		if ( ! isset( $schedules[ $schedule ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: 1: schedule name, 2: available schedules */
					__( 'Invalid schedule "%1$s". Available: %2$s.', 'wp-agent' ),
					$schedule,
					implode( ', ', array_keys( $schedules ) )
				),
			];
		}

		$result = wp_schedule_event( time(), $schedule, $hook, $args );

		if ( false === $result || is_wp_error( $result ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to schedule cron event.', 'wp-agent' ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'hook'     => $hook,
				'schedule' => $schedule,
			],
			'message' => sprintf(
				/* translators: 1: hook name, 2: schedule */
				__( 'Cron event "%1$s" scheduled (%2$s).', 'wp-agent' ),
				$hook,
				$schedule
			),
		];
	}

	/**
	 * Delete a cron event.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function delete_cron_event( array $params ) {
		$hook      = sanitize_text_field( $params['hook'] ?? '' );
		$timestamp = isset( $params['timestamp'] ) ? absint( $params['timestamp'] ) : 0;
		$args      = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : [];

		if ( empty( $hook ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'hook is required.', 'wp-agent' ),
			];
		}

		if ( $timestamp ) {
			$result = wp_unschedule_event( $timestamp, $hook, $args );
		} else {
			$result = wp_clear_scheduled_hook( $hook, $args );
		}

		if ( false === $result || is_wp_error( $result ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: hook name */
					__( 'Failed to delete cron event "%s".', 'wp-agent' ),
					$hook
				),
			];
		}

		return [
			'success' => true,
			'data'    => [ 'hook' => $hook ],
			'message' => sprintf(
				/* translators: %s: hook name */
				__( 'Cron event "%s" deleted.', 'wp-agent' ),
				$hook
			),
		];
	}

	/**
	 * Run a cron event immediately.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function run_cron_event( array $params ) {
		$hook = sanitize_text_field( $params['hook'] ?? '' );
		$args = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : [];

		if ( empty( $hook ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'hook is required.', 'wp-agent' ),
			];
		}

		// Fire the hook directly.
		do_action_ref_array( $hook, $args );

		return [
			'success' => true,
			'data'    => [ 'hook' => $hook ],
			'message' => sprintf(
				/* translators: %s: hook name */
				__( 'Cron hook "%s" executed immediately.', 'wp-agent' ),
				$hook
			),
		];
	}
}
