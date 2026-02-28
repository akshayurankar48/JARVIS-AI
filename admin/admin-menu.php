<?php
/**
 * Admin Menu.
 *
 * @package WPAgent\Admin
 * @since 1.0.0
 */

namespace WPAgent\Admin;

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
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Register admin menu page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WP Agent', 'wp-agent' ),
			__( 'WP Agent', 'wp-agent' ),
			'manage_options',
			'wp-agent',
			[ $this, 'render_page' ],
			'dashicons-format-chat',
			30
		);
	}

	/**
	 * Render the admin page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_page() {
		?>
		<div id="wp-agent-settings" class="wp-agent-settings"></div>
		<?php
	}
}
