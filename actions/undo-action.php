<?php
/**
 * Undo Action.
 *
 * Allows the AI to list recent undoable actions and revert them by
 * restoring the checkpoint snapshot taken before the action executed.
 *
 * @package WPAgent\Actions
 * @since   1.0.0
 */

namespace WPAgent\Actions;

use WPAgent\Core\Checkpoint_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Class Undo_Action
 *
 * @since 1.0.0
 */
class Undo_Action implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_name(): string {
		return 'undo_action';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Undo a previous action by restoring its checkpoint snapshot. '
			. 'Use operation "list" to see recent undoable actions in this conversation, '
			. 'then "undo" with a checkpoint_id to revert a specific action. '
			. 'Only reversible actions that were executed in this conversation can be undone. '
			. 'Each checkpoint can only be undone once.';
	}

	/**
	 * Get the JSON Schema for parameters.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_parameters(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'operation'       => [
					'type'        => 'string',
					'enum'        => [ 'list', 'undo' ],
					'description' => 'Operation: "list" to see undoable actions, "undo" to revert one.',
				],
				'checkpoint_id'   => [
					'type'        => 'integer',
					'description' => 'The checkpoint ID to undo. Required when operation is "undo". Get this from the "list" operation.',
				],
				'conversation_id' => [
					'type'        => 'integer',
					'description' => 'The conversation ID to query. Defaults to the current conversation.',
				],
			],
			'required'   => [ 'operation' ],
		];
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
	 * Undo itself is not reversible (no undo-undo).
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
		$operation = sanitize_key( $params['operation'] ?? '' );

		if ( ! in_array( $operation, [ 'list', 'undo' ], true ) ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Invalid operation. Must be "list" or "undo".', 'wp-agent' ),
			];
		}

		$conversation_id = isset( $params['conversation_id'] ) ? absint( $params['conversation_id'] ) : 0;

		if ( ! $conversation_id ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'Conversation ID is required. This should be automatically provided by the system.', 'wp-agent' ),
			];
		}

		if ( 'list' === $operation ) {
			return $this->handle_list( $conversation_id );
		}

		return $this->handle_undo( $params, $conversation_id );
	}

	/**
	 * List undoable checkpoints for a conversation.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array Execution result.
	 */
	private function handle_list( $conversation_id ): array {
		$manager     = Checkpoint_Manager::get_instance();
		$checkpoints = $manager->get_conversation_checkpoints( $conversation_id );

		if ( empty( $checkpoints ) ) {
			return [
				'success' => true,
				'data'    => [ 'checkpoints' => [] ],
				'message' => __( 'No undoable actions found in this conversation.', 'wp-agent' ),
			];
		}

		// Format for AI readability.
		$formatted = [];
		foreach ( $checkpoints as $cp ) {
			$formatted[] = [
				'checkpoint_id' => (int) $cp['id'],
				'action'        => $cp['action_type'],
				'entity_type'   => $cp['entity_type'],
				'entity_id'     => (int) $cp['entity_id'],
				'created_at'    => $cp['created_at'],
			];
		}

		return [
			'success' => true,
			'data'    => [ 'checkpoints' => $formatted ],
			'message' => sprintf(
				/* translators: %d: number of undoable checkpoints */
				__( 'Found %d undoable action(s). Use the "undo" operation with a checkpoint_id to revert.', 'wp-agent' ),
				count( $formatted )
			),
		];
	}

	/**
	 * Undo a specific checkpoint.
	 *
	 * @param array $params          Action parameters.
	 * @param int   $conversation_id Conversation ID.
	 * @return array Execution result.
	 */
	private function handle_undo( array $params, $conversation_id ): array {
		$checkpoint_id = isset( $params['checkpoint_id'] ) ? absint( $params['checkpoint_id'] ) : 0;

		if ( ! $checkpoint_id ) {
			return [
				'success' => false,
				'data'    => null,
				'message' => __( 'checkpoint_id is required for the undo operation. Use "list" first to see available checkpoints.', 'wp-agent' ),
			];
		}

		$manager = Checkpoint_Manager::get_instance();
		$result  = $manager->restore_checkpoint( $checkpoint_id, $conversation_id );

		return [
			'success' => $result['success'],
			'data'    => [
				'checkpoint_id' => $checkpoint_id,
				'restored'      => $result['success'],
			],
			'message' => $result['message'],
		];
	}
}
