<?php
/**
 * Import Content Action.
 *
 * Parses CSV or JSON data and bulk-imports posts into WordPress.
 * Supports column mapping for CSV files and direct post field
 * mapping for JSON arrays.
 *
 * @package WPAgent\Actions
 * @since   1.1.0
 */

namespace WPAgent\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Import_Content
 *
 * @since 1.1.0
 */
class Import_Content implements Action_Interface {

	/**
	 * Maximum posts per import batch.
	 *
	 * @var int
	 */
	const MAX_IMPORT = 100;

	/**
	 * Maximum CSV file size in bytes (2 MB).
	 *
	 * @var int
	 */
	const MAX_FILE_SIZE = 2 * 1024 * 1024;

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'import_content';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Import content from CSV or JSON data. Operations: "parse_csv" reads a CSV file and maps columns to post fields, '
			. '"parse_json" reads a JSON array of posts, "import" creates posts from parsed data. '
			. 'Supports title, content, excerpt, status, type, categories, and tags.';
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
				'operation'   => array(
					'type'        => 'string',
					'enum'        => array( 'parse_csv', 'parse_json', 'import' ),
					'description' => 'Operation to perform.',
				),
				'file_path'   => array(
					'type'        => 'string',
					'description' => 'Path to CSV file in uploads directory (for "parse_csv").',
				),
				'json_data'   => array(
					'type'        => 'string',
					'description' => 'JSON string of posts array (for "parse_json").',
				),
				'column_map'  => array(
					'type'        => 'object',
					'properties'  => array(
						'title'      => array(
							'type'        => 'string',
							'description' => 'CSV column name for post title.',
						),
						'content'    => array(
							'type'        => 'string',
							'description' => 'CSV column name for post content.',
						),
						'excerpt'    => array(
							'type'        => 'string',
							'description' => 'CSV column name for post excerpt.',
						),
						'categories' => array(
							'type'        => 'string',
							'description' => 'CSV column name for categories (comma-separated).',
						),
						'tags'       => array(
							'type'        => 'string',
							'description' => 'CSV column name for tags (comma-separated).',
						),
					),
					'description' => 'Column mapping for CSV (for "parse_csv").',
				),
				'posts'       => array(
					'type'        => 'array',
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'title'      => array( 'type' => 'string' ),
							'content'    => array( 'type' => 'string' ),
							'excerpt'    => array( 'type' => 'string' ),
							'status'     => array( 'type' => 'string' ),
							'type'       => array( 'type' => 'string' ),
							'categories' => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
							'tags'       => array(
								'type'  => 'array',
								'items' => array( 'type' => 'string' ),
							),
						),
					),
					'description' => 'Array of post data to import (for "import").',
				),
				'post_type'   => array(
					'type'        => 'string',
					'description' => 'Post type for imported content. Defaults to "post".',
				),
				'post_status' => array(
					'type'        => 'string',
					'enum'        => array( 'publish', 'draft', 'pending' ),
					'description' => 'Status for imported posts. Defaults to "draft".',
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
		return 'import';
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
			case 'parse_csv':
				return $this->parse_csv( $params );

			case 'parse_json':
				return $this->parse_json( $params );

			case 'import':
				return $this->import_posts( $params );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "parse_csv", "parse_json", or "import".', 'wp-agent' ),
				);
		}
	}

	/**
	 * Parse a CSV file and map columns to post fields.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function parse_csv( array $params ) {
		$file_path = ! empty( $params['file_path'] ) ? sanitize_text_field( $params['file_path'] ) : '';

		if ( empty( $file_path ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'File path is required.', 'wp-agent' ),
			);
		}

		// Ensure file is within uploads directory.
		$upload_dir = wp_upload_dir();
		$base_dir   = realpath( $upload_dir['basedir'] );
		$real_path  = realpath( $file_path );

		if ( false === $real_path || 0 !== strpos( $real_path, $base_dir ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'File must be within the WordPress uploads directory.', 'wp-agent' ),
			);
		}

		if ( ! is_readable( $real_path ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'File is not readable.', 'wp-agent' ),
			);
		}

		$file_size = filesize( $real_path );
		if ( $file_size > self::MAX_FILE_SIZE ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'File exceeds maximum size of 2 MB.', 'wp-agent' ),
			);
		}

		$column_map = isset( $params['column_map'] ) && is_array( $params['column_map'] ) ? $params['column_map'] : array();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $real_path, 'r' );
		if ( ! $handle ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to open CSV file.', 'wp-agent' ),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'CSV file has no headers.', 'wp-agent' ),
			);
		}

		$headers = array_map( 'trim', $headers );
		$posts   = array();
		$row_num = 0;

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fgetcsv
		while ( ( $row = fgetcsv( $handle ) ) !== false && $row_num < self::MAX_IMPORT ) {
			if ( count( $row ) !== count( $headers ) ) {
				continue;
			}

			$row_data = array_combine( $headers, $row );
			$post     = $this->map_csv_row( $row_data, $column_map );

			if ( ! empty( $post['title'] ) ) {
				$posts[] = $post;
				++$row_num;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return array(
			'success' => true,
			'data'    => array(
				'headers'    => $headers,
				'posts'      => $posts,
				'row_count'  => count( $posts ),
				'column_map' => $column_map,
			),
			'message' => sprintf(
				/* translators: 1: row count, 2: column count */
				__( 'Parsed %1$d row(s) from CSV with %2$d columns.', 'wp-agent' ),
				count( $posts ),
				count( $headers )
			),
		);
	}

	/**
	 * Parse JSON data into post array.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function parse_json( array $params ) {
		$json_data = $params['json_data'] ?? '';

		if ( empty( $json_data ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'JSON data is required.', 'wp-agent' ),
			);
		}

		$data = json_decode( $json_data, true );

		if ( null === $data || ! is_array( $data ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid JSON data.', 'wp-agent' ),
			);
		}

		// Ensure it's an array of objects.
		if ( ! isset( $data[0] ) ) {
			$data = array( $data );
		}

		$posts = array();
		foreach ( array_slice( $data, 0, self::MAX_IMPORT ) as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$post = array(
				'title'      => sanitize_text_field( $item['title'] ?? '' ),
				'content'    => wp_kses_post( $item['content'] ?? '' ),
				'excerpt'    => sanitize_textarea_field( $item['excerpt'] ?? '' ),
				'categories' => isset( $item['categories'] ) && is_array( $item['categories'] )
					? array_map( 'sanitize_text_field', $item['categories'] )
					: array(),
				'tags'       => isset( $item['tags'] ) && is_array( $item['tags'] )
					? array_map( 'sanitize_text_field', $item['tags'] )
					: array(),
			);

			if ( ! empty( $post['title'] ) ) {
				$posts[] = $post;
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'posts'     => $posts,
				'row_count' => count( $posts ),
			),
			'message' => sprintf(
				/* translators: %d: post count */
				__( 'Parsed %d post(s) from JSON data.', 'wp-agent' ),
				count( $posts )
			),
		);
	}

	/**
	 * Import posts from parsed data.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Action parameters.
	 * @return array Execution result.
	 */
	private function import_posts( array $params ) {
		$posts       = isset( $params['posts'] ) && is_array( $params['posts'] ) ? $params['posts'] : array();
		$post_type   = ! empty( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : 'post';
		$post_status = ! empty( $params['post_status'] ) ? sanitize_key( $params['post_status'] ) : 'draft';

		if ( ! in_array( $post_status, array( 'publish', 'draft', 'pending' ), true ) ) {
			$post_status = 'draft';
		}

		if ( ! post_type_exists( $post_type ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: post type */
					__( 'Post type "%s" does not exist.', 'wp-agent' ),
					$post_type
				),
			);
		}

		if ( empty( $posts ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'No posts data provided. Use parse_csv or parse_json first.', 'wp-agent' ),
			);
		}

		$posts   = array_slice( $posts, 0, self::MAX_IMPORT );
		$created = array();
		$errors  = array();

		foreach ( $posts as $index => $post_data ) {
			$title = sanitize_text_field( $post_data['title'] ?? '' );

			if ( empty( $title ) ) {
				$errors[] = sprintf(
					/* translators: %d: row index */
					__( 'Row %d: Missing title, skipped.', 'wp-agent' ),
					$index + 1
				);
				continue;
			}

			$insert_data = array(
				'post_title'   => $title,
				'post_content' => wp_kses_post( $post_data['content'] ?? '' ),
				'post_excerpt' => sanitize_textarea_field( $post_data['excerpt'] ?? '' ),
				'post_status'  => $post_status,
				'post_type'    => $post_type,
			);

			$post_id = wp_insert_post( $insert_data, true );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = sprintf(
					/* translators: 1: title, 2: error message */
					__( 'Failed to import "%1$s": %2$s', 'wp-agent' ),
					$title,
					$post_id->get_error_message()
				);
				continue;
			}

			// Assign categories.
			if ( ! empty( $post_data['categories'] ) && is_array( $post_data['categories'] ) && 'post' === $post_type ) {
				$cat_ids = array();
				foreach ( $post_data['categories'] as $cat_name ) {
					$cat_name = sanitize_text_field( $cat_name );
					$term     = term_exists( $cat_name, 'category' );
					if ( ! $term ) {
						$term = wp_insert_term( $cat_name, 'category' );
					}
					if ( ! is_wp_error( $term ) ) {
						$cat_ids[] = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
					}
				}
				if ( ! empty( $cat_ids ) ) {
					wp_set_post_categories( $post_id, $cat_ids );
				}
			}

			// Assign tags.
			if ( ! empty( $post_data['tags'] ) && is_array( $post_data['tags'] ) && 'post' === $post_type ) {
				wp_set_post_tags( $post_id, array_map( 'sanitize_text_field', $post_data['tags'] ) );
			}

			$created[] = array(
				'id'    => $post_id,
				'title' => $title,
				'url'   => get_permalink( $post_id ),
			);
		}

		return array(
			'success' => count( $created ) > 0,
			'data'    => array(
				'created' => $created,
				'count'   => count( $created ),
				'errors'  => $errors,
			),
			'message' => sprintf(
				/* translators: 1: created count, 2: total count, 3: error count */
				__( 'Imported %1$d of %2$d post(s) as %3$s.', 'wp-agent' ),
				count( $created ),
				count( $posts ),
				$post_status
			),
		);
	}

	/**
	 * Map a CSV row to post fields using column mapping.
	 *
	 * @since 1.1.0
	 *
	 * @param array $row_data   Associative array of CSV row data.
	 * @param array $column_map Column name mapping.
	 * @return array Mapped post data.
	 */
	private function map_csv_row( array $row_data, array $column_map ) {
		$title_col      = ! empty( $column_map['title'] ) ? $column_map['title'] : 'title';
		$content_col    = ! empty( $column_map['content'] ) ? $column_map['content'] : 'content';
		$excerpt_col    = ! empty( $column_map['excerpt'] ) ? $column_map['excerpt'] : 'excerpt';
		$categories_col = ! empty( $column_map['categories'] ) ? $column_map['categories'] : 'categories';
		$tags_col       = ! empty( $column_map['tags'] ) ? $column_map['tags'] : 'tags';

		$categories = array();
		if ( ! empty( $row_data[ $categories_col ] ) ) {
			$categories = array_map( 'trim', explode( ',', $row_data[ $categories_col ] ) );
		}

		$tags = array();
		if ( ! empty( $row_data[ $tags_col ] ) ) {
			$tags = array_map( 'trim', explode( ',', $row_data[ $tags_col ] ) );
		}

		return array(
			'title'      => sanitize_text_field( $row_data[ $title_col ] ?? '' ),
			'content'    => wp_kses_post( $row_data[ $content_col ] ?? '' ),
			'excerpt'    => sanitize_textarea_field( $row_data[ $excerpt_col ] ?? '' ),
			'categories' => array_map( 'sanitize_text_field', $categories ),
			'tags'       => array_map( 'sanitize_text_field', $tags ),
		);
	}
}
