<?php
/**
 * Export Site Action.
 *
 * Generates a WXR (WordPress eXtended RSS) export file and returns
 * a temporary download URL. Read-only — does not modify the site.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Export_Site
 *
 * @since 1.1.0
 */
class Export_Site implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'export_site';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Export the entire site (or specific content types) as a WXR XML file. '
			. 'Returns a temporary download URL. Read-only — does not modify the site.';
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
				'content_type' => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'post', 'page', 'media' ),
					'description' => 'Content type to export. Defaults to "all".',
				),
				'status'       => array(
					'type'        => 'string',
					'enum'        => array( 'all', 'publish', 'draft', 'private' ),
					'description' => 'Post status filter. Defaults to "all".',
				),
			),
			'required'   => array(),
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
		if ( ! function_exists( 'export_wp' ) ) {
			require_once ABSPATH . 'wp-admin/includes/export.php';
		}

		$content_type = isset( $params['content_type'] ) ? sanitize_text_field( $params['content_type'] ) : 'all';
		$status       = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'all';

		$upload_dir = wp_upload_dir();
		$secret     = wp_generate_password( 32, false );
		$filename   = 'jarvis-ai-export-' . gmdate( 'Y-m-d-His' ) . '-' . $secret . '.xml';
		$export_dir = trailingslashit( $upload_dir['basedir'] ) . 'jarvis-ai-exports';
		wp_mkdir_p( $export_dir );

		// Protect the directory from direct browsing.
		$htaccess = $export_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, "Options -Indexes\nDeny from all" );
		}

		$filepath = trailingslashit( $export_dir ) . $filename;
		$file_url = admin_url( 'admin-ajax.php?action=jarvis_ai_download_export&file=' . rawurlencode( $filename ) . '&nonce=' . wp_create_nonce( 'jarvis_ai_export_' . $filename ) );

		$args = array(
			'content' => 'all' === $content_type ? 'all' : $content_type,
		);

		if ( 'all' !== $status ) {
			$args['status'] = $status;
		}

		// Capture export output to file.
		ob_start();
		export_wp( $args );
		$xml_content = ob_get_clean();

		if ( empty( $xml_content ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Export generated no content.', 'jarvis-ai' ),
			);
		}

		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		$written = $wp_filesystem->put_contents( $filepath, $xml_content, FS_CHMOD_FILE );

		if ( false === $written ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to write export file.', 'jarvis-ai' ),
			);
		}

		$file_size = $wp_filesystem->size( $filepath );

		// Schedule cleanup after 1 hour.
		wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'jarvis_ai_cleanup_export', array( $filepath ) );

		return array(
			'success' => true,
			'data'    => array(
				'download_url' => $file_url,
				'filename'     => $filename,
				'size'         => size_format( $file_size ),
				'content_type' => $content_type,
			),
			'message' => sprintf(
				/* translators: 1: file size, 2: content type */
				__( 'Site export complete (%1$s, content: %2$s). Download link is valid for 1 hour.', 'jarvis-ai' ),
				size_format( $file_size ),
				$content_type
			),
		);
	}
}
