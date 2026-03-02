<?php
/**
 * Generate Sitemap Action.
 *
 * Checks sitemap status, generates a custom XML sitemap, or pings
 * search engines. Uses WP core sitemaps when available (WP 5.5+).
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Generate_Sitemap
 *
 * @since 1.1.0
 */
class Generate_Sitemap implements Action_Interface {

	public function get_name(): string {
		return 'generate_sitemap';
	}

	public function get_description(): string {
		return 'Check sitemap status, generate a sitemap XML file, or ping search engines (Google, Bing) '
			. 'with your sitemap URL. Uses WordPress core sitemaps (WP 5.5+) when available.';
	}

	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'operation' => array(
					'type'        => 'string',
					'enum'        => array( 'status', 'generate', 'ping' ),
					'description' => '"status" checks if sitemap exists, "generate" creates one, "ping" notifies search engines.',
				),
			),
			'required'   => array( 'operation' ),
		);
	}

	public function get_capabilities_required(): string {
		return 'manage_options';
	}

	public function is_reversible(): bool {
		return false;
	}

	public function execute( array $params ): array {
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'status':
				return $this->check_status();
			case 'generate':
				return $this->generate();
			case 'ping':
				return $this->ping_search_engines();
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "status", "generate", or "ping".', 'wp-agent' ),
				);
		}
	}

	private function check_status() {
		$has_core_sitemaps = function_exists( 'wp_sitemaps_get_server' );
		$sitemap_url       = home_url( '/wp-sitemap.xml' );
		$custom_sitemap    = ABSPATH . 'sitemap.xml';
		$has_custom        = file_exists( $custom_sitemap );

		// Check for popular SEO plugins that provide sitemaps.
		$seo_sitemap = '';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$seo_sitemap = 'Yoast SEO';
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$seo_sitemap = 'Rank Math';
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$seo_sitemap = 'All in One SEO';
		}

		// Count indexable content.
		$post_count      = wp_count_posts( 'post' );
		$page_count      = wp_count_posts( 'page' );
		$published_posts = isset( $post_count->publish ) ? $post_count->publish : 0;
		$published_pages = isset( $page_count->publish ) ? $page_count->publish : 0;

		return array(
			'success' => true,
			'data'    => array(
				'core_sitemaps_active' => $has_core_sitemaps,
				'core_sitemap_url'     => $has_core_sitemaps ? $sitemap_url : null,
				'custom_sitemap'       => $has_custom,
				'seo_plugin_sitemap'   => $seo_sitemap ?: null,
				'published_posts'      => $published_posts,
				'published_pages'      => $published_pages,
			),
			'message' => $has_core_sitemaps
				? sprintf( __( 'WordPress core sitemaps active at %s.', 'wp-agent' ), $sitemap_url )
				: __( 'No active sitemap found.', 'wp-agent' ),
		);
	}

	private function generate() {
		global $wpdb;

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_type, post_modified_gmt FROM {$wpdb->posts}
				WHERE post_status = %s AND post_type IN ('post', 'page')
				ORDER BY post_modified_gmt DESC
				LIMIT 50000",
				'publish'
			),
			ARRAY_A
		);

		$site_url = home_url();

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		// Homepage.
		$xml .= "<url>\n";
		$xml .= '  <loc>' . esc_url( $site_url ) . "</loc>\n";
		$xml .= '  <changefreq>daily</changefreq>' . "\n";
		$xml .= '  <priority>1.0</priority>' . "\n";
		$xml .= "</url>\n";

		foreach ( $posts as $post ) {
			$permalink = get_permalink( $post['ID'] );
			$lastmod   = gmdate( 'Y-m-d', strtotime( $post['post_modified_gmt'] ) );
			$priority  = 'page' === $post['post_type'] ? '0.8' : '0.6';

			$xml .= "<url>\n";
			$xml .= '  <loc>' . esc_url( $permalink ) . "</loc>\n";
			$xml .= "  <lastmod>{$lastmod}</lastmod>\n";
			$xml .= "  <priority>{$priority}</priority>\n";
			$xml .= "</url>\n";
		}

		$xml .= '</urlset>';

		$filepath = ABSPATH . 'sitemap.xml';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$written = file_put_contents( $filepath, $xml );

		if ( false === $written ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to write sitemap.xml. Check file permissions.', 'wp-agent' ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'url'       => home_url( '/sitemap.xml' ),
				'url_count' => count( $posts ) + 1,
				'size'      => size_format( $written ),
			),
			'message' => sprintf(
				/* translators: 1: URL count, 2: file size */
				__( 'Sitemap generated with %1$d URLs (%2$s).', 'wp-agent' ),
				count( $posts ) + 1,
				size_format( $written )
			),
		);
	}

	private function ping_search_engines() {
		$sitemap_url = home_url( '/wp-sitemap.xml' );

		// Use custom sitemap if it exists.
		if ( file_exists( ABSPATH . 'sitemap.xml' ) ) {
			$sitemap_url = home_url( '/sitemap.xml' );
		}

		$engines = array(
			'Google' => 'https://www.google.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
			'Bing'   => 'https://www.bing.com/ping?sitemap=' . rawurlencode( $sitemap_url ),
		);

		$results = array();
		foreach ( $engines as $name => $ping_url ) {
			$response = wp_remote_get( $ping_url, array( 'timeout' => 10 ) );
			$code     = wp_remote_retrieve_response_code( $response );

			$results[ $name ] = array(
				'status' => ( $code >= 200 && $code < 300 ) ? 'success' : 'failed',
				'code'   => $code,
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'sitemap_url' => $sitemap_url,
				'results'     => $results,
			),
			'message' => __( 'Pinged search engines with sitemap URL.', 'wp-agent' ),
		);
	}
}
