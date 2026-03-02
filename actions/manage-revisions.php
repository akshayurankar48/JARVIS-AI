<?php
/**
 * Manage Revisions Action.
 *
 * Lists, restores, and compares post revisions. Restoring a revision
 * creates a new revision so the operation is safely reversible.
 *
 * @package JarvisAI\Actions
 * @since   1.0.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Manage_Revisions
 *
 * @since 1.0.0
 */
class Manage_Revisions implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'manage_revisions';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Manage post revisions. Operations: "list" shows all revisions for a post, '
			. '"restore" reverts a post to a specific revision (creates a new revision as backup), '
			. '"compare" shows differences between two revisions.';
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
				'operation'   => array(
					'type'        => 'string',
					'enum'        => array( 'list', 'restore', 'compare' ),
					'description' => 'Operation to perform.',
				),
				'post_id'     => array(
					'type'        => 'integer',
					'description' => 'The post ID to manage revisions for.',
				),
				'revision_id' => array(
					'type'        => 'integer',
					'description' => 'Revision ID to restore. Required for "restore" operation.',
				),
				'compare_to'  => array(
					'type'        => 'integer',
					'description' => 'Second revision ID for "compare" operation. Compares revision_id against compare_to.',
				),
			),
			'required'   => array( 'operation', 'post_id' ),
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
		$operation = $params['operation'] ?? '';
		$post_id   = absint( $params['post_id'] ?? 0 );

		$post = get_post( $post_id );
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

		switch ( $operation ) {
			case 'list':
				return $this->list_revisions( $post_id );

			case 'restore':
				$revision_id = absint( $params['revision_id'] ?? 0 );
				return $this->restore_revision( $post_id, $revision_id );

			case 'compare':
				$revision_id = absint( $params['revision_id'] ?? 0 );
				$compare_to  = absint( $params['compare_to'] ?? 0 );
				return $this->compare_revisions( $post_id, $revision_id, $compare_to );

			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation. Use "list", "restore", or "compare".', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * List all revisions for a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 * @return array Execution result.
	 */
	private function list_revisions( $post_id ) {
		$revisions = wp_get_post_revisions( $post_id );

		if ( empty( $revisions ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'total'     => 0,
					'revisions' => array(),
				),
				'message' => sprintf(
					/* translators: %d: post ID */
					__( 'No revisions found for post #%d.', 'jarvis-ai' ),
					$post_id
				),
			);
		}

		$results = array();
		foreach ( $revisions as $revision ) {
			$results[] = array(
				'revision_id' => $revision->ID,
				'author'      => get_the_author_meta( 'display_name', $revision->post_author ),
				'date'        => $revision->post_date,
				'title'       => $revision->post_title,
				'excerpt'     => wp_trim_words( $revision->post_content, 20 ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'total'     => count( $results ),
				'revisions' => $results,
			),
			'message' => sprintf(
				/* translators: 1: count, 2: post ID */
				__( 'Found %1$d revision(s) for post #%2$d.', 'jarvis-ai' ),
				count( $results ),
				$post_id
			),
		);
	}

	/**
	 * Restore a post to a specific revision.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id     Post ID.
	 * @param int $revision_id Revision ID to restore.
	 * @return array Execution result.
	 */
	private function restore_revision( $post_id, $revision_id ) {
		if ( ! $revision_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'revision_id is required for restore operation.', 'jarvis-ai' ),
			);
		}

		$revision = wp_get_post_revision( $revision_id );
		if ( ! $revision ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %d: revision ID */
					__( 'Revision #%d not found.', 'jarvis-ai' ),
					$revision_id
				),
			);
		}

		// Verify the revision belongs to the specified post.
		if ( (int) $revision->post_parent !== $post_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: 1: revision ID, 2: post ID */
					__( 'Revision #%1$d does not belong to post #%2$d.', 'jarvis-ai' ),
					$revision_id,
					$post_id
				),
			);
		}

		$result = wp_restore_post_revision( $revision_id );

		if ( ! $result || is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Failed to restore revision.', 'jarvis-ai' ),
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'     => $post_id,
				'revision_id' => $revision_id,
				'restored_to' => $revision->post_date,
			),
			'message' => sprintf(
				/* translators: 1: post ID, 2: revision date */
				__( 'Post #%1$d restored to revision from %2$s.', 'jarvis-ai' ),
				$post_id,
				$revision->post_date
			),
		);
	}

	/**
	 * Compare two revisions of a post.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id     Post ID.
	 * @param int $revision_id First revision ID.
	 * @param int $compare_to  Second revision ID.
	 * @return array Execution result.
	 */
	private function compare_revisions( $post_id, $revision_id, $compare_to ) {
		if ( ! $revision_id || ! $compare_to ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Both revision_id and compare_to are required for compare.', 'jarvis-ai' ),
			);
		}

		$rev_a = wp_get_post_revision( $revision_id );
		$rev_b = wp_get_post_revision( $compare_to );

		if ( ! $rev_a || ! $rev_b ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'One or both revision IDs not found.', 'jarvis-ai' ),
			);
		}

		// Verify both belong to the same post.
		if ( (int) $rev_a->post_parent !== $post_id || (int) $rev_b->post_parent !== $post_id ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Both revisions must belong to the specified post.', 'jarvis-ai' ),
			);
		}

		$fields = array( 'post_title', 'post_content', 'post_excerpt' );
		$diff   = array();

		foreach ( $fields as $field ) {
			$a_val = $rev_a->$field ?? '';
			$b_val = $rev_b->$field ?? '';

			if ( $a_val !== $b_val ) {
				$diff[ $field ] = array(
					'revision_a' => wp_trim_words( $a_val, 50 ),
					'revision_b' => wp_trim_words( $b_val, 50 ),
					'changed'    => true,
				);
			} else {
				$diff[ $field ] = array(
					'changed' => false,
				);
			}
		}

		return array(
			'success' => true,
			'data'    => array(
				'revision_a' => array(
					'id'   => $revision_id,
					'date' => $rev_a->post_date,
				),
				'revision_b' => array(
					'id'   => $compare_to,
					'date' => $rev_b->post_date,
				),
				'diff'       => $diff,
			),
			'message' => sprintf(
				/* translators: 1: revision A ID, 2: revision B ID */
				__( 'Compared revision #%1$d with revision #%2$d.', 'jarvis-ai' ),
				$revision_id,
				$compare_to
			),
		);
	}
}
