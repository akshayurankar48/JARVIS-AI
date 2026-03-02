<?php
/**
 * Analyze Reference Site Action.
 *
 * Fetches and analyzes a reference website URL to extract design elements
 * including colors, fonts, section structure, layout patterns, and image usage.
 * Useful for replicating or drawing inspiration from existing sites.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Analyze_Reference_Site
 *
 * @since 1.1.0
 */
class Analyze_Reference_Site implements Action_Interface {

	/**
	 * Maximum response body size in bytes (1 MB).
	 *
	 * @var int
	 */
	const MAX_BODY_SIZE = 1024 * 1024;

	/**
	 * Request timeout in seconds.
	 *
	 * @var int
	 */
	const REQUEST_TIMEOUT = 20;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'analyze_reference_site';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Analyze a reference website URL to extract design elements (colors, fonts, section structure, layout). '
			. 'Use this before building pages to replicate or draw inspiration from existing designs. '
			. 'Returns structured data about the site\'s visual design choices.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.1.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'url'     => array(
					'type'        => 'string',
					'description' => 'The full URL to analyze (must be http or https).',
				),
				'extract' => array(
					'type'        => 'array',
					'items'       => array(
						'type' => 'string',
						'enum' => array( 'colors', 'fonts', 'sections', 'layout', 'images' ),
					),
					'description' => 'Design elements to extract. Defaults to all: colors, fonts, sections, layout, images.',
				),
			),
			'required'   => array( 'url' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_posts';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
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
		$url = isset( $params['url'] ) ? esc_url_raw( trim( $params['url'] ) ) : '';

		if ( empty( $url ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'A valid URL is required.', 'wp-agent' ),
			);
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Only http and https URLs are allowed.', 'wp-agent' ),
			);
		}

		$extract_items = array( 'colors', 'fonts', 'sections', 'layout', 'images' );
		if ( ! empty( $params['extract'] ) && is_array( $params['extract'] ) ) {
			$extract_items = array_intersect( array_map( 'sanitize_key', $params['extract'] ), $extract_items );
			if ( empty( $extract_items ) ) {
				$extract_items = array( 'colors', 'fonts', 'sections', 'layout', 'images' );
			}
		}

		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => self::REQUEST_TIMEOUT,
				'redirection'         => 3,
				'reject_unsafe_urls'  => true,
				'limit_response_size' => self::MAX_BODY_SIZE,
				'user-agent'          => 'WP-Agent/1.0 (WordPress)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to fetch URL: %s', 'wp-agent' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 400 ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'URL returned HTTP %d.', 'wp-agent' ),
					$response_code
				),
			);
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'The URL returned an empty response.', 'wp-agent' ),
			);
		}

		$analysis = array( 'url' => $url );

		if ( in_array( 'colors', $extract_items, true ) ) {
			$analysis['colors'] = $this->extract_colors( $html );
		}

		if ( in_array( 'fonts', $extract_items, true ) ) {
			$analysis['fonts'] = $this->extract_fonts( $html );
		}

		if ( in_array( 'sections', $extract_items, true ) ) {
			$analysis['sections'] = $this->extract_sections( $html );
		}

		if ( in_array( 'layout', $extract_items, true ) ) {
			$analysis['layout'] = $this->extract_layout( $html );
		}

		if ( in_array( 'images', $extract_items, true ) ) {
			$analysis['images'] = $this->extract_images( $html );
		}

		return array(
			'success' => true,
			'data'    => $analysis,
			'message' => sprintf(
				/* translators: %s: URL */
				__( 'Successfully analyzed design elements from %s.', 'wp-agent' ),
				$url
			),
		);
	}

	/**
	 * Extract color hex codes from HTML inline styles and embedded CSS.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Unique color hex codes found.
	 */
	private function extract_colors( $html ) {
		$colors = array();

		// Extract from inline styles and style blocks.
		$style_content = '';
		if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches ) ) {
			$style_content = implode( ' ', $style_matches[1] );
		}

		// Also check inline style attributes.
		if ( preg_match_all( '/style=["\']([^"\']+)["\']/i', $html, $inline_matches ) ) {
			$style_content .= ' ' . implode( ' ', $inline_matches[1] );
		}

		// Extract hex colors (#RGB, #RRGGBB).
		if ( preg_match_all( '/#([0-9a-fA-F]{3,8})\b/', $style_content, $hex_matches ) ) {
			foreach ( $hex_matches[0] as $hex ) {
				$hex = strtolower( $hex );
				// Normalize 3-char to 6-char.
				if ( 4 === strlen( $hex ) ) {
					$hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
				}
				if ( 7 === strlen( $hex ) ) {
					$colors[] = $hex;
				}
			}
		}

		// Extract rgb/rgba colors.
		if ( preg_match_all( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $style_content, $rgb_matches, PREG_SET_ORDER ) ) {
			foreach ( $rgb_matches as $match ) {
				$hex      = sprintf( '#%02x%02x%02x', (int) $match[1], (int) $match[2], (int) $match[3] );
				$colors[] = $hex;
			}
		}

		// Deduplicate and limit.
		$colors = array_values( array_unique( $colors ) );

		// Filter out common black/white/transparent.
		$filtered = array_filter(
			$colors,
			function ( $c ) {
				return ! in_array( $c, array( '#000000', '#ffffff', '#fff', '#000' ), true );
			}
		);

		// Return unique colors, keep black/white at end if nothing else.
		$result = array_values( $filtered );
		if ( empty( $result ) ) {
			$result = $colors;
		}

		return array_slice( $result, 0, 20 );
	}

	/**
	 * Extract font-family declarations from HTML.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Unique font families found.
	 */
	private function extract_fonts( $html ) {
		$fonts = array();

		// Extract from style blocks and inline styles.
		$style_content = '';
		if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches ) ) {
			$style_content = implode( ' ', $style_matches[1] );
		}
		if ( preg_match_all( '/style=["\']([^"\']+)["\']/i', $html, $inline_matches ) ) {
			$style_content .= ' ' . implode( ' ', $inline_matches[1] );
		}

		// Extract font-family declarations.
		if ( preg_match_all( '/font-family\s*:\s*([^;}"]+)/i', $style_content, $font_matches ) ) {
			foreach ( $font_matches[1] as $font_string ) {
				$font_list = array_map( 'trim', explode( ',', $font_string ) );
				foreach ( $font_list as $font ) {
					$font = trim( $font, " \t\n\r\0\x0B'\"" );
					if ( ! empty( $font ) && ! in_array( strtolower( $font ), array( 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'inherit', 'initial' ), true ) ) {
						$fonts[] = sanitize_text_field( $font );
					}
				}
			}
		}

		// Check Google Fonts links.
		if ( preg_match_all( '/fonts\.googleapis\.com\/css[^"\']*family=([^"\'&]+)/i', $html, $gf_matches ) ) {
			foreach ( $gf_matches[1] as $family_param ) {
				$families = explode( '|', urldecode( $family_param ) );
				foreach ( $families as $family ) {
					$font    = explode( ':', $family )[0];
					$font    = str_replace( '+', ' ', $font );
					$fonts[] = sanitize_text_field( trim( $font ) );
				}
			}
		}

		return array_values( array_unique( $fonts ) );
	}

	/**
	 * Extract section structure from headings.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Section structure with heading hierarchy.
	 */
	private function extract_sections( $html ) {
		$sections = array();

		// Remove script and style content.
		$clean_html = preg_replace( '/<(script|style|noscript)[^>]*>.*?<\/\1>/is', '', $html );

		if ( preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $clean_html, $matches, PREG_SET_ORDER ) ) {
			foreach ( array_slice( $matches, 0, 40 ) as $match ) {
				$text = trim( wp_strip_all_tags( $match[2] ) );
				if ( ! empty( $text ) ) {
					$sections[] = array(
						'level' => (int) $match[1],
						'text'  => substr( $text, 0, 200 ),
					);
				}
			}
		}

		return $sections;
	}

	/**
	 * Detect overall layout type from HTML structure.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Layout analysis data.
	 */
	private function extract_layout( $html ) {
		$layout = array(
			'has_header'  => (bool) preg_match( '/<header[^>]*>/i', $html ),
			'has_footer'  => (bool) preg_match( '/<footer[^>]*>/i', $html ),
			'has_sidebar' => (bool) preg_match( '/<aside[^>]*>/i', $html ) || (bool) preg_match( '/sidebar/i', $html ),
			'has_nav'     => (bool) preg_match( '/<nav[^>]*>/i', $html ),
		);

		// Count major sections.
		$layout['section_count'] = preg_match_all( '/<section[^>]*>/i', $html );

		// Detect grid/flex usage.
		$layout['uses_grid']    = (bool) preg_match( '/display\s*:\s*grid/i', $html );
		$layout['uses_flexbox'] = (bool) preg_match( '/display\s*:\s*flex/i', $html );

		// Detect layout type.
		if ( $layout['has_sidebar'] ) {
			$layout['type'] = 'sidebar';
		} elseif ( $layout['section_count'] > 3 ) {
			$layout['type'] = 'landing-page';
		} else {
			$layout['type'] = 'single-column';
		}

		return $layout;
	}

	/**
	 * Extract image information from HTML.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Image analysis data.
	 */
	private function extract_images( $html ) {
		$data = array(
			'total_count'    => 0,
			'with_alt'       => 0,
			'without_alt'    => 0,
			'has_lazy_load'  => false,
			'sample_sources' => array(),
		);

		if ( preg_match_all( '/<img[^>]+>/i', $html, $img_matches ) ) {
			$data['total_count'] = count( $img_matches[0] );

			foreach ( array_slice( $img_matches[0], 0, 50 ) as $img_tag ) {
				// Check alt text.
				if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img_tag, $alt_match ) ) {
					if ( ! empty( trim( $alt_match[1] ) ) ) {
						++$data['with_alt'];
					} else {
						++$data['without_alt'];
					}
				} else {
					++$data['without_alt'];
				}

				// Check lazy loading.
				if ( preg_match( '/loading=["\']lazy["\']/i', $img_tag ) ) {
					$data['has_lazy_load'] = true;
				}

				// Collect sample sources (first 5).
				if ( count( $data['sample_sources'] ) < 5 && preg_match( '/src=["\']([^"\']+)["\']/i', $img_tag, $src_match ) ) {
					$data['sample_sources'][] = esc_url( $src_match[1] );
				}
			}
		}

		return $data;
	}
}
