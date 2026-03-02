<?php
/**
 * MCP Server Registration.
 *
 * Exposes all JARVIS abilities as MCP tools for Claude Desktop, VS Code, etc.
 * Conditional — only loads when MCP Adapter is available.
 *
 * @package WPAgent\Integrations
 * @since   1.0.0
 */

namespace WPAgent\Integrations;

use WPAgent\Actions\Action_Registry;

defined( 'ABSPATH' ) || exit;

class MCP_Server {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'mcp_adapter_init', [ $this, 'register_mcp_server' ] );
	}

	public function register_mcp_server() {
		if ( ! class_exists( 'WP\\MCP\\Core\\McpAdapter' ) ) {
			return;
		}

		try {
			$adapter = \WP\MCP\Core\McpAdapter::instance();

			// Collect ability names registered by Abilities_Bridge (wp-agent/{action}).
			$registry     = Action_Registry::get_instance();
			$actions      = $registry->get_all_actions();
			$ability_names = [];
			foreach ( $actions as $name => $action ) {
				$ability_names[] = "wp-agent/{$name}";
			}

			$version = defined( 'WP_AGENT_VER' ) ? WP_AGENT_VER : '1.0.0';

			$adapter->create_server(
				'wp-agent-mcp',                                                     // server_id
				'wp-agent/v1',                                                       // server_route_namespace
				'/mcp',                                                              // server_route
				'WP Agent (JARVIS)',                                                 // server_name
				'AI-powered WordPress management — 70+ actions available as tools.', // server_description
				$version,                                                            // server_version
				[ \WP\MCP\Transport\HttpTransport::class ],                         // mcp_transports
				null,                                                                // error_handler (null = default)
				null,                                                                // observability_handler (null = default)
				$ability_names                                                       // tools (ability name strings)
			);
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'WP Agent MCP Server error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}
}
