<?php
/**
 * Build From Blueprint Action.
 *
 * Composes and inserts an entire page from a blueprint in a single
 * tool call. Replaces the manual pattern-by-pattern workflow where
 * the AI had to call get_pattern + insert_blocks 6-7 times.
 *
 * @package JarvisAI\Actions
 * @since   1.2.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Build_From_Blueprint
 *
 * @since 1.2.0
 */
class Build_From_Blueprint implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_name(): string {
		return 'build_from_blueprint';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Build a full page from a blueprint in a single call. '
			. 'Loads all sections from the blueprint, applies variable overrides (colors, text, images), '
			. 'and inserts all blocks into the post. Replaces the manual get_pattern + insert_blocks workflow. '
			. 'Available blueprints: saas-landing, landing-page, startup-page, agency-portfolio, restaurant-page, '
			. 'ecommerce-landing, real-estate, fitness-gym, consulting-firm, education-course, and more.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.2.0
	 * @return array
	 */
	public function get_parameters(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'blueprint_id' => array(
					'type'        => 'string',
					'description' => 'The blueprint ID (e.g. "saas-landing", "landing-page", "restaurant-page").',
				),
				'post_id'      => array(
					'type'        => 'integer',
					'description' => 'The post ID to insert the page content into.',
				),
				'variables'    => array(
					'type'        => 'object',
					'description' => 'Optional variable overrides applied to all sections (e.g. {"heading": "My App", "primary_color": "#6366f1"}).',
				),
				'position'     => array(
					'type'        => 'string',
					'description' => 'Where to insert: "replace" (default) clears existing content, "append" adds after.',
					'enum'        => array( 'replace', 'append' ),
					'default'     => 'replace',
				),
			),
			'required'   => array( 'blueprint_id', 'post_id' ),
		);
	}

	/**
	 * Get the required capability.
	 *
	 * @since 1.2.0
	 * @return string
	 */
	public function get_capabilities_required(): string {
		return 'edit_posts';
	}

	/**
	 * Whether this action is reversible.
	 *
	 * @since 1.2.0
	 * @return bool
	 */
	public function is_reversible(): bool {
		return false;
	}

	/**
	 * Execute the action.
	 *
	 * Loads the blueprint, composes all sections, and delegates to
	 * Insert_Blocks for the actual post insertion.
	 *
	 * @since 1.2.0
	 *
	 * @param array $params Validated parameters.
	 * @return array Execution result.
	 */
	public function execute( array $params ): array {
		$blueprint_id = sanitize_key( $params['blueprint_id'] ?? '' );
		$post_id      = absint( $params['post_id'] ?? 0 );
		$position     = ! empty( $params['position'] ) ? sanitize_text_field( $params['position'] ) : 'replace';

		if ( empty( $blueprint_id ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => __( 'Blueprint ID is required.', 'jarvis-ai' ),
			);
		}

		if ( empty( $post_id ) ) {
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

		// Build overrides.
		$overrides = array();
		if ( ! empty( $params['variables'] ) && is_array( $params['variables'] ) ) {
			foreach ( $params['variables'] as $key => $value ) {
				$overrides[ sanitize_key( $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
			}
		}

		// Load the full blueprint.
		$manager   = \JarvisAI\Patterns\Pattern_Manager::get_instance();
		$blueprint = $manager->get_blueprint_full( $blueprint_id, $overrides );

		if ( ! $blueprint ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: blueprint ID */
					__( 'Blueprint "%s" not found. Use list_patterns to see available blueprints.', 'jarvis-ai' ),
					$blueprint_id
				),
			);
		}

		if ( empty( $blueprint['blocks'] ) ) {
			return array(
				'success' => false,
				'data'    => null,
				'message' => sprintf(
					/* translators: %s: blueprint name */
					__( 'Blueprint "%s" produced no blocks. Some section patterns may be missing.', 'jarvis-ai' ),
					$blueprint['name']
				),
			);
		}

		// Delegate insertion to Insert_Blocks.
		$inserter = new Insert_Blocks();
		$result   = $inserter->execute(
			array(
				'post_id'  => $post_id,
				'blocks'   => $blueprint['blocks'],
				'position' => $position,
			)
		);

		if ( ! $result['success'] ) {
			return $result;
		}

		// Build a detailed success message.
		$section_list = implode( ', ', $blueprint['loaded'] );
		$message      = sprintf(
			/* translators: 1: blueprint name, 2: section count, 3: block count, 4: post ID, 5: section list */
			__( 'Built "%1$s" page with %2$d sections (%3$d blocks) into post #%4$d. Sections: %5$s.', 'jarvis-ai' ),
			$blueprint['name'],
			count( $blueprint['loaded'] ),
			$blueprint['block_count'],
			$post_id,
			$section_list
		);

		if ( ! empty( $blueprint['failed'] ) ) {
			$message .= ' ' . sprintf(
				/* translators: %s: comma-separated list of failed section IDs */
				__( 'Warning: sections not found: %s.', 'jarvis-ai' ),
				implode( ', ', $blueprint['failed'] )
			);
		}

		return array(
			'success' => true,
			'data'    => array(
				'post_id'   => $post_id,
				'blueprint' => $blueprint['id'],
				'sections'  => $blueprint['loaded'],
				'failed'    => $blueprint['failed'],
				'blocks'    => $result['data']['blocks'] ?? $blueprint['blocks'],
				'position'  => $position,
				'execution' => 'client',
			),
			'message' => $message,
		);
	}
}
