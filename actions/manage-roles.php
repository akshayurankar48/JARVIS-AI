<?php
/**
 * Manage Roles Action.
 *
 * Lists, creates, deletes, clones roles and manages capabilities.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Roles
 *
 * @since 1.1.0
 */
class Manage_Roles implements Action_Interface {

	/**
	 * Protected roles that cannot be deleted.
	 *
	 * @var string[]
	 */
	const PROTECTED_ROLES = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_roles';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage WordPress user roles. List all roles with capabilities, create new roles, '
			. 'delete custom roles, clone existing roles, add or remove capabilities from roles.';
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
				'operation'    => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'create', 'delete', 'add_cap', 'remove_cap', 'clone' ),
					'description' => 'Operation to perform.',
				),
				'role'         => array(
					'type'        => 'string',
					'description' => 'Role slug. Required for create, delete, add_cap, remove_cap, clone.',
				),
				'display_name' => array(
					'type'        => 'string',
					'description' => 'Human-readable role name. Required for create.',
				),
				'capabilities' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Capabilities to assign (create) or add/remove (add_cap/remove_cap).',
				),
				'source_role'  => array(
					'type'        => 'string',
					'description' => 'Role to clone from. Required for clone operation.',
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
		return 'promote_users';
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
				return $this->list_roles();
			case 'create':
				return $this->create_role( $params );
			case 'delete':
				return $this->delete_role( $params );
			case 'add_cap':
				return $this->modify_caps( $params, true );
			case 'remove_cap':
				return $this->modify_caps( $params, false );
			case 'clone':
				return $this->clone_role( $params );
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * List all registered WordPress roles with capabilities and user counts.
	 *
	 * @since 1.1.0
	 * @return array Execution result.
	 */
	private function list_roles() {
		global $wp_roles;

		$roles = array();
		foreach ( $wp_roles->roles as $slug => $role_data ) {
			$roles[] = array(
				'slug'         => $slug,
				'name'         => $role_data['name'],
				'capabilities' => array_keys( array_filter( $role_data['capabilities'] ) ),
				'user_count'   => count(
					get_users(
						array(
							'role'   => $slug,
							'fields' => 'ID',
						)
					)
				),
				'is_protected' => in_array( $slug, self::PROTECTED_ROLES, true ),
			);
		}

		return array(
			'success' => true,
			'data'    => array( 'roles' => $roles ),
			'message' => sprintf(
				/* translators: %d: role count */
				__( '%d role(s) found.', 'jarvis-ai' ),
				count( $roles )
			),
		);
	}

	/**
	 * Create a new WordPress role with specified capabilities.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters including role, display_name, and capabilities.
	 * @return array Execution result.
	 */
	private function create_role( array $params ) {
		$role         = sanitize_key( $params['role'] ?? '' );
		$display_name = sanitize_text_field( $params['display_name'] ?? '' );
		$capabilities = isset( $params['capabilities'] ) && is_array( $params['capabilities'] ) ? $params['capabilities'] : array();

		if ( empty( $role ) || empty( $display_name ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'role and display_name are required.', 'jarvis-ai' ),
			);
		}

		if ( get_role( $role ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" already exists.', 'jarvis-ai' ),
					$role
				),
			);
		}

		$caps = array();
		foreach ( $capabilities as $cap ) {
			$caps[ sanitize_key( $cap ) ] = true;
		}

		$result = add_role( $role, $display_name, $caps );

		if ( null === $result ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to create role.', 'jarvis-ai' ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'role'         => $role,
				'display_name' => $display_name,
				'capabilities' => array_keys( $caps ),
			),
			'message' => sprintf(
				/* translators: %s: role name */
				__( 'Role "%s" created.', 'jarvis-ai' ),
				$display_name
			),
		);
	}

	/**
	 * Delete a custom WordPress role.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters including role slug.
	 * @return array Execution result.
	 */
	private function delete_role( array $params ) {
		$role = sanitize_key( $params['role'] ?? '' );

		if ( empty( $role ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'role is required.', 'jarvis-ai' ),
			);
		}

		if ( in_array( $role, self::PROTECTED_ROLES, true ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Cannot delete built-in WordPress roles.', 'jarvis-ai' ),
			);
		}

		if ( ! get_role( $role ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" not found.', 'jarvis-ai' ),
					$role
				),
			);
		}

		remove_role( $role );

		return array(
			'success' => true,
			'data'    => array( 'role' => $role ),
			'message' => sprintf(
				/* translators: %s: role slug */
				__( 'Role "%s" deleted.', 'jarvis-ai' ),
				$role
			),
		);
	}

	/**
	 * Add or remove capabilities from a role.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters including role and capabilities.
	 * @param bool  $add    Whether to add (true) or remove (false) capabilities.
	 * @return array Execution result.
	 */
	private function modify_caps( array $params, bool $add ) {
		$role_slug    = sanitize_key( $params['role'] ?? '' );
		$capabilities = isset( $params['capabilities'] ) && is_array( $params['capabilities'] ) ? $params['capabilities'] : array();

		if ( empty( $role_slug ) || empty( $capabilities ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'role and capabilities are required.', 'jarvis-ai' ),
			);
		}

		$role = get_role( $role_slug );
		if ( ! $role ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" not found.', 'jarvis-ai' ),
					$role_slug
				),
			);
		}

		$modified = array();
		foreach ( $capabilities as $cap ) {
			$cap = sanitize_key( $cap );
			if ( $add ) {
				$role->add_cap( $cap );
			} else {
				$role->remove_cap( $cap );
			}
			$modified[] = $cap;
		}

		$action = $add ? 'added to' : 'removed from';

		return array(
			'success' => true,
			'data'    => array(
				'role'         => $role_slug,
				'capabilities' => $modified,
				'action'       => $add ? 'added' : 'removed',
			),
			'message' => sprintf(
				/* translators: 1: count, 2: action, 3: role */
				__( '%1$d capability(ies) %2$s "%3$s".', 'jarvis-ai' ),
				count( $modified ),
				$action,
				$role_slug
			),
		);
	}

	/**
	 * Clone an existing role into a new role with identical capabilities.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters including role, display_name, and source_role.
	 * @return array Execution result.
	 */
	private function clone_role( array $params ) {
		$new_role     = sanitize_key( $params['role'] ?? '' );
		$display_name = sanitize_text_field( $params['display_name'] ?? '' );
		$source_slug  = sanitize_key( $params['source_role'] ?? '' );

		if ( empty( $new_role ) || empty( $source_slug ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'role and source_role are required.', 'jarvis-ai' ),
			);
		}

		$source = get_role( $source_slug );
		if ( ! $source ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Source role "%s" not found.', 'jarvis-ai' ),
					$source_slug
				),
			);
		}

		if ( empty( $display_name ) ) {
			$display_name = ucfirst( str_replace( array( '-', '_' ), ' ', $new_role ) );
		}

		$result = add_role( $new_role, $display_name, $source->capabilities );

		if ( null === $result ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to clone role. It may already exist.', 'jarvis-ai' ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'role'         => $new_role,
				'display_name' => $display_name,
				'cloned_from'  => $source_slug,
				'capabilities' => array_keys( array_filter( $source->capabilities ) ),
			),
			'message' => sprintf(
				/* translators: 1: new role, 2: source role */
				__( 'Role "%1$s" cloned from "%2$s".', 'jarvis-ai' ),
				$display_name,
				$source_slug
			),
		);
	}
}
