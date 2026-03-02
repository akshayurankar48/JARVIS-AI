<?php
/**
 * Manage Cron Action.
 *
 * Lists, adds, deletes, and immediately runs WP-Cron scheduled events.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

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
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation' => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'add', 'delete', 'run_now' ),
					'description' => 'Operation to perform.',
				),
				'hook'      => array(
					'type'        => 'string',
					'description' => 'Cron hook name. Required for add, delete, run_now.',
				),
				'schedule'  => array(
					'type'        => 'string',
					'enum'        => array( 'hourly', 'twicedaily', 'daily', 'weekly' ),
					'description' => 'Recurrence schedule. Required for add.',
				),
				'args'      => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Arguments to pass to the cron hook callback.',
				),
				'timestamp' => array(
					'type'        => 'integer',
					'description' => 'Unix timestamp for the event. Used in delete to identify specific event.',
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
			case 'list':
				return $this->list_cron_events();
			case 'add':
				return $this->add_cron_event( $params );
			case 'delete':
				return $this->delete_cron_event( $params );
			case 'run_now':
				return $this->run_cron_event( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "add", "delete", or "run_now".', 'jarvis-ai' ),
				);
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
			return array(
				'success' => true,
				'data'    => array(
					'events' => array(),
					'count'  => 0,
				),
				'message' => __( 'No cron events scheduled.', 'jarvis-ai' ),
			);
		}

		$events = array();
		$now    = time();

		foreach ( $cron_array as $timestamp => $hooks ) {
			foreach ( $hooks as $hook => $event_list ) {
				foreach ( $event_list as $key => $event ) {
					$events[] = array(
						'hook'      => $hook,
						'timestamp' => $timestamp,
						'next_run'  => gmdate( 'Y-m-d H:i:s', $timestamp ),
						'schedule'  => $event['schedule'] ?: 'one-time',
						'interval'  => isset( $event['interval'] ) ? $event['interval'] : null,
						'overdue'   => $timestamp < $now,
						'args'      => $event['args'],
					);
				}
			}
		}

		// Sort by timestamp.
		usort(
			$events,
			function ( $a, $b ) {
				return $a['timestamp'] - $b['timestamp'];
			}
		);

		return array(
			'success' => true,
			'data'    => array(
				'count'  => count( $events ),
				'events' => array_slice( $events, 0, 100 ),
			),
			'message' => sprintf(
				/* translators: %d: event count */
				__( '%d cron event(s) scheduled.', 'jarvis-ai' ),
				count( $events )
			),
		);
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
		$args     = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : array();

		if ( empty( $hook ) || empty( $schedule ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'hook and schedule are required.', 'jarvis-ai' ),
			);
		}

		$schedules = wp_get_schedules();
		if ( ! isset( $schedules[ $schedule ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: 1: schedule name, 2: available schedules */
					__( 'Invalid schedule "%1$s". Available: %2$s.', 'jarvis-ai' ),
					$schedule,
					implode( ', ', array_keys( $schedules ) )
				),
			);
		}

		$result = wp_schedule_event( time(), $schedule, $hook, $args );

		if ( false === $result || is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to schedule cron event.', 'jarvis-ai' ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'hook'     => $hook,
				'schedule' => $schedule,
			),
			'message' => sprintf(
				/* translators: 1: hook name, 2: schedule */
				__( 'Cron event "%1$s" scheduled (%2$s).', 'jarvis-ai' ),
				$hook,
				$schedule
			),
		);
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
		$args      = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : array();

		if ( empty( $hook ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'hook is required.', 'jarvis-ai' ),
			);
		}

		if ( $timestamp ) {
			$result = wp_unschedule_event( $timestamp, $hook, $args );
		} else {
			$result = wp_clear_scheduled_hook( $hook, $args );
		}

		if ( false === $result || is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: hook name */
					__( 'Failed to delete cron event "%s".', 'jarvis-ai' ),
					$hook
				),
			);
		}

		return array(
			'success' => true,
			'data'    => array( 'hook' => $hook ),
			'message' => sprintf(
				/* translators: %s: hook name */
				__( 'Cron event "%s" deleted.', 'jarvis-ai' ),
				$hook
			),
		);
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
		$args = isset( $params['args'] ) && is_array( $params['args'] ) ? $params['args'] : array();

		if ( empty( $hook ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'hook is required.', 'jarvis-ai' ),
			);
		}

		// Fire the hook directly.
		do_action_ref_array( $hook, $args );

		return array(
			'success' => true,
			'data'    => array( 'hook' => $hook ),
			'message' => sprintf(
				/* translators: %s: hook name */
				__( 'Cron hook "%s" executed immediately.', 'jarvis-ai' ),
				$hook
			),
		);
	}
}
