<?php
/**
 * Edit Post Action.
 *
 * Updates an existing WordPress post via wp_update_post().
 *
 * @package JarvisAI\Actions
 * @since   1.0.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Edit_Post
 *
 * @since 1.0.0
 */
class Edit_Post implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'edit_post';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Update metadata of an existing WordPress post or page (title, status, excerpt, slug, categories, tags, scheduled date). '
			. 'Only the fields you provide will be changed. '
			. 'To schedule a post, set post_date to a future datetime (e.g. "2025-12-25 09:00:00") — '
			. 'the status will automatically be set to "future". '
			. 'IMPORTANT: Do NOT use this tool to set post_content when the user is in the Gutenberg editor — use insert_blocks instead, '
			. 'which produces properly structured blocks with styling. '
			. 'Use edit_post only for metadata changes like publishing a draft, changing the title, or updating the excerpt.';
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
				'post_id'       => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to update.',
				),
				'post_title'    => array(
					'type'        => 'string',
					'description' => 'New title for the post.',
				),
				'post_content'  => array(
					'type'        => 'string',
					'description' => 'New content/body for the post (supports HTML and block markup).',
				),
				'post_excerpt'  => array(
					'type'        => 'string',
					'description' => 'New excerpt/summary for the post.',
				),
				'post_status'   => array(
					'type'        => 'string',
					'description' => 'New status for the post.',
					'enum'        => array( 'draft', 'publish', 'pending', 'private', 'future' ),
				),
				'post_date'     => array(
					'type'        => 'string',
					'description' => 'Post date in "YYYY-MM-DD HH:MM:SS" format. Set to a future date to schedule the post.',
				),
				'post_date_gmt' => array(
					'type'        => 'string',
					'description' => 'Post date in GMT. If omitted but post_date is set, GMT is calculated automatically.',
				),
				'post_name'     => array(
					'type'        => 'string',
					'description' => 'New slug (URL-friendly name) for the post.',
				),
				'post_parent'   => array(
					'type'        => 'integer',
					'description' => 'New parent post ID (for hierarchical post types).',
				),
				'post_category' => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'integer' ),
					'description' => 'Array of category IDs to assign (replaces existing).',
				),
				'tags_input'    => array(
					'type'        => 'array',
					'items'       => array( 'type' => 'string' ),
					'description' => 'Array of tag names to assign (replaces existing).',
				),
			),
			'required'   => array( 'post_id' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_posts';
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
		$post_id = absint( $params['post_id'] );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: post ID */
					__( 'Post #%d not found.', 'jarvis-ai' ),
					$post_id
				),
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'You do not have permission to edit this post.', 'jarvis-ai' ),
			);
		}

		$args = array( 'ID' => $post_id );

		if ( isset( $params['post_title'] ) ) {
			$args['post_title'] = sanitize_text_field( $params['post_title'] );
		}

		if ( isset( $params['post_content'] ) ) {
			$args['post_content'] = wp_kses_post( $params['post_content'] );
		}

		if ( isset( $params['post_excerpt'] ) ) {
			$args['post_excerpt'] = sanitize_textarea_field( $params['post_excerpt'] );
		}

		if ( ! empty( $params['post_status'] ) ) {
			$allowed_statuses = array( 'draft', 'publish', 'pending', 'private', 'future' );
			$status           = sanitize_text_field( $params['post_status'] );
			if ( in_array( $status, $allowed_statuses, true ) ) {
				$args['post_status'] = $status;
			}
		}

		// Handle scheduling via post_date.
		if ( ! empty( $params['post_date'] ) ) {
			$timestamp = strtotime( $params['post_date'] );
			if ( false === $timestamp ) {
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid post_date format. Use "YYYY-MM-DD HH:MM:SS".', 'jarvis-ai' ),
				);
			}

			$args['post_date'] = gmdate( 'Y-m-d H:i:s', $timestamp );
			$args['edit_date'] = true;

			if ( ! empty( $params['post_date_gmt'] ) ) {
				$gmt_timestamp         = strtotime( $params['post_date_gmt'] );
				$args['post_date_gmt'] = false !== $gmt_timestamp ? gmdate( 'Y-m-d H:i:s', $gmt_timestamp ) : '';
			} else {
				$args['post_date_gmt'] = get_gmt_from_date( $args['post_date'] );
			}

			// Auto-set status to 'future' when date is in the future and status is 'publish' or not explicitly set.
			if ( $timestamp > time() ) {
				$explicit_status = $params['post_status'] ?? '';
				if ( empty( $explicit_status ) || 'publish' === $explicit_status ) {
					$args['post_status'] = 'future';
				}
			}
		}

		if ( isset( $params['post_name'] ) ) {
			$args['post_name'] = sanitize_title( $params['post_name'] );
		}

		if ( isset( $params['post_parent'] ) ) {
			$args['post_parent'] = absint( $params['post_parent'] );
		}

		if ( isset( $params['post_category'] ) ) {
			$args['post_category'] = array_map( 'absint', $params['post_category'] );
		}

		if ( isset( $params['tags_input'] ) ) {
			$args['tags_input'] = array_map( 'sanitize_text_field', $params['tags_input'] );
		}

		$result = wp_update_post( $args, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'  => $result,
				'edit_url' => get_edit_post_link( $result, 'raw' ),
			),
			'message' => sprintf(
				/* translators: %d: post ID */
				__( 'Updated post #%d.', 'jarvis-ai' ),
				$result
			),
		);
	}
}
