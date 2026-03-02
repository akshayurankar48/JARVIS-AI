<?php
/**
 * Analyze Reference Site Action.
 *
 * Fetches and analyzes a reference website URL to extract design elements
 * including colors, fonts, section structure, layout patterns, and image usage.
 * Useful for replicating or drawing inspiration from existing sites.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

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
				'message' => __( 'A valid URL is required.', 'jarvis-ai' ),
			);
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Only http and https URLs are allowed.', 'jarvis-ai' ),
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
				'user-agent'          => 'JARVIS-AI/1.0 (WordPress)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to fetch URL: %s', 'jarvis-ai' ),
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
					__( 'URL returned HTTP %d.', 'jarvis-ai' ),
					$response_code
				),
			);
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'The URL returned an empty response.', 'jarvis-ai' ),
			);
		}

		$analysis = array( 'url' => $url );

		if ( in_array( 'colors', $extract_items, true ) ) {
			$raw_colors          = $this->extract_colors( $html );
			$analysis['palette'] = $this->classify_colors( $raw_colors );
		}

		if ( in_array( 'fonts', $extract_items, true ) ) {
			$analysis['typography'] = $this->extract_fonts( $html );
		}

		if ( in_array( 'sections', $extract_items, true ) ) {
			$classified              = $this->extract_sections( $html );
			$analysis['sections']    = $classified;
			$analysis['suggestions'] = $this->suggest_patterns( $classified );
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
				__( 'Successfully analyzed design elements from %s.', 'jarvis-ai' ),
				$url
			),
		);
	}

	/**
	 * Classify raw colors into design roles (primary, accent, backgrounds, text).
	 *
	 * Converts hex colors to HSL, clusters by hue distance, and assigns
	 * roles based on saturation and lightness thresholds.
	 *
	 * @since 1.2.0
	 *
	 * @param array $colors Array of hex color strings.
	 * @return array Classified palette with role keys and all_colors.
	 */
	private function classify_colors( array $colors ) {
		if ( empty( $colors ) ) {
			return array(
				'primary'    => '#6366f1',
				'accent'     => '#06b6d4',
				'dark_bg'    => '#0f172a',
				'light_bg'   => '#f8fafc',
				'text_dark'  => '#1e293b',
				'text_muted' => '#64748b',
				'all_colors' => array(),
			);
		}

		// Convert all colors to HSL for analysis.
		$hsl_data = array();
		foreach ( $colors as $hex ) {
			$hsl = $this->hex_to_hsl( $hex );
			if ( $hsl ) {
				$hsl_data[] = array(
					'hex' => $hex,
					'h'   => $hsl[0],
					's'   => $hsl[1],
					'l'   => $hsl[2],
				);
			}
		}

		if ( empty( $hsl_data ) ) {
			return array(
				'primary'    => '#6366f1',
				'accent'     => '#06b6d4',
				'dark_bg'    => '#0f172a',
				'light_bg'   => '#f8fafc',
				'text_dark'  => '#1e293b',
				'text_muted' => '#64748b',
				'all_colors' => $colors,
			);
		}

		$palette = array(
			'primary'    => null,
			'accent'     => null,
			'dark_bg'    => null,
			'light_bg'   => null,
			'text_dark'  => null,
			'text_muted' => null,
			'all_colors' => $colors,
		);

		// Sort by saturation descending to find primary/accent first.
		$by_saturation = $hsl_data;
		usort(
			$by_saturation,
			function ( $a, $b ) {
				return $b['s'] - $a['s'];
			}
		);

		// Primary = highest saturation color.
		foreach ( $by_saturation as $c ) {
			if ( $c['s'] >= 20 && $c['l'] > 10 && $c['l'] < 90 ) {
				$palette['primary'] = $c['hex'];
				break;
			}
		}

		// Accent = second-highest saturation with different hue (30+ degrees apart).
		$primary_hue = null;
		if ( $palette['primary'] ) {
			foreach ( $hsl_data as $c ) {
				if ( $c['hex'] === $palette['primary'] ) {
					$primary_hue = $c['h'];
					break;
				}
			}
		}

		foreach ( $by_saturation as $c ) {
			if ( $c['hex'] === $palette['primary'] ) {
				continue;
			}
			if ( $c['s'] >= 20 && $c['l'] > 10 && $c['l'] < 90 ) {
				if ( null === $primary_hue || $this->hue_distance( $c['h'], $primary_hue ) >= 30 ) {
					$palette['accent'] = $c['hex'];
					break;
				}
			}
		}

		// Sort by lightness for background/text roles.
		$by_lightness = $hsl_data;
		usort(
			$by_lightness,
			function ( $a, $b ) {
				return $a['l'] - $b['l'];
			}
		);

		// Dark background = darkest color (L < 20).
		foreach ( $by_lightness as $c ) {
			if ( $c['l'] < 20 ) {
				$palette['dark_bg'] = $c['hex'];
				break;
			}
		}

		// Light background = lightest color (L > 90).
		$reversed = array_reverse( $by_lightness );
		foreach ( $reversed as $c ) {
			if ( $c['l'] > 90 ) {
				$palette['light_bg'] = $c['hex'];
				break;
			}
		}

		// Text dark = dark but not darkest (L 10-30).
		foreach ( $by_lightness as $c ) {
			if ( $c['hex'] === $palette['dark_bg'] ) {
				continue;
			}
			if ( $c['l'] >= 10 && $c['l'] <= 30 ) {
				$palette['text_dark'] = $c['hex'];
				break;
			}
		}

		// Text muted = mid-lightness (L 35-65).
		foreach ( $by_lightness as $c ) {
			if ( $c['l'] >= 35 && $c['l'] <= 65 ) {
				$palette['text_muted'] = $c['hex'];
				break;
			}
		}

		// Fill remaining nulls with sensible defaults.
		$defaults = array(
			'primary'    => '#6366f1',
			'accent'     => '#06b6d4',
			'dark_bg'    => '#0f172a',
			'light_bg'   => '#f8fafc',
			'text_dark'  => '#1e293b',
			'text_muted' => '#64748b',
		);
		foreach ( $defaults as $key => $default ) {
			if ( null === $palette[ $key ] ) {
				$palette[ $key ] = $default;
			}
		}

		return $palette;
	}

	/**
	 * Convert a hex color to HSL values.
	 *
	 * @since 1.2.0
	 *
	 * @param string $hex Hex color string (e.g. '#6366f1').
	 * @return array|null Array of [hue, saturation, lightness] or null if invalid.
	 */
	private function hex_to_hsl( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( 6 !== strlen( $hex ) ) {
			return null;
		}

		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

		$max   = max( $r, $g, $b );
		$min   = min( $r, $g, $b );
		$delta = $max - $min;

		$l = ( $max + $min ) / 2;

		if ( 0.0 === $delta ) {
			return array( 0, 0, (int) round( $l * 100 ) );
		}

		$s = $delta / ( 1 - abs( 2 * $l - 1 ) );

		if ( $max === $r ) {
			$h = 60 * fmod( ( $g - $b ) / $delta, 6 );
		} elseif ( $max === $g ) {
			$h = 60 * ( ( $b - $r ) / $delta + 2 );
		} else {
			$h = 60 * ( ( $r - $g ) / $delta + 4 );
		}

		if ( $h < 0 ) {
			$h += 360;
		}

		return array(
			(int) round( $h ),
			(int) round( $s * 100 ),
			(int) round( $l * 100 ),
		);
	}

	/**
	 * Calculate the angular distance between two hue values.
	 *
	 * @since 1.2.0
	 *
	 * @param int $h1 First hue (0-360).
	 * @param int $h2 Second hue (0-360).
	 * @return int Angular distance (0-180).
	 */
	private function hue_distance( $h1, $h2 ) {
		$diff = abs( $h1 - $h2 );
		return $diff > 180 ? 360 - $diff : $diff;
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
	 * Extract font-family declarations with heading/body hierarchy detection.
	 *
	 * Parses style blocks to determine which font is used for headings
	 * vs body text. Returns a structured typography object.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Structured typography data with heading_font, body_font, all_fonts.
	 */
	private function extract_fonts( $html ) {
		$all_fonts    = array();
		$heading_font = null;
		$body_font    = null;

		// Extract from style blocks and inline styles.
		$style_content = '';
		if ( preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $style_matches ) ) {
			$style_content = implode( ' ', $style_matches[1] );
		}
		if ( preg_match_all( '/style=["\']([^"\']+)["\']/i', $html, $inline_matches ) ) {
			$style_content .= ' ' . implode( ' ', $inline_matches[1] );
		}

		// Detect heading font: look for h1-h6 selector rules.
		$heading_pattern = '/(?:h[1-3]|\.heading|\.title)[^{]*\{[^}]*font-family\s*:\s*([^;}"]+)/i';
		if ( preg_match( $heading_pattern, $style_content, $h_match ) ) {
			$heading_font = $this->extract_primary_font( $h_match[1] );
		}

		// Detect body font: look for body/p/main selector rules.
		$body_pattern = '/(?:body|html|\.body|p\s|main)[^{]*\{[^}]*font-family\s*:\s*([^;}"]+)/i';
		if ( preg_match( $body_pattern, $style_content, $b_match ) ) {
			$body_font = $this->extract_primary_font( $b_match[1] );
		}

		// Extract all font-family declarations.
		if ( preg_match_all( '/font-family\s*:\s*([^;}"]+)/i', $style_content, $font_matches ) ) {
			foreach ( $font_matches[1] as $font_string ) {
				$font = $this->extract_primary_font( $font_string );
				if ( $font ) {
					$all_fonts[] = $font;
				}
			}
		}

		// Check Google Fonts links.
		$google_fonts = array();
		if ( preg_match_all( '/fonts\.googleapis\.com\/css2?\?[^"\']*family=([^"\']+)/i', $html, $gf_matches ) ) {
			foreach ( $gf_matches[1] as $family_param ) {
				$decoded  = urldecode( $family_param );
				$families = preg_split( '/[|&]family=/', $decoded );
				foreach ( $families as $family ) {
					$parts = explode( ':', $family );
					$name  = str_replace( '+', ' ', trim( $parts[0] ) );
					if ( ! empty( $name ) ) {
						$all_fonts[]    = sanitize_text_field( $name );
						$google_fonts[] = sanitize_text_field( str_replace( ' ', '+', $family ) );
					}
				}
			}
		}

		$all_fonts = array_values( array_unique( $all_fonts ) );

		// If heading/body fonts not detected from CSS rules, infer from font list.
		if ( ! $heading_font && count( $all_fonts ) >= 1 ) {
			$heading_font = $all_fonts[0];
		}
		if ( ! $body_font && count( $all_fonts ) >= 2 ) {
			$body_font = $all_fonts[1];
		} elseif ( ! $body_font && count( $all_fonts ) >= 1 ) {
			$body_font = $all_fonts[0];
		}

		return array(
			'heading_font' => $heading_font,
			'body_font'    => $body_font,
			'all_fonts'    => $all_fonts,
			'google_fonts' => array_values( array_unique( $google_fonts ) ),
		);
	}

	/**
	 * Extract the primary (first non-generic) font from a font-family string.
	 *
	 * @since 1.2.0
	 *
	 * @param string $font_string CSS font-family value (e.g. "'Inter', sans-serif").
	 * @return string|null Primary font name or null if only generic fonts.
	 */
	private function extract_primary_font( $font_string ) {
		$generics  = array( 'serif', 'sans-serif', 'monospace', 'cursive', 'fantasy', 'system-ui', 'inherit', 'initial', 'ui-sans-serif', 'ui-serif', 'ui-monospace' );
		$font_list = array_map( 'trim', explode( ',', $font_string ) );

		foreach ( $font_list as $font ) {
			$font = trim( $font, " \t\n\r\0\x0B'\"" );
			if ( ! empty( $font ) && ! in_array( strtolower( $font ), $generics, true ) ) {
				return sanitize_text_field( $font );
			}
		}

		return null;
	}

	/**
	 * Extract and classify section structure from HTML.
	 *
	 * Identifies section types (hero, features, testimonials, pricing, CTA, etc.)
	 * based on heading text, surrounding DOM signals, and position on the page.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html Raw HTML.
	 * @return array Classified section structure with type, heading, and signals.
	 */
	private function extract_sections( $html ) {
		$sections = array();

		// Remove script and style content.
		$clean_html = preg_replace( '/<(script|style|noscript)[^>]*>.*?<\/\1>/is', '', $html );

		// Try to find <section> or major div blocks with headings.
		$section_blocks = array();
		if ( preg_match_all( '/<(?:section|article)[^>]*>(.*?)<\/(?:section|article)>/is', $clean_html, $sec_matches ) ) {
			$section_blocks = $sec_matches[1];
		}

		// Also extract headings globally as fallback.
		$heading_sections = array();
		if ( preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $clean_html, $h_matches, PREG_SET_ORDER ) ) {
			foreach ( array_slice( $h_matches, 0, 40 ) as $match ) {
				$text = trim( wp_strip_all_tags( $match[2] ) );
				if ( ! empty( $text ) ) {
					$heading_sections[] = array(
						'level' => (int) $match[1],
						'text'  => substr( $text, 0, 200 ),
					);
				}
			}
		}

		// Classify section blocks if available.
		if ( ! empty( $section_blocks ) ) {
			$index = 0;
			foreach ( array_slice( $section_blocks, 0, 20 ) as $block ) {
				$heading = '';
				if ( preg_match( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $block, $h_match ) ) {
					$heading = trim( wp_strip_all_tags( $h_match[1] ) );
				}

				$type    = $this->classify_section_type( $heading, $block, $index );
				$has_cta = (bool) preg_match( '/<(?:a|button)[^>]*>.*?<\/(?:a|button)>/is', $block );

				$section = array(
					'type'    => $type,
					'heading' => substr( $heading, 0, 200 ),
					'has_cta' => $has_cta,
				);

				// Count items for grid-like sections.
				if ( in_array( $type, array( 'features', 'pricing', 'testimonials', 'team', 'logos' ), true ) ) {
					$item_count  = 0;
					$item_count += preg_match_all( '/<(?:li|article)[^>]*>/i', $block );
					if ( 0 === $item_count ) {
						$item_count = preg_match_all( '/<div[^>]*class=[^>]*(?:card|item|col|feature|member|plan|logo)[^>]*>/i', $block );
					}
					if ( $item_count > 0 ) {
						$section['item_count'] = $item_count;
					}
				}

				$sections[] = $section;
				++$index;
			}
		} elseif ( ! empty( $heading_sections ) ) {
			// Fallback: classify from headings only.
			foreach ( $heading_sections as $index => $h ) {
				$type       = $this->classify_section_type( $h['text'], '', $index );
				$sections[] = array(
					'type'    => $type,
					'heading' => $h['text'],
					'has_cta' => false,
				);
			}
		}

		return $sections;
	}

	/**
	 * Classify a section's type based on heading text, content, and position.
	 *
	 * @since 1.2.0
	 *
	 * @param string $heading Heading text.
	 * @param string $content Section HTML content.
	 * @param int    $index   Section position index (0-based).
	 * @return string Section type identifier.
	 */
	private function classify_section_type( $heading, $content, $index ) {
		$heading_lower = strtolower( $heading );
		$content_lower = strtolower( $content );

		// First section is almost always the hero.
		if ( 0 === $index ) {
			return 'hero';
		}

		// Keyword-based classification.
		$type_keywords = array(
			'pricing'      => array( 'pricing', 'plans', 'price', 'subscription', 'tier', 'per month', '/mo', '/year' ),
			'testimonials' => array( 'testimonial', 'review', 'customer', 'what people say', 'what our', 'client', 'trust', 'loved by' ),
			'features'     => array( 'feature', 'benefit', 'why choose', 'what we offer', 'capabilities', 'advantages', 'everything you need' ),
			'faq'          => array( 'faq', 'frequently asked', 'questions', 'common questions' ),
			'cta'          => array( 'get started', 'sign up', 'try it', 'start free', 'join', 'ready to', 'start your', 'take the' ),
			'stats'        => array( 'numbers', 'stats', 'metrics', 'by the numbers', 'achievements', 'impact' ),
			'team'         => array( 'team', 'our people', 'meet the', 'leadership', 'founders' ),
			'logos'        => array( 'trusted by', 'partners', 'clients', 'companies', 'brands', 'backed by', 'as seen' ),
			'gallery'      => array( 'gallery', 'portfolio', 'our work', 'projects', 'showcase' ),
			'contact'      => array( 'contact', 'get in touch', 'reach us', 'reach out', 'talk to us', 'location', 'address' ),
			'about'        => array( 'about', 'our story', 'who we are', 'mission', 'our vision' ),
			'newsletter'   => array( 'newsletter', 'subscribe', 'stay updated', 'email list' ),
			'footer'       => array( 'footer', 'copyright', 'all rights reserved' ),
			'process'      => array( 'how it works', 'our process', 'steps', 'getting started', 'how to', 'simple steps', 'workflow' ),
			'services'     => array( 'services', 'what we do', 'our solutions', 'offerings', 'expertise', 'specialties' ),
		);

		// Check heading first (more reliable).
		foreach ( $type_keywords as $type => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( false !== strpos( $heading_lower, $keyword ) ) {
					return $type;
				}
			}
		}

		// Check content for signals.
		if ( ! empty( $content ) ) {
			foreach ( $type_keywords as $type => $keywords ) {
				$matches = 0;
				foreach ( $keywords as $keyword ) {
					if ( false !== strpos( $content_lower, $keyword ) ) {
						++$matches;
					}
				}
				if ( $matches >= 2 ) {
					return $type;
				}
			}
		}

		return 'content';
	}

	/**
	 * Suggest matching pattern IDs for each classified section.
	 *
	 * Maps section types to pattern categories and returns the best
	 * matching pattern IDs from the library.
	 *
	 * @since 1.2.0
	 *
	 * @param array $sections Classified sections from extract_sections().
	 * @return array Array of section type => suggested pattern IDs.
	 */
	private function suggest_patterns( array $sections ) {
		// Section type → pattern category/ID mapping (IDs match filenames minus .json).
		$type_to_patterns = array(
			'hero'         => array( 'hero-dark', 'hero-gradient', 'hero-aurora', 'hero-split', 'hero-cover', 'hero-minimal', 'hero-glass', 'hero-video-bg' ),
			'features'     => array( 'features-bento', 'features-3col', 'features-4col', 'features-glass', 'features-icons' ),
			'testimonials' => array( 'testimonials-cards', 'testimonials-modern', 'testimonials-wall', 'testimonials-quote', 'testimonials-single', 'testimonials-slider' ),
			'pricing'      => array( 'pricing-3tier', 'pricing-glass', 'pricing-simple', 'pricing-comparison', 'pricing-toggle' ),
			'cta'          => array( 'cta-aurora', 'cta-gradient', 'cta-banner', 'cta-split' ),
			'faq'          => array( 'faq-accordion', 'faq-modern', 'faq-two-col', 'faq-search' ),
			'stats'        => array( 'stats-counters', 'stats-gradient', 'stats-animated', 'stats-cards', 'stats-minimal' ),
			'team'         => array( 'team-grid', 'team-cards', 'team-carousel', 'team-minimal' ),
			'logos'        => array( 'logo-bar', 'logo-bar-dark', 'logo-bar-glass', 'logo-bar-light' ),
			'gallery'      => array( 'gallery-grid', 'gallery-masonry', 'gallery-carousel', 'gallery-lightbox' ),
			'contact'      => array( 'contact-split', 'contact-info', 'contact-map' ),
			'about'        => array( 'content-media-text', 'content-two-col', 'content-glass-cards' ),
			'newsletter'   => array( 'newsletter-banner', 'newsletter-inline', 'newsletter-signup' ),
			'footer'       => array( 'footer-columns', 'footer-dark', 'footer-minimal', 'footer-simple', 'footer-cta' ),
			'content'      => array( 'content-media-text', 'content-two-col', 'content-glass-cards' ),
			'process'      => array( 'process-steps', 'process-modern', 'timeline-vertical' ),
			'services'     => array( 'services-cards', 'services-icons', 'services-detailed', 'services-tabs' ),
		);

		// Validate against actual patterns in the library.
		$manager     = \JarvisAI\Patterns\Pattern_Manager::get_instance();
		$all_ids     = array_map(
			function ( $p ) {
				return $p['id'];
			},
			$manager->list_patterns()
		);
		$valid_ids   = array_flip( $all_ids );
		$suggestions = array();

		foreach ( $sections as $section ) {
			$type     = isset( $section['type'] ) ? $section['type'] : 'content';
			$patterns = isset( $type_to_patterns[ $type ] ) ? $type_to_patterns[ $type ] : $type_to_patterns['content'];

			// Filter to only patterns that actually exist in the library.
			$valid_patterns = array_filter(
				$patterns,
				function ( $id ) use ( $valid_ids ) {
					return isset( $valid_ids[ $id ] );
				}
			);

			$suggestions[] = array(
				'section_type'       => $type,
				'heading'            => isset( $section['heading'] ) ? $section['heading'] : '',
				'suggested_patterns' => array_values( $valid_patterns ),
			);
		}

		return $suggestions;
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
