<?php
/**
 * Delete Plugin Action.
 *
 * Permanently deletes a WordPress plugin from the filesystem.
 * Auto-deactivates the plugin if it is currently active.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Delete_Plugin
 *
 * @since 1.0.0
 */
class Delete_Plugin implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'delete_plugin';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Permanently delete a WordPress plugin from the filesystem. '
			. 'If the plugin is active, it will be deactivated first. '
			. 'Pass the plugin file path (e.g. "akismet/akismet.php"). '
			. 'Cannot delete WP Agent itself.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin file path relative to plugins directory (e.g. "akismet/akismet.php").',
				],
			],
			'required'   => [ 'plugin' ],
		];
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'delete_plugins';
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
		$plugin = sanitize_text_field( $params['plugin'] );

		// Validate file path.
		if ( 0 !== validate_file( $plugin ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid plugin file path.', 'wp-agent' ),
			];
		}

		// Self-deletion guard.
		if ( defined( 'WP_AGENT_BASE' ) && WP_AGENT_BASE === $plugin ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Cannot delete WP Agent through its own action system.', 'wp-agent' ),
			];
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// Verify plugin is installed.
		$installed = get_plugins();
		if ( ! isset( $installed[ $plugin ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin file */
					__( 'Plugin "%s" is not installed.', 'wp-agent' ),
					$plugin
				),
			];
		}

		$plugin_name = $installed[ $plugin ]['Name'];

		// Auto-deactivate if active.
		if ( is_plugin_active( $plugin ) ) {
			deactivate_plugins( [ $plugin ] );
		}

		// Delete the plugin.
		$result = delete_plugins( [ $plugin ] );

		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => $result->get_error_message(),
			];
		}

		if ( true !== $result ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin name */
					__( 'Failed to delete "%s". Check filesystem permissions.', 'wp-agent' ),
					$plugin_name
				),
			];
		}

		return [
			'success' => true,
			'data'    => [
				'plugin' => $plugin,
				'name'   => $plugin_name,
			],
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( 'Deleted plugin "%s" permanently.', 'wp-agent' ),
				$plugin_name
			),
		];
	}
}
