<?php
/**
 * Manage Roles Action.
 *
 * Lists, creates, deletes, clones roles and manages capabilities.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

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
	const PROTECTED_ROLES = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ];

	public function get_name(): string {
		return 'manage_roles';
	}

	public function get_description(): string {
		return 'Manage WordPress user roles. List all roles with capabilities, create new roles, '
			. 'delete custom roles, clone existing roles, add or remove capabilities from roles.';
	}

	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'    => [
					'type'        => 'string',
					'enum'        => [ 'list', 'create', 'delete', 'add_cap', 'remove_cap', 'clone' ],
					'description' => 'Operation to perform.',
				],
				'role'         => [
					'type'        => 'string',
					'description' => 'Role slug. Required for create, delete, add_cap, remove_cap, clone.',
				],
				'display_name' => [
					'type'        => 'string',
					'description' => 'Human-readable role name. Required for create.',
				],
				'capabilities' => [
					'type'        => 'array',
					'items'       => [ 'type' => 'string' ],
					'description' => 'Capabilities to assign (create) or add/remove (add_cap/remove_cap).',
				],
				'source_role'  => [
					'type'        => 'string',
					'description' => 'Role to clone from. Required for clone operation.',
				],
			],
			'required'   => [ 'operation' ],
		];
	}

	public function get_capabilities_required(): string {
		return 'promote_users';
	}

	public function is_reversible(): bool {
		return true;
	}

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
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'wp-agent' ),
				];
		}
	}

	private function list_roles() {
		global $wp_roles;

		$roles = [];
		foreach ( $wp_roles->roles as $slug => $role_data ) {
			$roles[] = [
				'slug'         => $slug,
				'name'         => $role_data['name'],
				'capabilities' => array_keys( array_filter( $role_data['capabilities'] ) ),
				'user_count'   => count( get_users( [ 'role' => $slug, 'fields' => 'ID' ] ) ),
				'is_protected' => in_array( $slug, self::PROTECTED_ROLES, true ),
			];
		}

		return [
			'success' => true,
			'data'    => [ 'roles' => $roles ],
			'message' => sprintf(
				/* translators: %d: role count */
				__( '%d role(s) found.', 'wp-agent' ),
				count( $roles )
			),
		];
	}

	private function create_role( array $params ) {
		$role         = sanitize_key( $params['role'] ?? '' );
		$display_name = sanitize_text_field( $params['display_name'] ?? '' );
		$capabilities = isset( $params['capabilities'] ) && is_array( $params['capabilities'] ) ? $params['capabilities'] : [];

		if ( empty( $role ) || empty( $display_name ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'role and display_name are required.', 'wp-agent' ),
			];
		}

		if ( get_role( $role ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" already exists.', 'wp-agent' ),
					$role
				),
			];
		}

		$caps = [];
		foreach ( $capabilities as $cap ) {
			$caps[ sanitize_key( $cap ) ] = true;
		}

		$result = add_role( $role, $display_name, $caps );

		if ( null === $result ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to create role.', 'wp-agent' ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'role'         => $role,
				'display_name' => $display_name,
				'capabilities' => array_keys( $caps ),
			],
			'message' => sprintf(
				/* translators: %s: role name */
				__( 'Role "%s" created.', 'wp-agent' ),
				$display_name
			),
		];
	}

	private function delete_role( array $params ) {
		$role = sanitize_key( $params['role'] ?? '' );

		if ( empty( $role ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'role is required.', 'wp-agent' ),
			];
		}

		if ( in_array( $role, self::PROTECTED_ROLES, true ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Cannot delete built-in WordPress roles.', 'wp-agent' ),
			];
		}

		if ( ! get_role( $role ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" not found.', 'wp-agent' ),
					$role
				),
			];
		}

		remove_role( $role );

		return [
			'success' => true,
			'data'    => [ 'role' => $role ],
			'message' => sprintf(
				/* translators: %s: role slug */
				__( 'Role "%s" deleted.', 'wp-agent' ),
				$role
			),
		];
	}

	private function modify_caps( array $params, bool $add ) {
		$role_slug    = sanitize_key( $params['role'] ?? '' );
		$capabilities = isset( $params['capabilities'] ) && is_array( $params['capabilities'] ) ? $params['capabilities'] : [];

		if ( empty( $role_slug ) || empty( $capabilities ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'role and capabilities are required.', 'wp-agent' ),
			];
		}

		$role = get_role( $role_slug );
		if ( ! $role ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Role "%s" not found.', 'wp-agent' ),
					$role_slug
				),
			];
		}

		$modified = [];
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

		return [
			'success' => true,
			'data'    => [
				'role'         => $role_slug,
				'capabilities' => $modified,
				'action'       => $add ? 'added' : 'removed',
			],
			'message' => sprintf(
				/* translators: 1: count, 2: action, 3: role */
				__( '%1$d capability(ies) %2$s "%3$s".', 'wp-agent' ),
				count( $modified ),
				$action,
				$role_slug
			),
		];
	}

	private function clone_role( array $params ) {
		$new_role     = sanitize_key( $params['role'] ?? '' );
		$display_name = sanitize_text_field( $params['display_name'] ?? '' );
		$source_slug  = sanitize_key( $params['source_role'] ?? '' );

		if ( empty( $new_role ) || empty( $source_slug ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'role and source_role are required.', 'wp-agent' ),
			];
		}

		$source = get_role( $source_slug );
		if ( ! $source ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: role slug */
					__( 'Source role "%s" not found.', 'wp-agent' ),
					$source_slug
				),
			];
		}

		if ( empty( $display_name ) ) {
			$display_name = ucfirst( str_replace( [ '-', '_' ], ' ', $new_role ) );
		}

		$result = add_role( $new_role, $display_name, $source->capabilities );

		if ( null === $result ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to clone role. It may already exist.', 'wp-agent' ),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'role'         => $new_role,
				'display_name' => $display_name,
				'cloned_from'  => $source_slug,
				'capabilities' => array_keys( array_filter( $source->capabilities ) ),
			],
			'message' => sprintf(
				/* translators: 1: new role, 2: source role */
				__( 'Role "%1$s" cloned from "%2$s".', 'wp-agent' ),
				$display_name,
				$source_slug
			),
		];
	}
}
