<?php
/**
 * Update Plugin Action.
 *
 * Updates one or all WordPress plugins to their latest versions.
 * Uses the WordPress Plugin_Upgrader API for safe upgrades.
 *
 * @package JarvisAI\Actions
 * @since   1.0.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Update_Plugin
 *
 * @since 1.0.0
 */
class Update_Plugin implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'update_plugin';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Update a WordPress plugin to its latest version, or update all plugins at once. '
			. 'Call list_plugins first to check which plugins have updates available. '
			. 'Pass the plugin file path (e.g. "akismet/akismet.php") or set update_all to true.';
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
				'plugin'     => array(
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php"). Required unless update_all is true.',
				),
				'update_all' => array(
					'type'        => 'boolean',
					'description' => 'Set to true to update all plugins that have updates available.',
				),
			),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'update_plugins';
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
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/update.php';

		$update_all = ! empty( $params['update_all'] );
		$plugin     = isset( $params['plugin'] ) ? sanitize_text_field( $params['plugin'] ) : '';

		if ( ! $update_all && empty( $plugin ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Provide a plugin file path or set update_all to true.', 'jarvis-ai' ),
			);
		}

		// Refresh update transient.
		wp_update_plugins();
		$updates = get_plugin_updates();

		if ( $update_all ) {
			return $this->update_all_plugins( $updates );
		}

		return $this->update_single_plugin( $plugin, $updates );
	}

	/**
	 * Update a single plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin  Plugin file path.
	 * @param array  $updates Available updates.
	 * @return array Execution result.
	 */
	private function update_single_plugin( $plugin, $updates ) {
		// Validate file path.
		if ( 0 !== validate_file( $plugin ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid plugin file path.', 'jarvis-ai' ),
			);
		}

		// Self-update guard.
		if ( defined( 'JARVIS_AI_BASE' ) && JARVIS_AI_BASE === $plugin ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Cannot update JARVIS AI through its own action system.', 'jarvis-ai' ),
			);
		}

		// Verify plugin is installed.
		$installed = get_plugins();
		if ( ! isset( $installed[ $plugin ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin file */
					__( 'Plugin "%s" is not installed.', 'jarvis-ai' ),
					$plugin
				),
			);
		}

		// Check if update is available.
		if ( ! isset( $updates[ $plugin ] ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'plugin'  => $plugin,
					'name'    => $installed[ $plugin ]['Name'],
					'version' => $installed[ $plugin ]['Version'],
				),
				'message' => sprintf(
					/* translators: 1: plugin name, 2: version */
					__( '"%1$s" is already at the latest version (%2$s).', 'jarvis-ai' ),
					$installed[ $plugin ]['Name'],
					$installed[ $plugin ]['Version']
				),
			);
		}

		$old_version = $installed[ $plugin ]['Version'];
		$skin        = new \WP_Ajax_Upgrader_Skin();
		$upgrader    = new \Plugin_Upgrader( $skin );
		$result      = $upgrader->upgrade( $plugin );

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
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin name */
					__( 'Failed to update "%s". Check filesystem permissions.', 'jarvis-ai' ),
					$installed[ $plugin ]['Name']
				),
			);
		}

		// Re-read to get new version.
		wp_cache_delete( 'plugins', 'plugins' );
		$updated_plugins = get_plugins();
		$new_version     = isset( $updated_plugins[ $plugin ] ) ? $updated_plugins[ $plugin ]['Version'] : 'unknown';

		return array(
			'success' => true,
			'data'    => array(
				'plugin'      => $plugin,
				'name'        => $installed[ $plugin ]['Name'],
				'old_version' => $old_version,
				'new_version' => $new_version,
			),
			'message' => sprintf(
				/* translators: 1: plugin name, 2: old version, 3: new version */
				__( 'Updated "%1$s" from v%2$s to v%3$s.', 'jarvis-ai' ),
				$installed[ $plugin ]['Name'],
				$old_version,
				$new_version
			),
		);
	}

	/**
	 * Update all plugins with available updates.
	 *
	 * @since 1.0.0
	 *
	 * @param array $updates Available updates.
	 * @return array Execution result.
	 */
	private function update_all_plugins( $updates ) {
		if ( empty( $updates ) ) {
			return array(
				'success' => true,
				'data'    => array( 'updated' => 0 ),
				'message' => __( 'All plugins are up to date.', 'jarvis-ai' ),
			);
		}

		// Remove self from update list.
		if ( defined( 'JARVIS_AI_BASE' ) && isset( $updates[ JARVIS_AI_BASE ] ) ) {
			unset( $updates[ JARVIS_AI_BASE ] );
		}

		if ( empty( $updates ) ) {
			return array(
				'success' => true,
				'data'    => array( 'updated' => 0 ),
				'message' => __( 'All plugins are up to date (JARVIS AI excluded from self-update).', 'jarvis-ai' ),
			);
		}

		$plugins_to_update = array_keys( $updates );
		$skin              = new \WP_Ajax_Upgrader_Skin();
		$upgrader          = new \Plugin_Upgrader( $skin );
		$results           = $upgrader->bulk_upgrade( $plugins_to_update );

		$updated = array();
		$failed  = array();

		foreach ( $plugins_to_update as $plugin_file ) {
			if ( ! empty( $results[ $plugin_file ] ) && ! is_wp_error( $results[ $plugin_file ] ) ) {
				$updated[] = $updates[ $plugin_file ]->Name ?? $plugin_file;
			} else {
				$failed[] = $updates[ $plugin_file ]->Name ?? $plugin_file;
			}
		}

		$success = count( $failed ) === 0;

		return array(
			'success' => $success,
			'data'    => array(
				'updated'      => count( $updated ),
				'failed'       => count( $failed ),
				'updated_list' => $updated,
				'failed_list'  => $failed,
			),
			'message' => sprintf(
				/* translators: 1: updated count, 2: failed count */
				__( 'Updated %1$d plugin(s). %2$d failed.', 'jarvis-ai' ),
				count( $updated ),
				count( $failed )
			),
		);
	}
}
