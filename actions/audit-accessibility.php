<?php
/**
 * Audit Accessibility Action.
 *
 * Audits WordPress post content for common accessibility issues including
 * missing alt text, heading hierarchy violations, empty links, missing
 * form labels, and potential color contrast problems.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Audit_Accessibility
 *
 * @since 1.1.0
 */
class Audit_Accessibility implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'audit_accessibility';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Audit a post or page for accessibility issues. Operations: "audit" checks for missing alt text, '
			. 'heading hierarchy violations, empty links, missing form labels, and color contrast issues. '
			. '"check_element" inspects a specific block. "fix" applies suggested fixes to post content. '
			. 'Returns issues with severity (critical/warning/info) and suggested fixes.';
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
				'operation' => [
					'type'        => 'string',
					'enum'        => [ 'audit', 'check_element', 'fix' ],
					'description' => 'Operation to perform.',
				],
				'post_id'   => [
					'type'        => 'integer',
					'description' => 'Post or page ID to audit.',
				],
				'block_index' => [
					'type'        => 'integer',
					'description' => 'Block index to check (for "check_element" operation).',
				],
				'fix_type'  => [
					'type'        => 'string',
					'enum'        => [ 'alt_text', 'heading_hierarchy', 'empty_links', 'all' ],
					'description' => 'Type of fix to apply (for "fix" operation). Defaults to "all".',
				],
			],
			'required'   => [ 'operation', 'post_id' ],
		];
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
		$operation = $params['operation'] ?? '';
		$post_id   = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( ! $post_id ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Post ID is required.', 'wp-agent' ),
			];
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Post not found.', 'wp-agent' ),
			];
		}

		switch ( $operation ) {
			case 'audit':
				return $this->audit_post( $post );

			case 'check_element':
				$block_index = isset( $params['block_index'] ) ? absint( $params['block_index'] ) : 0;
				return $this->check_element( $post, $block_index );

			case 'fix':
				$fix_type = ! empty( $params['fix_type'] ) ? sanitize_key( $params['fix_type'] ) : 'all';
				return $this->fix_issues( $post, $fix_type );

			default:
				return [
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "audit", "check_element", or "fix".', 'wp-agent' ),
				];
		}
	}

	/**
	 * Audit a post for accessibility issues.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $post Post object.
	 * @return array Execution result.
	 */
	private function audit_post( $post ) {
		$content = $post->post_content;
		$issues  = [];

		// Check images without alt text.
		$issues = array_merge( $issues, $this->check_alt_text( $content ) );

		// Check heading hierarchy.
		$issues = array_merge( $issues, $this->check_heading_hierarchy( $content ) );

		// Check empty links.
		$issues = array_merge( $issues, $this->check_empty_links( $content ) );

		// Check missing form labels.
		$issues = array_merge( $issues, $this->check_form_labels( $content ) );

		// Check color contrast indicators.
		$issues = array_merge( $issues, $this->check_color_contrast( $content ) );

		$counts = [
			'critical' => 0,
			'warning'  => 0,
			'info'     => 0,
		];

		foreach ( $issues as $issue ) {
			if ( isset( $counts[ $issue['severity'] ] ) ) {
				++$counts[ $issue['severity'] ];
			}
		}

		$total = count( $issues );

		return [
			'success' => true,
			'data'    => [
				'post_id' => $post->ID,
				'title'   => $post->post_title,
				'issues'  => $issues,
				'counts'  => $counts,
				'total'   => $total,
				'score'   => $total > 0 ? max( 0, 100 - ( $counts['critical'] * 15 ) - ( $counts['warning'] * 5 ) - ( $counts['info'] * 1 ) ) : 100,
			],
			'message' => 0 === $total
				? sprintf(
					/* translators: %s: post title */
					__( 'No accessibility issues found in "%s".', 'wp-agent' ),
					$post->post_title
				)
				: sprintf(
					/* translators: 1: total issues, 2: critical count, 3: warning count, 4: post title */
					__( 'Found %1$d accessibility issue(s) in "%4$s": %2$d critical, %3$d warnings.', 'wp-agent' ),
					$total,
					$counts['critical'],
					$counts['warning'],
					$post->post_title
				),
		];
	}

	/**
	 * Check a specific block element.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $post        Post object.
	 * @param int      $block_index Block index.
	 * @return array Execution result.
	 */
	private function check_element( $post, $block_index ) {
		$blocks = parse_blocks( $post->post_content );

		if ( ! isset( $blocks[ $block_index ] ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: block index */
					__( 'Block at index %d not found.', 'wp-agent' ),
					$block_index
				),
			];
		}

		$block   = $blocks[ $block_index ];
		$html    = render_block( $block );
		$issues  = [];

		$issues = array_merge( $issues, $this->check_alt_text( $html ) );
		$issues = array_merge( $issues, $this->check_heading_hierarchy( $html ) );
		$issues = array_merge( $issues, $this->check_empty_links( $html ) );

		return [
			'success' => true,
			'data'    => [
				'block_name'  => $block['blockName'] ?? 'unknown',
				'block_index' => $block_index,
				'issues'      => $issues,
				'total'       => count( $issues ),
			],
			'message' => sprintf(
				/* translators: 1: issue count, 2: block name */
				__( 'Found %1$d issue(s) in block "%2$s".', 'wp-agent' ),
				count( $issues ),
				$block['blockName'] ?? 'unknown'
			),
		];
	}

	/**
	 * Apply automated fixes to post content.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_Post $post     Post object.
	 * @param string   $fix_type Type of fix to apply.
	 * @return array Execution result.
	 */
	private function fix_issues( $post, $fix_type ) {
		$content       = $post->post_content;
		$fixes_applied = 0;

		if ( in_array( $fix_type, [ 'alt_text', 'all' ], true ) ) {
			// Add placeholder alt text to images missing it.
			$content = preg_replace_callback( '/<img(?![^>]*\balt=)[^>]*>/i', function ( $match ) use ( &$fixes_applied ) {
				++$fixes_applied;
				return str_replace( '<img', '<img alt="' . esc_attr__( 'Image', 'wp-agent' ) . '"', $match[0] );
			}, $content );
		}

		if ( in_array( $fix_type, [ 'empty_links', 'all' ], true ) ) {
			// Add aria-label to empty links that have no text.
			$content = preg_replace_callback( '/<a([^>]*)>\s*<\/a>/i', function ( $match ) use ( &$fixes_applied ) {
				if ( false === strpos( $match[1], 'aria-label' ) ) {
					++$fixes_applied;
					return '<a' . $match[1] . ' aria-label="' . esc_attr__( 'Link', 'wp-agent' ) . '"></a>';
				}
				return $match[0];
			}, $content );
		}

		if ( $fixes_applied > 0 ) {
			$result = wp_update_post( [
				'ID'           => $post->ID,
				'post_content' => wp_slash( $content ),
			], true );

			if ( is_wp_error( $result ) ) {
				return [
					'success' => false,
					'data'    => null,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Failed to save fixes: %s', 'wp-agent' ),
						$result->get_error_message()
					),
				];
			}
		}

		return [
			'success' => true,
			'data'    => [
				'post_id'       => $post->ID,
				'fixes_applied' => $fixes_applied,
				'fix_type'      => $fix_type,
			],
			'message' => $fixes_applied > 0
				? sprintf(
					/* translators: 1: fix count, 2: post title */
					__( 'Applied %1$d accessibility fix(es) to "%2$s".', 'wp-agent' ),
					$fixes_applied,
					$post->post_title
				)
				: sprintf(
					/* translators: %s: post title */
					__( 'No automatic fixes needed for "%s".', 'wp-agent' ),
					$post->post_title
				),
		];
	}

	/**
	 * Check for images without alt text.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html HTML content.
	 * @return array Issues found.
	 */
	private function check_alt_text( $html ) {
		$issues = [];

		if ( preg_match_all( '/<img[^>]+>/i', $html, $matches ) ) {
			foreach ( $matches[0] as $img ) {
				if ( ! preg_match( '/\balt=["\']([^"\']*)["\']/', $img, $alt_match ) ) {
					$src = '';
					if ( preg_match( '/src=["\']([^"\']+)["\']/', $img, $src_match ) ) {
						$src = basename( $src_match[1] );
					}
					$issues[] = [
						'type'     => 'missing_alt_text',
						'severity' => 'critical',
						'element'  => substr( $img, 0, 120 ),
						'message'  => sprintf(
							/* translators: %s: image filename */
							__( 'Image "%s" has no alt attribute.', 'wp-agent' ),
							$src
						),
						'fix'      => __( 'Add descriptive alt text that conveys the image content.', 'wp-agent' ),
					];
				} elseif ( empty( trim( $alt_match[1] ) ) ) {
					$issues[] = [
						'type'     => 'empty_alt_text',
						'severity' => 'warning',
						'element'  => substr( $img, 0, 120 ),
						'message'  => __( 'Image has an empty alt attribute. If decorative, this is acceptable.', 'wp-agent' ),
						'fix'      => __( 'Add descriptive alt text or confirm the image is purely decorative.', 'wp-agent' ),
					];
				}
			}
		}

		return $issues;
	}

	/**
	 * Check heading hierarchy for skipped levels.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html HTML content.
	 * @return array Issues found.
	 */
	private function check_heading_hierarchy( $html ) {
		$issues = [];

		if ( preg_match_all( '/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER ) ) {
			$last_level = 0;

			foreach ( $matches as $match ) {
				$level = (int) $match[1];
				$text  = trim( wp_strip_all_tags( $match[2] ) );

				// Check for skipped heading levels (e.g., h2 -> h4).
				if ( $last_level > 0 && $level > $last_level + 1 ) {
					$issues[] = [
						'type'     => 'heading_skip',
						'severity' => 'warning',
						'element'  => sprintf( 'h%d: %s', $level, substr( $text, 0, 80 ) ),
						'message'  => sprintf(
							/* translators: 1: current heading level, 2: previous heading level */
							__( 'Heading level h%1$d skips from h%2$d. Headings should not skip levels.', 'wp-agent' ),
							$level,
							$last_level
						),
						'fix'      => sprintf(
							/* translators: %d: correct heading level */
							__( 'Change to h%d to maintain proper hierarchy.', 'wp-agent' ),
							$last_level + 1
						),
					];
				}

				$last_level = $level;
			}
		}

		return $issues;
	}

	/**
	 * Check for empty links with no text content.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html HTML content.
	 * @return array Issues found.
	 */
	private function check_empty_links( $html ) {
		$issues = [];

		if ( preg_match_all( '/<a([^>]*)>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$attrs = $match[1];
				$inner = trim( wp_strip_all_tags( $match[2] ) );

				// Empty link with no text and no aria-label.
				if ( empty( $inner ) && false === strpos( $attrs, 'aria-label' ) ) {
					// Check if it contains an image (which is okay).
					if ( false !== strpos( $match[2], '<img' ) ) {
						continue;
					}

					$issues[] = [
						'type'     => 'empty_link',
						'severity' => 'critical',
						'element'  => substr( $match[0], 0, 120 ),
						'message'  => __( 'Link has no accessible text content or aria-label.', 'wp-agent' ),
						'fix'      => __( 'Add descriptive text or an aria-label attribute.', 'wp-agent' ),
					];
				}
			}
		}

		return $issues;
	}

	/**
	 * Check for form inputs without associated labels.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html HTML content.
	 * @return array Issues found.
	 */
	private function check_form_labels( $html ) {
		$issues = [];

		if ( preg_match_all( '/<input[^>]+>/i', $html, $matches ) ) {
			foreach ( $matches[0] as $input ) {
				// Skip hidden, submit, button types.
				if ( preg_match( '/type=["\'](hidden|submit|button|reset|image)["\']/', $input ) ) {
					continue;
				}

				// Check for aria-label or aria-labelledby.
				if ( false !== strpos( $input, 'aria-label' ) ) {
					continue;
				}

				// Check for associated label via id.
				$has_id = preg_match( '/\bid=["\']([^"\']+)["\']/', $input, $id_match );
				if ( $has_id && preg_match( '/for=["\']' . preg_quote( $id_match[1], '/' ) . '["\']/', $html ) ) {
					continue;
				}

				$issues[] = [
					'type'     => 'missing_label',
					'severity' => 'critical',
					'element'  => substr( $input, 0, 120 ),
					'message'  => __( 'Form input has no associated label, aria-label, or aria-labelledby.', 'wp-agent' ),
					'fix'      => __( 'Add a <label for="..."> element or aria-label attribute.', 'wp-agent' ),
				];
			}
		}

		return $issues;
	}

	/**
	 * Check for potential color contrast issues.
	 *
	 * @since 1.1.0
	 *
	 * @param string $html HTML content.
	 * @return array Issues found.
	 */
	private function check_color_contrast( $html ) {
		$issues = [];

		// Look for inline styles with light text colors that might be on light backgrounds.
		if ( preg_match_all( '/style=["\']([^"\']*color\s*:[^"\']+)["\']/i', $html, $matches ) ) {
			foreach ( $matches[1] as $style ) {
				$color = '';
				$bg    = '';

				if ( preg_match( '/(?<![a-z-])color\s*:\s*(#[0-9a-fA-F]{3,6}|rgb[^)]+\))/i', $style, $c_match ) ) {
					$color = strtolower( $c_match[1] );
				}
				if ( preg_match( '/background(?:-color)?\s*:\s*(#[0-9a-fA-F]{3,6}|rgb[^)]+\))/i', $style, $bg_match ) ) {
					$bg = strtolower( $bg_match[1] );
				}

				// Flag light-on-light or dark-on-dark patterns.
				if ( $color && $bg && $this->is_low_contrast( $color, $bg ) ) {
					$issues[] = [
						'type'     => 'low_contrast',
						'severity' => 'warning',
						'element'  => sprintf( 'color: %s, background: %s', $color, $bg ),
						'message'  => __( 'Potential low contrast between text color and background color.', 'wp-agent' ),
						'fix'      => __( 'Ensure a contrast ratio of at least 4.5:1 for normal text (WCAG AA).', 'wp-agent' ),
					];
				}
			}
		}

		return $issues;
	}

	/**
	 * Simple check if two hex colors might have low contrast.
	 *
	 * @since 1.1.0
	 *
	 * @param string $color Text color hex.
	 * @param string $bg    Background color hex.
	 * @return bool True if potentially low contrast.
	 */
	private function is_low_contrast( $color, $bg ) {
		$color_lum = $this->hex_luminance( $color );
		$bg_lum    = $this->hex_luminance( $bg );

		if ( false === $color_lum || false === $bg_lum ) {
			return false;
		}

		$lighter = max( $color_lum, $bg_lum );
		$darker  = min( $color_lum, $bg_lum );

		$ratio = ( $lighter + 0.05 ) / ( $darker + 0.05 );

		// WCAG AA requires 4.5:1 for normal text.
		return $ratio < 4.5;
	}

	/**
	 * Calculate relative luminance from a hex color.
	 *
	 * @since 1.1.0
	 *
	 * @param string $hex Hex color string.
	 * @return float|false Relative luminance or false on failure.
	 */
	private function hex_luminance( $hex ) {
		$hex = ltrim( $hex, '#' );

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		if ( 6 !== strlen( $hex ) ) {
			return false;
		}

		$r = hexdec( substr( $hex, 0, 2 ) ) / 255;
		$g = hexdec( substr( $hex, 2, 2 ) ) / 255;
		$b = hexdec( substr( $hex, 4, 2 ) ) / 255;

		$r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
		$g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
		$b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}
}
