<?php
/**
 * Assets Manager.
 *
 * @package JarvisAI\Admin
 * @since 1.0.0
 */

namespace JarvisAI\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Assets_Manager
 *
 * @since 1.0.0
 */
class Assets_Manager {

	/**
	 * Instance
	 *
	 * @access private
	 * @var Assets_Manager|null Class Instance.
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Admin page hook suffixes.
	 *
	 * @var string[]
	 */
	private const PAGE_HOOKS = array(
		'toplevel_page_jarvis-ai',
		'jarvis-ai_page_jarvis-ai-settings',
		'jarvis-ai_page_jarvis-ai-history',
		'jarvis-ai_page_jarvis-ai-schedules',
		'jarvis-ai_page_jarvis-ai-capabilities',
		'jarvis-ai_page_jarvis-ai-help',
	);

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return Assets_Manager Initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_drawer_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_animations' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_ab_testing' ) );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, self::PAGE_HOOKS, true ) ) {
			return;
		}

		$asset_file = JARVIS_AI_DIR . 'build/main.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'jarvis-ai-admin',
			JARVIS_AI_URL . 'build/main.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'jarvis-ai-admin',
			JARVIS_AI_URL . 'build/style-main.css',
			array(),
			$asset['version']
		);

		wp_localize_script(
			'jarvis-ai-admin',
			'jarvisAiData',
			array(
				'restUrl'     => rest_url( 'jarvis-ai/v1/' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
				'hasApiKey'   => ! empty( get_option( \JarvisAI\AI\Open_Router_Client::API_KEY_OPTION ) ),
				'userId'      => get_current_user_id(),
				'userName'    => wp_get_current_user()->display_name,
				'version'     => JARVIS_AI_VER,
				'adminUrl'    => admin_url(),
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading admin page slug, no form action.
				'currentPage' => isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'jarvis-ai',
			)
		);

		// Hide default admin notices on our pages.
		wp_add_inline_style(
			'jarvis-ai-admin',
			'.jarvis-ai-wrap ~ .notice, .jarvis-ai-wrap ~ .updated, .jarvis-ai-wrap ~ .error, .jarvis-ai-wrap .notice, div.notice:not(.jarvis-ai-notice) { display: none !important; } #wpcontent { padding-left: 0; } #wpbody-content { padding-bottom: 0; }'
		);
	}

	/**
	 * Enqueue drawer assets on all admin pages except JARVIS AI's own pages
	 * and the block editor (which has its own PluginSidebar).
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_drawer_assets( $hook_suffix ) {
		// Skip on JARVIS AI pages — they already have the full UI.
		if ( in_array( $hook_suffix, self::PAGE_HOOKS, true ) ) {
			return;
		}

		// Skip in the block editor — it has the PluginSidebar.
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			return;
		}

		// Only for admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$asset_file = JARVIS_AI_DIR . 'build/drawer.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'jarvis-ai-drawer',
			JARVIS_AI_URL . 'build/drawer.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		if ( file_exists( JARVIS_AI_DIR . 'build/style-drawer.css' ) ) {
			wp_enqueue_style(
				'jarvis-ai-drawer',
				JARVIS_AI_URL . 'build/style-drawer.css',
				array(),
				$asset['version']
			);
		}

		wp_localize_script(
			'jarvis-ai-drawer',
			'jarvisAiData',
			array(
				'restUrl'   => rest_url( 'jarvis-ai/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'hasApiKey' => ! empty( get_option( \JarvisAI\AI\Open_Router_Client::API_KEY_OPTION ) ),
				'userId'    => get_current_user_id(),
				'userName'  => wp_get_current_user()->display_name,
				'version'   => JARVIS_AI_VER,
				'adminUrl'  => admin_url(),
			)
		);
	}

	/**
	 * Enqueue animation assets on the frontend when content uses wpa- classes.
	 *
	 * Checks the current post content for animation class names and only
	 * enqueues the CSS/JS when at least one is found.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_frontend_animations() {
		if ( is_admin() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || empty( $post->post_content ) ) {
			return;
		}

		// Only enqueue when the content actually contains animation classes.
		if ( false === strpos( $post->post_content, 'wpa-' ) ) {
			return;
		}

		wp_enqueue_style(
			'jarvis-ai-animations',
			JARVIS_AI_URL . 'assets/css/animations.css',
			array(),
			JARVIS_AI_VER
		);

		wp_enqueue_script(
			'jarvis-ai-animations',
			JARVIS_AI_URL . 'assets/js/animations.js',
			array(),
			JARVIS_AI_VER,
			true
		);
	}

	/**
	 * Enqueue A/B testing script on the frontend when active tests exist.
	 *
	 * Only loads the lightweight tracking script when at least one A/B test
	 * is active, passing test data via wpAgentAB localization.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function enqueue_ab_testing() {
		if ( is_admin() ) {
			return;
		}

		$tests = get_option( 'jarvis_ai_ab_tests', array() );

		if ( empty( $tests ) ) {
			return;
		}

		// Filter to only active tests.
		$active_tests = array_values(
			array_filter(
				$tests,
				function ( $test ) {
					return 'active' === ( $test['status'] ?? '' );
				}
			)
		);

		if ( empty( $active_tests ) ) {
			return;
		}

		// Only send minimal data to the frontend.
		$frontend_tests = array_map(
			function ( $test ) {
				return array( 'id' => $test['id'] );
			},
			$active_tests
		);

		wp_enqueue_script(
			'jarvis-ai-ab-testing',
			JARVIS_AI_URL . 'assets/js/ab-testing.js',
			array(),
			JARVIS_AI_VER,
			true
		);

		wp_localize_script(
			'jarvis-ai-ab-testing',
			'wpAgentAB',
			array(
				'restUrl' => rest_url( 'jarvis-ai/v1/ab-track' ),
				'tests'   => $frontend_tests,
			)
		);
	}

	/**
	 * Enqueue block editor assets for the Gutenberg sidebar.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_assets() {
		$asset_file = JARVIS_AI_DIR . 'build/editor.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		wp_enqueue_script(
			'jarvis-ai-editor',
			JARVIS_AI_URL . 'build/editor.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'jarvis-ai-editor',
			JARVIS_AI_URL . 'build/style-main.css',
			array(),
			$asset['version']
		);

		wp_localize_script(
			'jarvis-ai-editor',
			'jarvisAiData',
			array(
				'restUrl'   => rest_url( 'jarvis-ai/v1/' ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'hasApiKey' => ! empty( get_option( \JarvisAI\AI\Open_Router_Client::API_KEY_OPTION ) ),
				'userId'    => get_current_user_id(),
				'userName'  => wp_get_current_user()->display_name,
				'version'   => JARVIS_AI_VER,
				'adminUrl'  => admin_url(),
			)
		);
	}
}
