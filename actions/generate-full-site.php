<?php
/**
 * Generate Full Site Action.
 *
 * Creates a complete multi-page website from a business type and page
 * specifications. Creates pages, sets a static homepage, and builds
 * a navigation menu linking all pages.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Generate_Full_Site
 *
 * @since 1.1.0
 */
class Generate_Full_Site implements Action_Interface {

	/**
	 * Maximum pages per site generation.
	 *
	 * @var int
	 */
	const MAX_PAGES = 20;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'generate_full_site';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Generate a complete multi-page website. Creates pages based on specifications, sets the homepage, '
			. 'and builds a navigation menu. Provide business type, page definitions (title and type for each), '
			. 'and optional brand info (colors, tone). Page types: home, about, services, contact, blog.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'business_type' => [
					'type'        => 'string',
					'description' => 'Type of business (e.g. "restaurant", "law firm", "saas startup").',
				],
				'pages'         => [
					'type'        => 'array',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'title'   => [ 'type' => 'string', 'description' => 'Page title.' ],
							'type'    => [
								'type' => 'string',
								'enum' => [ 'home', 'about', 'services', 'contact', 'blog' ],
								'description' => 'Page type for content scaffolding.',
							],
							'content' => [ 'type' => 'string', 'description' => 'Optional page content (block markup).' ],
						],
						'required' => [ 'title', 'type' ],
					],
					'description' => 'Array of pages to create.',
				],
				'brand_info'    => [
					'type'       => 'object',
					'properties' => [
						'colors' => [
							'type'        => 'array',
							'items'       => [ 'type' => 'string' ],
							'description' => 'Brand colors (hex values).',
						],
						'tone'   => [
							'type'        => 'string',
							'description' => 'Brand tone (e.g. "professional", "friendly", "luxury").',
						],
					],
					'description' => 'Optional brand information for content styling.',
				],
				'menu_name'     => [
					'type'        => 'string',
					'description' => 'Navigation menu name. Defaults to "Main Menu".',
				],
			],
			'required'   => [ 'business_type', 'pages' ],
		];
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'manage_options';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return true;
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$business_type = ! empty( $params['business_type'] ) ? sanitize_text_field( $params['business_type'] ) : '';
		$pages         = isset( $params['pages'] ) && is_array( $params['pages'] ) ? $params['pages'] : [];
		$menu_name     = ! empty( $params['menu_name'] ) ? sanitize_text_field( $params['menu_name'] ) : __( 'Main Menu', 'wp-agent' );

		if ( empty( $business_type ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Business type is required.', 'wp-agent' ),
			];
		}

		if ( empty( $pages ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'At least one page is required.', 'wp-agent' ),
			];
		}

		$pages = array_slice( $pages, 0, self::MAX_PAGES );

		$created_pages = [];
		$homepage_id   = 0;
		$errors        = [];

		// Create each page.
		foreach ( $pages as $index => $page_def ) {
			$title = ! empty( $page_def['title'] ) ? sanitize_text_field( $page_def['title'] ) : '';
			$type  = ! empty( $page_def['type'] ) ? sanitize_key( $page_def['type'] ) : 'home';

			if ( empty( $title ) ) {
				$errors[] = sprintf(
					/* translators: %d: page index */
					__( 'Page at index %d has no title, skipped.', 'wp-agent' ),
					$index
				);
				continue;
			}

			$content = ! empty( $page_def['content'] )
				? wp_kses_post( $page_def['content'] )
				: $this->get_placeholder_content( $type, $title, $business_type );

			$post_data = [
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'meta_input'   => [
					'_wp_agent_business_type' => $business_type,
					'_wp_agent_page_type'     => $type,
				],
			];

			$page_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $page_id ) ) {
				$errors[] = sprintf(
					/* translators: 1: page title, 2: error message */
					__( 'Failed to create "%1$s": %2$s', 'wp-agent' ),
					$title,
					$page_id->get_error_message()
				);
				continue;
			}

			$created_pages[] = [
				'id'    => $page_id,
				'title' => $title,
				'type'  => $type,
				'url'   => get_permalink( $page_id ),
			];

			// First 'home' type becomes the homepage.
			if ( 'home' === $type && 0 === $homepage_id ) {
				$homepage_id = $page_id;
			}
		}

		if ( empty( $created_pages ) ) {
			return [
				'success' => false,
				'data'    => [ 'errors' => $errors ],
				'message' => __( 'No pages were created.', 'wp-agent' ),
			];
		}

		// Set static homepage.
		if ( $homepage_id ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $homepage_id );

			// Set blog page if one was created.
			foreach ( $created_pages as $cp ) {
				if ( 'blog' === $cp['type'] ) {
					update_option( 'page_for_posts', $cp['id'] );
					break;
				}
			}
		}

		// Create navigation menu.
		$menu_id = $this->create_nav_menu( $menu_name, $created_pages );

		return [
			'success' => true,
			'data'    => [
				'business_type' => $business_type,
				'pages'         => $created_pages,
				'page_count'    => count( $created_pages ),
				'homepage_id'   => $homepage_id,
				'menu_id'       => $menu_id,
				'menu_name'     => $menu_name,
				'errors'        => $errors,
			],
			'message' => sprintf(
				/* translators: 1: page count, 2: business type */
				__( 'Created %1$d page(s) for %2$s site with navigation menu.', 'wp-agent' ),
				count( $created_pages ),
				$business_type
			),
		];
	}

	/**
	 * Get placeholder content for a page type.
	 *
	 * @since 1.1.0
	 *
	 * @param string $type          Page type.
	 * @param string $title         Page title.
	 * @param string $business_type Business type.
	 * @return string Block markup placeholder.
	 */
	private function get_placeholder_content( $type, $title, $business_type ) {
		$escaped_title    = esc_html( $title );
		$escaped_business = esc_html( $business_type );

		switch ( $type ) {
			case 'home':
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->'
					. '<!-- wp:paragraph -->'
					. '<p>' . sprintf(
						/* translators: %s: business type */
						esc_html__( 'Welcome to our %s. We are dedicated to providing exceptional service.', 'wp-agent' ),
						$escaped_business
					) . '</p>'
					. '<!-- /wp:paragraph -->';

			case 'about':
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->'
					. '<!-- wp:paragraph -->'
					. '<p>' . esc_html__( 'Learn more about our story, mission, and the team behind our success.', 'wp-agent' ) . '</p>'
					. '<!-- /wp:paragraph -->';

			case 'services':
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->'
					. '<!-- wp:paragraph -->'
					. '<p>' . esc_html__( 'Explore our range of services designed to meet your needs.', 'wp-agent' ) . '</p>'
					. '<!-- /wp:paragraph -->';

			case 'contact':
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->'
					. '<!-- wp:paragraph -->'
					. '<p>' . esc_html__( 'Get in touch with us. We would love to hear from you.', 'wp-agent' ) . '</p>'
					. '<!-- /wp:paragraph -->';

			case 'blog':
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->'
					. '<!-- wp:paragraph -->'
					. '<p>' . esc_html__( 'Stay updated with our latest news and insights.', 'wp-agent' ) . '</p>'
					. '<!-- /wp:paragraph -->';

			default:
				return '<!-- wp:heading {"level":1} -->'
					. '<h1 class="wp-block-heading">' . $escaped_title . '</h1>'
					. '<!-- /wp:heading -->';
		}
	}

	/**
	 * Create a navigation menu with links to all created pages.
	 *
	 * @since 1.1.0
	 *
	 * @param string $menu_name     Menu name.
	 * @param array  $created_pages Array of created page data.
	 * @return int|false Menu ID or false on failure.
	 */
	private function create_nav_menu( $menu_name, array $created_pages ) {
		// Delete existing menu with same name if it exists.
		$existing = wp_get_nav_menu_object( $menu_name );
		if ( $existing ) {
			wp_delete_nav_menu( $existing->term_id );
		}

		$menu_id = wp_create_nav_menu( $menu_name );

		if ( is_wp_error( $menu_id ) ) {
			return false;
		}

		$position = 0;
		foreach ( $created_pages as $page ) {
			++$position;
			wp_update_nav_menu_item( $menu_id, 0, [
				'menu-item-title'     => $page['title'],
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page['id'],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $position,
			] );
		}

		// Assign to primary/header menu location if available.
		$locations = get_registered_nav_menus();
		if ( ! empty( $locations ) ) {
			$current_locations = get_nav_menu_locations();
			$primary_keys      = [ 'primary', 'main', 'header', 'main-menu', 'primary-menu' ];

			foreach ( $primary_keys as $key ) {
				if ( isset( $locations[ $key ] ) ) {
					$current_locations[ $key ] = $menu_id;
					set_theme_mod( 'nav_menu_locations', $current_locations );
					break;
				}
			}
		}

		return $menu_id;
	}
}
