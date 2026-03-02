<?php
/**
 * Admin Menu.
 *
 * @package JarvisAI\Admin
 * @since 1.0.0
 */

namespace JarvisAI\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Menu
 *
 * @since 1.0.0
 */
class Admin_Menu {

	/**
	 * Instance
	 *
	 * @access private
	 * @var Admin_Menu|null Class Instance.
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return Admin_Menu Initialized object of class.
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
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Register admin menu and submenu pages.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'JARVIS AI', 'jarvis-ai' ),
			__( 'JARVIS AI', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai',
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			30
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'Dashboard', 'jarvis-ai' ),
			__( 'Dashboard', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'Settings', 'jarvis-ai' ),
			__( 'Settings', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'History', 'jarvis-ai' ),
			__( 'History', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai-history',
			array( $this, 'render_history' )
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'Schedules', 'jarvis-ai' ),
			__( 'Schedules', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai-schedules',
			array( $this, 'render_schedules' )
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'Capabilities', 'jarvis-ai' ),
			__( 'Capabilities', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai-capabilities',
			array( $this, 'render_capabilities' )
		);

		add_submenu_page(
			'jarvis-ai',
			__( 'Help', 'jarvis-ai' ),
			__( 'Help', 'jarvis-ai' ),
			'manage_options',
			'jarvis-ai-help',
			array( $this, 'render_help' )
		);
	}

	/**
	 * Render the Dashboard page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_dashboard() {
		?>
		<div id="jarvis-ai-dashboard" class="jarvis-ai-wrap"></div>
		<?php
	}

	/**
	 * Render the Settings page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings() {
		?>
		<div id="jarvis-ai-settings" class="jarvis-ai-wrap"></div>
		<?php
	}

	/**
	 * Render the History page.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function render_history() {
		?>
		<div id="jarvis-ai-history" class="jarvis-ai-wrap"></div>
		<?php
	}

	/**
	 * Render the Schedules page.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public function render_schedules() {
		?>
		<div id="jarvis-ai-schedules" class="jarvis-ai-wrap"></div>
		<?php
	}

	/**
	 * Render the Capabilities page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_capabilities() {
		?>
		<div id="jarvis-ai-capabilities" class="jarvis-ai-wrap"></div>
		<?php
	}

	/**
	 * Render the Help page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_help() {
		?>
		<div id="jarvis-ai-help" class="jarvis-ai-wrap"></div>
		<?php
	}
}
