<?php
/**
 * Read Blocks Action.
 *
 * Parses and returns the block structure of a post's content
 * via parse_blocks(). Read-only, no modifications.
 *
 * @package JarvisAI\Actions
 * @since   1.0.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Read_Blocks
 *
 * @since 1.0.0
 */
class Read_Blocks implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'read_blocks';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Read and parse the block structure of a post. Returns the block tree with block names, attributes, and inner HTML. '
			. 'Use this before modifying content to understand the current page structure. '
			. 'Call this first when the user wants to edit, rearrange, or add to existing page content.';
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
				'post_id' => array(
					'type'        => 'integer',
					'description' => 'The ID of the post to read blocks from.',
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
		return 'read';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
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

		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'You do not have permission to read this post.', 'jarvis-ai' ),
			);
		}

		$content = $post->post_content;

		if ( empty( $content ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'post_id'    => $post_id,
					'blocks'     => array(),
					'is_classic' => false,
				),
				'message' => sprintf(
					/* translators: %d: post ID */
					__( 'Post #%d has no content.', 'jarvis-ai' ),
					$post_id
				),
			);
		}

		if ( ! has_blocks( $content ) ) {
			return array(
				'success' => true,
				'data'    => array(
					'post_id'    => $post_id,
					'blocks'     => array(),
					'is_classic' => true,
					'content'    => wp_strip_all_tags( $content ),
				),
				'message' => sprintf(
					/* translators: %d: post ID */
					__( 'Post #%d uses classic content (no blocks).', 'jarvis-ai' ),
					$post_id
				),
			);
		}

		$raw_blocks = parse_blocks( $content );
		$simplified = $this->simplify_blocks( $raw_blocks );

		return array(
			'success' => true,
			'data'    => array(
				'post_id'    => $post_id,
				'blocks'     => $simplified,
				'is_classic' => false,
			),
			'message' => sprintf(
				/* translators: 1: block count, 2: post ID */
				__( 'Found %1$d block(s) in post #%2$d.', 'jarvis-ai' ),
				count( $simplified ),
				$post_id
			),
		);
	}

	/**
	 * Simplify parsed blocks to only the fields the AI needs.
	 *
	 * Filters out empty/whitespace-only freeform blocks that
	 * parse_blocks() creates between real blocks.
	 *
	 * @since 1.0.0
	 *
	 * @param array $blocks Raw parsed blocks from parse_blocks().
	 * @return array Simplified block tree.
	 */
	private function simplify_blocks( array $blocks ): array {
		$simplified = array();

		foreach ( $blocks as $block ) {
			// Skip empty freeform blocks (null blockName, whitespace only).
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$simple = array(
				'blockName' => $block['blockName'],
				'attrs'     => ! empty( $block['attrs'] ) ? $block['attrs'] : new \stdClass(),
				'innerHTML' => isset( $block['innerHTML'] ) ? trim( $block['innerHTML'] ) : '',
			);

			if ( ! empty( $block['innerBlocks'] ) ) {
				$simple['innerBlocks'] = $this->simplify_blocks( $block['innerBlocks'] );
			}

			$simplified[] = $simple;
		}

		return $simplified;
	}
}
