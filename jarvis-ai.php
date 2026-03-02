<?php
/**
 * Plugin Name: JARVIS AI
 * Description: AI-powered admin assistant for WordPress. Chat with your site using natural language in the Gutenberg editor sidebar.
 * Author: Brainstorm Force
 * Author URI: https://developer.suspended.suspended/
 * Version: 1.0.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jarvis-ai
 * Requires at least: 6.4
 * Requires PHP: 7.4
 *
 * @package JarvisAI
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set constants.
 */
define( 'JARVIS_AI_FILE', __FILE__ );
define( 'JARVIS_AI_BASE', plugin_basename( JARVIS_AI_FILE ) );
define( 'JARVIS_AI_DIR', plugin_dir_path( JARVIS_AI_FILE ) );
define( 'JARVIS_AI_URL', plugins_url( '/', JARVIS_AI_FILE ) );
define( 'JARVIS_AI_VER', '1.0.0' );
define( 'JARVIS_AI_DB_VER', '1.0.0' );

require_once JARVIS_AI_DIR . 'plugin-loader.php';

/**
 * Run on plugin activation.
 */
register_activation_hook(
	JARVIS_AI_FILE,
	function () {
		// Database tables are created via the autoloaded Database class.
		if ( class_exists( 'JarvisAI\Core\Database' ) ) {
			JarvisAI\Core\Database::activate();
		}

		// Store activation timestamp for first-run experience.
		add_option( 'jarvis_ai_activated_at', time() );

		// Flush rewrite rules so REST endpoints register cleanly.
		flush_rewrite_rules();
	}
);

/**
 * Run on plugin deactivation.
 */
register_deactivation_hook(
	JARVIS_AI_FILE,
	function () {
		// Clean up transients.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_jarvis_ai_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_jarvis_ai_' ) . '%'
			)
		);

		flush_rewrite_rules();
	}
);
