<?php
/**
 * Deactivate Plugin Action.
 *
 * Deactivates an active WordPress plugin by its plugin file path.
 * Validates the plugin exists and is currently active before deactivating.
 * Includes a self-deactivation guard to prevent JARVIS AI from deactivating itself.
 *
 * @package JarvisAI\Actions
 * @since   1.0.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Deactivate_Plugin
 *
 * @since 1.0.0
 */
class Deactivate_Plugin implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'deactivate_plugin';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Deactivate a currently active WordPress plugin. Requires the plugin file path '
			. '(e.g. "akismet/akismet.php", "hello.php"). Cannot deactivate JARVIS AI itself.';
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
				'plugin' => array(
					'type'        => 'string',
					'description' => 'The plugin file path relative to the plugins directory (e.g. "akismet/akismet.php").',
				),
			),
			'required'   => array( 'plugin' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'activate_plugins';
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
		$plugin = sanitize_text_field( $params['plugin'] );

		// Validate the file path is safe.
		if ( 0 !== validate_file( $plugin ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid plugin file path.', 'jarvis-ai' ),
			);
		}

		// Self-deactivation guard: prevent deactivating JARVIS AI itself.
		if ( defined( 'JARVIS_AI_BASE' ) && JARVIS_AI_BASE === $plugin ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Cannot deactivate JARVIS AI through its own action system.', 'jarvis-ai' ),
			);
		}

		// Load plugin functions if not already available.
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Validate the plugin exists in the installed plugins list.
		$installed_plugins = get_plugins();

		if ( ! isset( $installed_plugins[ $plugin ] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin file path */
					__( 'Plugin "%s" is not installed.', 'jarvis-ai' ),
					$plugin
				),
			);
		}

		// Check if already inactive.
		if ( ! is_plugin_active( $plugin ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin name */
					__( 'Plugin "%s" is already inactive.', 'jarvis-ai' ),
					$installed_plugins[ $plugin ]['Name']
				),
			);
		}

		// Deactivate the plugin.
		deactivate_plugins( array( $plugin ) );

		// Verify deactivation succeeded.
		if ( is_plugin_active( $plugin ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: plugin name */
					__( 'Failed to deactivate "%s".', 'jarvis-ai' ),
					$installed_plugins[ $plugin ]['Name']
				),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'plugin' => $plugin,
				'name'   => $installed_plugins[ $plugin ]['Name'],
			),
			'message' => sprintf(
				/* translators: %s: plugin name */
				__( 'Deactivated plugin "%s" successfully.', 'jarvis-ai' ),
				$installed_plugins[ $plugin ]['Name']
			),
		);
	}
}
