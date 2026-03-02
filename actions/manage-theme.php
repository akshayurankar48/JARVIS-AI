<?php
/**
 * Manage Theme Action.
 *
 * Lists installed themes, gets the active theme info, or switches
 * to a different theme. Validates theme existence before switching.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Theme
 *
 * @since 1.0.0
 */
class Manage_Theme implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_theme';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage installed WordPress themes. Operations: "list" shows all installed themes, '
			. '"get_active" returns details about the current theme, '
			. '"switch" activates a different installed theme, '
			. '"update" updates a theme to its latest version, '
			. '"delete" permanently removes an inactive theme. '
			. 'To install a new theme from WordPress.org, use install_theme first.';
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
				'operation'  => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'get_active', 'switch', 'update', 'delete' ),
					'description' => 'Operation to perform.',
				),
				'stylesheet' => array(
					'type'        => 'string',
					'description' => 'Theme stylesheet (slug) to switch to. Required for "switch" operation.',
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
		return 'switch_themes';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return true;
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

		switch ( $operation ) {
			case 'list':
				return $this->list_themes();

			case 'get_active':
				return $this->get_active_theme();

			case 'switch':
				$stylesheet = ! empty( $params['stylesheet'] ) ? sanitize_text_field( $params['stylesheet'] ) : '';
				return $this->switch_theme( $stylesheet );

			case 'update':
				$stylesheet = ! empty( $params['stylesheet'] ) ? sanitize_text_field( $params['stylesheet'] ) : '';
				return $this->update_theme( $stylesheet );

			case 'delete':
				$stylesheet = ! empty( $params['stylesheet'] ) ? sanitize_text_field( $params['stylesheet'] ) : '';
				return $this->delete_theme( $stylesheet );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "get_active", "switch", "update", or "delete".', 'wp-agent' ),
				);
		}
	}

	/**
	 * List all installed themes.
	 *
	 * @since 1.0.0
	 * @return array Execution result.
	 */
	private function list_themes() {
		$themes       = wp_get_themes();
		$active_theme = get_stylesheet();
		$results      = array();

		foreach ( $themes as $stylesheet => $theme ) {
			$results[] = array(
				'name'           => $theme->get( 'Name' ),
				'slug'           => $stylesheet,
				'version'        => $theme->get( 'Version' ),
				'author'         => $theme->get( 'Author' ),
				'is_block_theme' => $theme->is_block_theme(),
				'is_active'      => ( $stylesheet === $active_theme ),
				'screenshot_url' => $theme->get_screenshot() ? $theme->get_screenshot() : '',
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'total'  => count( $results ),
				'themes' => $results,
			),
			'message' => sprintf(
				/* translators: %d: theme count */
				__( '%d theme(s) installed.', 'wp-agent' ),
				count( $results )
			),
		);
	}

	/**
	 * Get the active theme details.
	 *
	 * @since 1.0.0
	 * @return array Execution result.
	 */
	private function get_active_theme() {
		$theme      = wp_get_theme();
		$stylesheet = get_stylesheet();

		$data = array(
			'name'           => $theme->get( 'Name' ),
			'slug'           => $stylesheet,
			'version'        => $theme->get( 'Version' ),
			'author'         => $theme->get( 'Author' ),
			'description'    => $theme->get( 'Description' ),
			'is_block_theme' => $theme->is_block_theme(),
			'is_child_theme' => is_child_theme(),
			'template'       => get_template(),
			'screenshot_url' => $theme->get_screenshot() ? $theme->get_screenshot() : '',
		);

		if ( is_child_theme() ) {
			$parent = $theme->parent();
			if ( $parent ) {
				$data['parent_theme'] = $parent->get( 'Name' );
			}
		}

		return array(
			'success' => true,
			'data'    => $data,
			'message' => sprintf(
				/* translators: 1: theme name, 2: version */
				__( 'Active theme: %1$s (v%2$s).', 'wp-agent' ),
				$data['name'],
				$data['version']
			),
		);
	}

	/**
	 * Switch to a different theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stylesheet Theme stylesheet to switch to.
	 * @return array Execution result.
	 */
	private function switch_theme( $stylesheet ) {
		if ( empty( $stylesheet ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Theme stylesheet (slug) is required for switching.', 'wp-agent' ),
			);
		}

		// Validate the theme exists.
		$themes = wp_get_themes();
		if ( ! isset( $themes[ $stylesheet ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme stylesheet */
					__( 'Theme "%s" is not installed.', 'wp-agent' ),
					$stylesheet
				),
			);
		}

		// Check if already active.
		if ( get_stylesheet() === $stylesheet ) {
			return array(
				'success' => true,
				'data'    => array( 'slug' => $stylesheet ),
				'message' => sprintf(
					/* translators: %s: theme name */
					__( '"%s" is already the active theme.', 'wp-agent' ),
					$themes[ $stylesheet ]->get( 'Name' )
				),
			);
		}

		$previous_theme = wp_get_theme()->get( 'Name' );

		switch_theme( $stylesheet );

		// Verify the switch.
		if ( get_stylesheet() !== $stylesheet ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Theme switch failed. The theme may have errors.', 'wp-agent' ),
			);
		}

		$new_theme = wp_get_theme();

		return array(
			'success' => true,
			'data'    => array(
				'previous'       => $previous_theme,
				'current'        => $new_theme->get( 'Name' ),
				'slug'           => $stylesheet,
				'is_block_theme' => $new_theme->is_block_theme(),
			),
			'message' => sprintf(
				/* translators: 1: previous theme, 2: new theme */
				__( 'Switched theme from "%1$s" to "%2$s".', 'wp-agent' ),
				$previous_theme,
				$new_theme->get( 'Name' )
			),
		);
	}

	/**
	 * Update a theme to its latest version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stylesheet Theme stylesheet to update.
	 * @return array Execution result.
	 */
	private function update_theme( $stylesheet ) {
		if ( empty( $stylesheet ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Theme stylesheet (slug) is required for updating.', 'wp-agent' ),
			);
		}

		$themes = wp_get_themes();
		if ( ! isset( $themes[ $stylesheet ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme stylesheet */
					__( 'Theme "%s" is not installed.', 'wp-agent' ),
					$stylesheet
				),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';

		// Refresh update transient.
		wp_update_themes();

		$old_version = $themes[ $stylesheet ]->get( 'Version' );
		$skin        = new \WP_Ajax_Upgrader_Skin();
		$upgrader    = new \Theme_Upgrader( $skin );
		$result      = $upgrader->upgrade( $stylesheet );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $result->get_error_message(),
			);
		}

		if ( is_wp_error( $skin->result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $skin->result->get_error_message(),
			);
		}

		if ( false === $result ) {
			return array(
				'success' => true,
				'data'    => array(
					'slug'    => $stylesheet,
					'version' => $old_version,
				),
				'message' => sprintf(
					/* translators: 1: theme name, 2: version */
					__( '"%1$s" is already at the latest version (%2$s).', 'wp-agent' ),
					$themes[ $stylesheet ]->get( 'Name' ),
					$old_version
				),
			);
		}

		// Re-read to get new version.
		wp_clean_themes_cache();
		$updated_theme = wp_get_theme( $stylesheet );
		$new_version   = $updated_theme->get( 'Version' );

		return array(
			'success' => true,
			'data'    => array(
				'slug'        => $stylesheet,
				'name'        => $themes[ $stylesheet ]->get( 'Name' ),
				'old_version' => $old_version,
				'new_version' => $new_version,
			),
			'message' => sprintf(
				/* translators: 1: theme name, 2: old version, 3: new version */
				__( 'Updated "%1$s" from v%2$s to v%3$s.', 'wp-agent' ),
				$themes[ $stylesheet ]->get( 'Name' ),
				$old_version,
				$new_version
			),
		);
	}

	/**
	 * Delete an inactive theme.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stylesheet Theme stylesheet to delete.
	 * @return array Execution result.
	 */
	private function delete_theme( $stylesheet ) {
		if ( empty( $stylesheet ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Theme stylesheet (slug) is required for deletion.', 'wp-agent' ),
			);
		}

		$themes = wp_get_themes();
		if ( ! isset( $themes[ $stylesheet ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme stylesheet */
					__( 'Theme "%s" is not installed.', 'wp-agent' ),
					$stylesheet
				),
			);
		}

		// Guard: cannot delete active theme.
		if ( get_stylesheet() === $stylesheet ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme name */
					__( 'Cannot delete the active theme "%s". Switch to another theme first.', 'wp-agent' ),
					$themes[ $stylesheet ]->get( 'Name' )
				),
			);
		}

		// Guard: cannot delete parent of active child theme.
		if ( is_child_theme() && get_template() === $stylesheet ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme name */
					__( 'Cannot delete "%s" because it is the parent of the active child theme.', 'wp-agent' ),
					$themes[ $stylesheet ]->get( 'Name' )
				),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$theme_name = $themes[ $stylesheet ]->get( 'Name' );
		$result     = delete_theme( $stylesheet );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $result->get_error_message(),
			);
		}

		if ( true !== $result ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: theme name */
					__( 'Failed to delete "%s". Check filesystem permissions.', 'wp-agent' ),
					$theme_name
				),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'slug' => $stylesheet,
				'name' => $theme_name,
			),
			'message' => sprintf(
				/* translators: %s: theme name */
				__( 'Deleted theme "%s" permanently.', 'wp-agent' ),
				$theme_name
			),
		);
	}
}
