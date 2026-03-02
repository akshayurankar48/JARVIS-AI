<?php
/**
 * REST Permissions Helper.
 *
 * Shared permission check for role-based access control across
 * chat, stream, history, and action controllers.
 *
 * @package JarvisAI\REST
 * @since   1.0.0
 */

namespace JarvisAI\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Class REST_Permissions
 *
 * @since 1.0.0
 */
class REST_Permissions {

	/**
	 * Check if the current user has an allowed role for JARVIS AI access.
	 *
	 * Checks the user's role against the jarvis_ai_allowed_roles option.
	 * Defaults to administrator-only if no option is set.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if the user has an allowed role.
	 */
	public static function current_user_has_allowed_role() {
		$user = wp_get_current_user();

		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		$allowed_roles = get_option( Settings_Controller::ROLES_OPTION, array( 'administrator' ) );

		if ( ! is_array( $allowed_roles ) || empty( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator' );
		}

		return ! empty( array_intersect( $user->roles, $allowed_roles ) );
	}
}
