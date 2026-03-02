<?php
/**
 * Import Media Action.
 *
 * Downloads an image from an external URL and adds it to the
 * WordPress media library using media_sideload_image().
 * Returns the new attachment ID, local URL, and metadata so
 * the AI can immediately use it in page builds.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import_Media
 *
 * @since 1.0.0
 */
class Import_Media implements Action_Interface {

	/**
	 * Maximum file size in bytes (5 MB).
	 *
	 * @var int
	 */
	const MAX_FILE_SIZE = 5 * 1024 * 1024;

	/**
	 * Allowed MIME types for import.
	 *
	 * @var string[]
	 */
	const ALLOWED_MIME_TYPES = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	);

	/**
	 * Allowed URL schemes.
	 *
	 * @var string[]
	 */
	const ALLOWED_SCHEMES = array( 'http', 'https' );

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'import_media';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Download an image from an external URL and import it into the WordPress media library. '
			. 'Returns the new attachment ID and local URL. Use this when you need a specific image from the web '
			. 'that is not already in the media library (e.g. stock photos, logos, product images from URLs). '
			. 'After importing, use the returned URL in insert_blocks for core/image, core/cover, or core/media-text blocks.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'url'         => array(
					'type'        => 'string',
					'description' => 'The full URL of the image to download (must be https or http). Supports JPEG, PNG, GIF, and WebP.',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'Optional title for the imported image. Defaults to the filename.',
				),
				'alt_text'    => array(
					'type'        => 'string',
					'description' => 'Optional alt text for accessibility. Recommended for all images.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description/caption for the image.',
				),
			),
			'required'   => array( 'url' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'upload_files';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return true;
	}

	/**
	 * Execute the action.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$url = $this->validate_url( $params['url'] ?? '' );

		if ( is_wp_error( $url ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $url->get_error_message(),
			);
		}

		// Require media handling functions.
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Download via wp_safe_remote_get (SSRF-safe: validates at connection time,
		// blocks private IPs, and prevents DNS rebinding / redirect-chain attacks).
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 30,
				'redirection'         => 0,
				'reject_unsafe_urls'  => true,
				'limit_response_size' => self::MAX_FILE_SIZE,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to download image: %s', 'wp-agent' ),
					$response->get_error_message()
				),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: HTTP status code */
					__( 'Image URL returned HTTP %d.', 'wp-agent' ),
					$response_code
				),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Downloaded file is empty.', 'wp-agent' ),
			);
		}

		// Write to a temp file for validation and sideload.
		$temp_file = wp_tempnam( 'wp-agent-import-' );
		if ( ! $temp_file ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Could not create temporary file.', 'wp-agent' ),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $temp_file, $body );
		unset( $body );

		// Validate file size.
		$file_size = filesize( $temp_file );
		if ( false === $file_size ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Could not read downloaded file.', 'wp-agent' ),
			);
		}
		if ( $file_size > self::MAX_FILE_SIZE ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: max file size */
					__( 'Image exceeds maximum file size of %s.', 'wp-agent' ),
					size_format( self::MAX_FILE_SIZE )
				),
			);
		}

		// Content-level validation: confirm the file is actually an image.
		$image_info = wp_getimagesize( $temp_file );
		if ( false === $image_info ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'The downloaded file is not a valid image.', 'wp-agent' ),
			);
		}

		// Validate MIME type using WordPress file type check.
		$file_type = wp_check_filetype_and_ext( $temp_file, basename( wp_parse_url( $url, PHP_URL_PATH ) ) );
		$mime_type = $file_type['type'];

		if ( ! $mime_type || ! in_array( $mime_type, self::ALLOWED_MIME_TYPES, true ) ) {
			wp_delete_file( $temp_file );
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'The URL does not point to a supported image format (JPEG, PNG, GIF, or WebP).', 'wp-agent' ),
			);
		}

		// Build the file array for media_handle_sideload.
		$file_array = array(
			'name'     => $this->generate_filename( $url, $file_type['ext'] ),
			'tmp_name' => $temp_file,
		);

		// Sideload into the media library (post_id 0 = unattached).
		$attachment_id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			// Explicitly clean up in case sideload left the file behind.
			if ( file_exists( $temp_file ) ) {
				wp_delete_file( $temp_file );
			}
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'Failed to import image: %s', 'wp-agent' ),
					$attachment_id->get_error_message()
				),
			);
		}

		// Set optional metadata.
		$title = ! empty( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$alt   = ! empty( $params['alt_text'] ) ? sanitize_text_field( $params['alt_text'] ) : '';
		$desc  = ! empty( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';

		$post_update = array();
		if ( $title ) {
			$post_update['post_title'] = $title;
		}
		if ( $desc ) {
			$post_update['post_excerpt'] = $desc;
			$post_update['post_content'] = $desc;
		}
		if ( ! empty( $post_update ) ) {
			$post_update['ID'] = $attachment_id;
			wp_update_post( $post_update );
		}
		if ( $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}

		// Build response.
		$local_url = wp_get_attachment_url( $attachment_id );
		$metadata  = wp_get_attachment_metadata( $attachment_id );

		$data = array(
			'id'    => $attachment_id,
			'url'   => esc_url( $local_url ),
			'title' => $title ? $title : get_the_title( $attachment_id ),
			'alt'   => $alt ? $alt : get_the_title( $attachment_id ),
			'mime'  => get_post_mime_type( $attachment_id ),
		);

		if ( is_array( $metadata ) && ! empty( $metadata['width'] ) ) {
			$data['width']  = absint( $metadata['width'] );
			$data['height'] = absint( $metadata['height'] );
		}

		return array(
			'success' => true,
			'data'    => $data,
			'message' => sprintf(
				/* translators: 1: image title, 2: attachment ID */
				__( 'Image "%1$s" imported to media library (ID: %2$d). Use the local URL in your blocks.', 'wp-agent' ),
				$data['title'],
				$attachment_id
			),
		);
	}

	/**
	 * Validate and sanitize the image URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url Raw URL input.
	 * @return string|\WP_Error Sanitized URL or error.
	 */
	private function validate_url( $url ) {
		if ( empty( $url ) ) {
			return new \WP_Error( 'missing_url', __( 'Image URL is required.', 'wp-agent' ) );
		}

		$url = esc_url_raw( trim( $url ) );

		if ( empty( $url ) ) {
			return new \WP_Error( 'invalid_url', __( 'The provided URL is not valid.', 'wp-agent' ) );
		}

		// Validate scheme.
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, self::ALLOWED_SCHEMES, true ) ) {
			return new \WP_Error( 'invalid_scheme', __( 'Only http and https URLs are allowed.', 'wp-agent' ) );
		}

		// Block localhost and private IPs (SSRF protection).
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return new \WP_Error( 'invalid_host', __( 'The URL must contain a valid hostname.', 'wp-agent' ) );
		}

		$ip = gethostbyname( $host );
		if ( $ip && $this->is_private_ip( $ip ) ) {
			return new \WP_Error( 'blocked_host', __( 'Cannot import from private or local network addresses.', 'wp-agent' ) );
		}

		return $url;
	}

	/**
	 * Check if an IP address is private/reserved.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ip IP address.
	 * @return bool True if the IP is private or reserved.
	 */
	private function is_private_ip( $ip ) {
		return ! filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
		);
	}

	/**
	 * Generate a clean filename from the URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url       The source URL.
	 * @param string $extension File extension.
	 * @return string Sanitized filename.
	 */
	private function generate_filename( $url, $extension ) {
		$path     = wp_parse_url( $url, PHP_URL_PATH );
		$basename = $path ? pathinfo( $path, PATHINFO_FILENAME ) : '';
		$basename = sanitize_file_name( $basename );

		if ( empty( $basename ) || strlen( $basename ) < 3 ) {
			$basename = 'imported-image-' . wp_generate_password( 6, false );
		}

		// Ensure correct extension.
		if ( $extension ) {
			$basename = preg_replace( '/\.[^.]+$/', '', $basename );
			return $basename . '.' . $extension;
		}

		return $basename;
	}
}
