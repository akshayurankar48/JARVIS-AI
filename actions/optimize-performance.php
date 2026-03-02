<?php
/**
 * Optimize Performance Action.
 *
 * Analyzes and optimizes post content for performance by checking image
 * dimensions, lazy loading, and media library file sizes. Provides
 * actionable reports and automated fixes.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Optimize_Performance
 *
 * @since 1.1.0
 */
class Optimize_Performance implements Action_Interface {

	/**
	 * Large image threshold in bytes (500 KB).
	 *
	 * @var int
	 */
	const LARGE_IMAGE_THRESHOLD = 500 * 1024;

	/**
	 * Maximum attachments to scan.
	 *
	 * @var int
	 */
	const MAX_ATTACHMENTS = 100;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'optimize_performance';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Analyze and optimize post/page performance. Operations: "analyze" checks for images without dimensions, '
			. 'large images in media library, and lazy loading status. "optimize_images" adds missing width/height attributes. '
			. '"lazy_load" ensures loading="lazy" on images. "report" generates a full performance summary.';
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
				'operation' => array(
					'type'        => 'string',
					'enum'        => array( 'analyze', 'optimize_images', 'lazy_load', 'report' ),
					'description' => 'Operation to perform.',
				),
				'post_id'   => array(
					'type'        => 'integer',
					'description' => 'Post or page ID. Required for analyze, optimize_images, and lazy_load.',
				),
			),
			'required'   => array( 'operation' ),
		);
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
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'analyze':
				return $this->analyze( $params );

			case 'optimize_images':
				return $this->optimize_images( $params );

			case 'lazy_load':
				return $this->add_lazy_loading( $params );

			case 'report':
				return $this->generate_report( $params );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "analyze", "optimize_images", "lazy_load", or "report".', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * Analyze post content for performance issues.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function analyze( array $params ) {
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( ! $post_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post ID is required for analysis.', 'jarvis-ai' ),
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post not found.', 'jarvis-ai' ),
			);
		}

		$content = $post->post_content;
		$issues  = array();

		// Check images without width/height.
		$images_without_dimensions = 0;
		$images_without_lazy       = 0;
		$total_images              = 0;

		if ( preg_match_all( '/<img[^>]+>/i', $content, $img_matches ) ) {
			$total_images = count( $img_matches[0] );

			foreach ( $img_matches[0] as $img ) {
				if ( ! preg_match( '/\bwidth=/', $img ) || ! preg_match( '/\bheight=/', $img ) ) {
					++$images_without_dimensions;
				}
				if ( ! preg_match( '/loading=["\']lazy["\']/', $img ) ) {
					++$images_without_lazy;
				}
			}
		}

		if ( $images_without_dimensions > 0 ) {
			$issues[] = array(
				'type'    => 'missing_dimensions',
				'count'   => $images_without_dimensions,
				'message' => sprintf(
					/* translators: %d: image count */
					__( '%d image(s) missing width/height attributes (causes layout shift).', 'jarvis-ai' ),
					$images_without_dimensions
				),
			);
		}

		if ( $images_without_lazy > 0 ) {
			$issues[] = array(
				'type'    => 'missing_lazy_load',
				'count'   => $images_without_lazy,
				'message' => sprintf(
					/* translators: %d: image count */
					__( '%d image(s) without lazy loading.', 'jarvis-ai' ),
					$images_without_lazy
				),
			);
		}

		// Check content size.
		$content_size = strlen( $content );
		if ( $content_size > 100000 ) {
			$issues[] = array(
				'type'    => 'large_content',
				'size'    => $content_size,
				'message' => sprintf(
					/* translators: %s: content size */
					__( 'Post content is %s. Consider splitting into multiple pages.', 'jarvis-ai' ),
					size_format( $content_size )
				),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'      => $post_id,
				'total_images' => $total_images,
				'issues'       => $issues,
				'issue_count'  => count( $issues ),
			),
			'message' => count( $issues ) > 0
				? sprintf(
					/* translators: 1: issue count, 2: post title */
					__( 'Found %1$d performance issue(s) in "%2$s".', 'jarvis-ai' ),
					count( $issues ),
					$post->post_title
				)
				: sprintf(
					/* translators: %s: post title */
					__( 'No performance issues found in "%s".', 'jarvis-ai' ),
					$post->post_title
				),
		);
	}

	/**
	 * Add missing width/height attributes to images.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function optimize_images( array $params ) {
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( ! $post_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post ID is required.', 'jarvis-ai' ),
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post not found.', 'jarvis-ai' ),
			);
		}

		$content = $post->post_content;
		$fixed   = 0;

		$content = preg_replace_callback(
			'/<img[^>]+>/i',
			function ( $match ) use ( &$fixed ) {
				$img = $match[0];

				// Skip if already has both width and height.
				if ( preg_match( '/\bwidth=/', $img ) && preg_match( '/\bheight=/', $img ) ) {
					return $img;
				}

				// Try to get dimensions from the src.
				if ( preg_match( '/src=["\']([^"\']+)["\']/', $img, $src_match ) ) {
					$attachment_id = attachment_url_to_postid( $src_match[1] );

					if ( $attachment_id ) {
						$metadata = wp_get_attachment_metadata( $attachment_id );
						if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
							$width  = (int) $metadata['width'];
							$height = (int) $metadata['height'];

							if ( ! preg_match( '/\bwidth=/', $img ) ) {
								$img = str_replace( '<img', '<img width="' . $width . '"', $img );
							}
							if ( ! preg_match( '/\bheight=/', $img ) ) {
								$img = str_replace( '<img', '<img height="' . $height . '"', $img );
							}

							++$fixed;
						}
					}
				}

				return $img;
			},
			$content
		);

		if ( $fixed > 0 ) {
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $content ),
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'data'    => null,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Failed to save optimizations: %s', 'jarvis-ai' ),
						$result->get_error_message()
					),
				);
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'      => $post_id,
				'images_fixed' => $fixed,
			),
			'message' => sprintf(
				/* translators: 1: fixed count, 2: post title */
				__( 'Added width/height to %1$d image(s) in "%2$s".', 'jarvis-ai' ),
				$fixed,
				$post->post_title
			),
		);
	}

	/**
	 * Ensure loading="lazy" on images in post content.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function add_lazy_loading( array $params ) {
		$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;

		if ( ! $post_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post ID is required.', 'jarvis-ai' ),
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Post not found.', 'jarvis-ai' ),
			);
		}

		$content = $post->post_content;
		$fixed   = 0;

		$content = preg_replace_callback(
			'/<img([^>]+)>/i',
			function ( $match ) use ( &$fixed ) {
				$attrs = $match[1];

				// Skip if already has loading attribute.
				if ( preg_match( '/\bloading=/', $attrs ) ) {
					return $match[0];
				}

				++$fixed;
				return '<img' . $attrs . ' loading="lazy">';
			},
			$content
		);

		if ( $fixed > 0 ) {
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => wp_slash( $content ),
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'data'    => null,
					'message' => sprintf(
						/* translators: %s: error message */
						__( 'Failed to save lazy loading changes: %s', 'jarvis-ai' ),
						$result->get_error_message()
					),
				);
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'      => $post_id,
				'images_fixed' => $fixed,
			),
			'message' => sprintf(
				/* translators: 1: fixed count, 2: post title */
				__( 'Added lazy loading to %1$d image(s) in "%2$s".', 'jarvis-ai' ),
				$fixed,
				$post->post_title
			),
		);
	}

	/**
	 * Generate a site-wide performance report.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function generate_report( array $params ) {
		// Check large images in media library.
		$large_images = array();
		$attachments  = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => self::MAX_ATTACHMENTS,
				'post_status'    => 'inherit',
				'fields'         => 'ids',
			)
		);

		foreach ( $attachments as $attachment_id ) {
			$file = get_attached_file( $attachment_id );
			if ( $file && file_exists( $file ) ) {
				$size = filesize( $file );
				if ( $size > self::LARGE_IMAGE_THRESHOLD ) {
					$large_images[] = array(
						'id'       => $attachment_id,
						'filename' => basename( $file ),
						'size'     => size_format( $size ),
						'bytes'    => $size,
					);
				}
			}
		}

		// Sort by size descending.
		usort(
			$large_images,
			function ( $a, $b ) {
				return $b['bytes'] - $a['bytes'];
			}
		);

		// WordPress lazy loading default.
		$wp_lazy_loading = function_exists( 'wp_lazy_loading_enabled' ) && wp_lazy_loading_enabled( 'img', 'the_content' );

		// Check post count.
		$post_count  = wp_count_posts( 'post' );
		$page_count  = wp_count_posts( 'page' );
		$total_media = wp_count_attachments();

		$report = array(
			'large_images'      => array_slice( $large_images, 0, 20 ),
			'large_image_count' => count( $large_images ),
			'total_media'       => array_sum( (array) $total_media ),
			'wp_lazy_loading'   => $wp_lazy_loading,
			'published_posts'   => isset( $post_count->publish ) ? (int) $post_count->publish : 0,
			'published_pages'   => isset( $page_count->publish ) ? (int) $page_count->publish : 0,
			'active_plugins'    => count( get_option( 'active_plugins', array() ) ),
		);

		return array(
			'success' => true,
			'data'    => $report,
			'message' => sprintf(
				/* translators: 1: large image count, 2: total media count */
				__( 'Performance report: %1$d large image(s) (>500KB) out of %2$d total media files.', 'jarvis-ai' ),
				count( $large_images ),
				$report['total_media']
			),
		);
	}
}
