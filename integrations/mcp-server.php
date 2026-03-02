<?php
/**
 * MCP Server Registration.
 *
 * Exposes all JARVIS abilities as MCP tools for Claude Desktop, VS Code, etc.
 * Conditional — only loads when MCP Adapter is available.
 *
 * @package JarvisAI\Integrations
 * @since   1.0.0
 */

namespace JarvisAI\Integrations;

use JarvisAI\Actions\Action_Registry;

defined( 'ABSPATH' ) || exit;

/**
 * Class MCP_Server
 *
 * Registers JARVIS abilities as MCP tools for external AI clients.
 *
 * @package WP_Agent
 * @since   1.0.0
 */
class MCP_Server {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor. Hooks into MCP adapter init.
	 */
	public function __construct() {
		add_action( 'mcp_adapter_init', array( $this, 'register_mcp_server' ) );
	}

	/**
	 * Register the MCP server with all JARVIS abilities as tools.
	 *
	 * @return void
	 */
	public function register_mcp_server() {
		if ( ! class_exists( 'WP\\MCP\\Core\\McpAdapter' ) ) {
			return;
		}

		try {
			$adapter = \WP\MCP\Core\McpAdapter::instance();

			// Collect ability names registered by Abilities_Bridge (jarvis-ai/{action}).
			$registry      = Action_Registry::get_instance();
			$actions       = $registry->get_all_actions();
			$ability_names = array();
			foreach ( $actions as $name => $action ) {
				$ability_names[] = "jarvis-ai/{$name}";
			}

			$version = defined( 'JARVIS_AI_VER' ) ? JARVIS_AI_VER : '1.0.0';

			$adapter->create_server(
				'jarvis-ai-mcp',                                                     // server_id
				'jarvis-ai/v1',                                                       // server_route_namespace
				'/mcp',                                                              // server_route
				'JARVIS AI (JARVIS)',                                                 // server_name
				'AI-powered WordPress management — 70+ actions available as tools.', // server_description
				$version,                                                            // server_version
				array( \WP\MCP\Transport\HttpTransport::class ),                         // mcp_transports
				null,                                                                // error_handler (null = default)
				null,                                                                // observability_handler (null = default)
				$ability_names                                                       // tools (ability name strings)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'JARVIS AI MCP Server error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
