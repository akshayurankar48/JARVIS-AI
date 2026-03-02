<?php
/**
 * Manage Sessions Action.
 *
 * Lists and destroys user login sessions via the WP_Session_Tokens API.
 * Useful for security audits and forcing re-authentication.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Sessions
 *
 * @since 1.0.0
 */
class Manage_Sessions implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_sessions';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage user login sessions. Operations: "list" shows all active sessions for a user, '
			. '"destroy_all" logs out all sessions for a user, '
			. '"destroy_others" logs out all sessions except the current one. '
			. 'Defaults to the current user if user_id is not provided.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation' => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'destroy_all', 'destroy_others' ),
					'description' => 'Operation to perform.',
				),
				'user_id'   => array(
					'type'        => 'integer',
					'description' => 'User ID to manage sessions for. Defaults to the current user.',
				),
			),
			'required'   => array( 'operation' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_users';
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
	 * Execute the action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';
		$user_id   = absint( $params['user_id'] ?? get_current_user_id() );

		if ( ! $user_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid user ID.', 'wp-agent' ),
			);
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: user ID */
					__( 'User #%d not found.', 'wp-agent' ),
					$user_id
				),
			);
		}

		$manager = \WP_Session_Tokens::get_instance( $user_id );

		switch ( $operation ) {
			case 'list':
				return $this->list_sessions( $user_id, $user, $manager );

			case 'destroy_all':
				return $this->destroy_all_sessions( $user_id, $user, $manager );

			case 'destroy_others':
				return $this->destroy_other_sessions( $user_id, $user, $manager );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "destroy_all", or "destroy_others".', 'wp-agent' ),
				);
		}
	}

	/**
	 * List all active sessions for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int                $user_id User ID.
	 * @param \WP_User           $user    User object.
	 * @param \WP_Session_Tokens $manager Session manager.
	 * @return array Execution result.
	 */
	private function list_sessions( $user_id, $user, $manager ) {
		$sessions = $manager->get_all();

		if ( empty( $sessions ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'total'    => 0,
					'sessions' => array(),
				),
				'message' => sprintf(
					/* translators: %s: username */
					__( 'No active sessions for "%s".', 'wp-agent' ),
					$user->display_name
				),
			);
		}

		$results = array();
		foreach ( $sessions as $token_hash => $session ) {
			$results[] = array(
				'ip'         => $session['ip'] ?? 'unknown',
				'user_agent' => isset( $session['ua'] ) ? wp_trim_words( $session['ua'], 10 ) : 'unknown',
				'login_time' => isset( $session['login'] ) ? gmdate( 'Y-m-d H:i:s', $session['login'] ) : 'unknown',
				'expiration' => isset( $session['expiration'] ) ? gmdate( 'Y-m-d H:i:s', $session['expiration'] ) : 'unknown',
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'user'     => $user->display_name,
				'user_id'  => $user_id,
				'total'    => count( $results ),
				'sessions' => $results,
			),
			'message' => sprintf(
				/* translators: 1: count, 2: username */
				__( 'Found %1$d active session(s) for "%2$s".', 'wp-agent' ),
				count( $results ),
				$user->display_name
			),
		);
	}

	/**
	 * Destroy all sessions for a user.
	 *
	 * @since 1.0.0
	 *
	 * @param int                $user_id User ID.
	 * @param \WP_User           $user    User object.
	 * @param \WP_Session_Tokens $manager Session manager.
	 * @return array Execution result.
	 */
	private function destroy_all_sessions( $user_id, $user, $manager ) {
		$count = count( $manager->get_all() );
		$manager->destroy_all();

		return array(
			'success' => true,
			'data'    => array(
				'user'      => $user->display_name,
				'user_id'   => $user_id,
				'destroyed' => $count,
			),
			'message' => sprintf(
				/* translators: 1: count, 2: username */
				__( 'Destroyed %1$d session(s) for "%2$s". User must log in again.', 'wp-agent' ),
				$count,
				$user->display_name
			),
		);
	}

	/**
	 * Destroy all sessions except the current one.
	 *
	 * @since 1.0.0
	 *
	 * @param int                $user_id User ID.
	 * @param \WP_User           $user    User object.
	 * @param \WP_Session_Tokens $manager Session manager.
	 * @return array Execution result.
	 */
	private function destroy_other_sessions( $user_id, $user, $manager ) {
		$session_token = wp_get_session_token();
		$count         = count( $manager->get_all() );

		if ( $session_token ) {
			$manager->destroy_others( $session_token );
		} else {
			// No current session token — destroy all.
			$manager->destroy_all();
		}

		$remaining = count( $manager->get_all() );
		$destroyed = $count - $remaining;

		return array(
			'success' => true,
			'data'    => array(
				'user'      => $user->display_name,
				'user_id'   => $user_id,
				'destroyed' => $destroyed,
				'remaining' => $remaining,
			),
			'message' => sprintf(
				/* translators: 1: destroyed count, 2: username, 3: remaining */
				__( 'Destroyed %1$d other session(s) for "%2$s". %3$d session(s) remaining.', 'wp-agent' ),
				$destroyed,
				$user->display_name,
				$remaining
			),
		);
	}
}
