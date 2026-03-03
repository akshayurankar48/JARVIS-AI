<?php
/**
 * History REST Controller.
 *
 * Provides conversation list and detail endpoints for the current user.
 * All queries are scoped to the authenticated user — no cross-user access.
 *
 * @package JarvisAI\REST
 * @since   1.0.0
 */

namespace JarvisAI\REST;

use JarvisAI\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Class History_Controller
 *
 * @since 1.0.0
 */
class History_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	const NAMESPACE = 'jarvis-ai/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	const ROUTE = '/history';

	/**
	 * Default items per page.
	 *
	 * @var int
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Maximum items per page.
	 *
	 * @var int
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * Register REST routes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_routes() {
		// GET /history — paginated conversation list.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_conversations' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => $this->get_list_args(),
			)
		);

		// GET /history/<id> — single conversation with messages.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE . '/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_conversation' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /history/<id>/rename — rename a conversation.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE . '/(?P<id>\d+)/rename',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rename_conversation' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id'    => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'title' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => function ( $value ) {
							if ( empty( trim( $value ) ) ) {
								return new \WP_Error(
									'empty_title',
									__( 'Title cannot be empty.', 'jarvis-ai' ),
									array( 'status' => 400 )
								);
							}
							return true;
						},
					),
				),
			)
		);

		// DELETE /history/<id> — delete a single conversation.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE . '/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_conversation' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// POST /history/bulk-delete — delete multiple conversations.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE . '/bulk-delete',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'bulk_delete_conversations' ),
				'permission_callback' => array( $this, 'check_permissions' ),
				'args'                => array(
					'ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'validate_callback' => function ( $value ) {
							if ( ! is_array( $value ) || empty( $value ) ) {
								return new \WP_Error(
									'invalid_ids',
									__( 'IDs must be a non-empty array.', 'jarvis-ai' ),
									array( 'status' => 400 )
								);
							}
							if ( count( $value ) > 50 ) {
								return new \WP_Error(
									'too_many_ids',
									__( 'Cannot delete more than 50 conversations at once.', 'jarvis-ai' ),
									array( 'status' => 400 )
								);
							}
							return true;
						},
						'sanitize_callback' => function ( $value ) {
							return array_map( 'absint', $value );
						},
					),
				),
			)
		);
	}

	/**
	 * Permission check — edit_posts required.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return bool|\WP_Error
	 */
	public function check_permissions( $request ) {
		if ( ! current_user_can( 'edit_posts' ) || ! REST_Permissions::current_user_has_allowed_role() ) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to view chat history.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * GET /jarvis-ai/v1/history
	 *
	 * Returns a paginated list of the current user's conversations.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response
	 */
	public function list_conversations( $request ) {
		global $wpdb;

		$tables   = Database::get_table_names();
		$user_id  = get_current_user_id();
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( self::MAX_PER_PAGE, max( 1, (int) $request->get_param( 'per_page' ) ?: self::DEFAULT_PER_PAGE ) );
		$offset   = ( $page - 1 ) * $per_page;
		$post_id  = $request->get_param( 'post_id' ) ? absint( $request->get_param( 'post_id' ) ) : 0;

		// Build WHERE clause — always scoped to current user, optionally filtered by post.
		$where = $wpdb->prepare( 'user_id = %d', $user_id );
		if ( $post_id ) {
			$where .= $wpdb->prepare( ' AND post_id = %d', $post_id );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
			"SELECT COUNT(*) FROM {$tables['conversations']} WHERE {$where}"
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$conversations = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
				"SELECT id, post_id, title, status, model, tokens_used, created_at, updated_at
				FROM {$tables['conversations']}
				WHERE {$where}
				ORDER BY updated_at DESC
				LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);

		if ( null === $conversations ) {
			$conversations = array();
		}

		// Cast numeric fields.
		foreach ( $conversations as &$conv ) {
			$conv['id']          = (int) $conv['id'];
			$conv['post_id']     = $conv['post_id'] ? (int) $conv['post_id'] : null;
			$conv['tokens_used'] = (int) $conv['tokens_used'];
		}
		unset( $conv );

		$response = rest_ensure_response(
			array(
				'conversations' => $conversations,
				'total'         => $total,
				'page'          => $page,
				'per_page'      => $per_page,
				'total_pages'   => (int) ceil( $total / $per_page ),
			)
		);

		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $per_page ) );

		return $response;
	}

	/**
	 * GET /jarvis-ai/v1/history/<id>
	 *
	 * Returns a single conversation with its messages.
	 * Verifies the current user owns the conversation.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_conversation( $request ) {
		global $wpdb;

		$tables          = Database::get_table_names();
		$conversation_id = (int) $request->get_param( 'id' );
		$user_id         = get_current_user_id();

		// Fetch conversation and verify ownership.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$conversation = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, user_id, post_id, title, status, model, tokens_used, created_at, updated_at
				FROM {$tables['conversations']}
				WHERE id = %d
				LIMIT 1",
				$conversation_id
			),
			ARRAY_A
		);

		if ( null === $conversation ) {
			return new \WP_Error(
				'not_found',
				__( 'Conversation not found.', 'jarvis-ai' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $conversation['user_id'] !== $user_id ) {
			return new \WP_Error(
				'forbidden',
				__( 'You do not have access to this conversation.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		// Cast numeric fields.
		$conversation['id']          = (int) $conversation['id'];
		$conversation['post_id']     = $conversation['post_id'] ? (int) $conversation['post_id'] : null;
		$conversation['tokens_used'] = (int) $conversation['tokens_used'];
		unset( $conversation['user_id'] ); // Don't expose user_id in response.

		// Fetch messages.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$messages = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, role, content, metadata, tokens, model, created_at
				FROM {$tables['messages']}
				WHERE conversation_id = %d
				ORDER BY id ASC",
				$conversation_id
			),
			ARRAY_A
		);

		if ( null === $messages ) {
			$messages = array();
		}

		// Parse message metadata and cast fields.
		foreach ( $messages as &$msg ) {
			$msg['id']     = (int) $msg['id'];
			$msg['tokens'] = (int) $msg['tokens'];

			if ( ! empty( $msg['metadata'] ) ) {
				$decoded         = json_decode( $msg['metadata'], true );
				$msg['metadata'] = is_array( $decoded ) ? $decoded : null;
			} else {
				$msg['metadata'] = null;
			}
		}
		unset( $msg );

		return rest_ensure_response(
			array(
				'conversation' => $conversation,
				'messages'     => $messages,
			)
		);
	}

	/**
	 * POST /jarvis-ai/v1/history/<id>/rename
	 *
	 * Renames a conversation. Verifies ownership.
	 *
	 * @since 1.1.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function rename_conversation( $request ) {
		global $wpdb;

		$tables          = Database::get_table_names();
		$conversation_id = (int) $request->get_param( 'id' );
		$user_id         = get_current_user_id();
		$title           = $request->get_param( 'title' );

		// Verify ownership.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$owner_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT user_id FROM {$tables['conversations']} WHERE id = %d LIMIT 1",
				$conversation_id
			)
		);

		if ( null === $owner_id ) {
			return new \WP_Error(
				'not_found',
				__( 'Conversation not found.', 'jarvis-ai' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $owner_id !== $user_id ) {
			return new \WP_Error(
				'forbidden',
				__( 'You do not have access to this conversation.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		// Truncate to 255 chars (column limit).
		if ( function_exists( 'mb_substr' ) ) {
			$title = mb_substr( $title, 0, 255, 'UTF-8' );
		} else {
			$title = substr( $title, 0, 255 );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$tables['conversations'],
			array( 'title' => $title ),
			array( 'id' => $conversation_id ),
			array( '%s' ),
			array( '%d' )
		);

		return rest_ensure_response(
			array(
				'id'    => $conversation_id,
				'title' => $title,
			)
		);
	}

	/**
	 * DELETE /jarvis-ai/v1/history/<id>
	 *
	 * Deletes a single conversation and its messages. Verifies ownership.
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_conversation( $request ) {
		global $wpdb;

		$tables          = Database::get_table_names();
		$conversation_id = (int) $request->get_param( 'id' );
		$user_id         = get_current_user_id();

		// Verify ownership.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$owner_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT user_id FROM {$tables['conversations']} WHERE id = %d LIMIT 1",
				$conversation_id
			)
		);

		if ( null === $owner_id ) {
			return new \WP_Error(
				'not_found',
				__( 'Conversation not found.', 'jarvis-ai' ),
				array( 'status' => 404 )
			);
		}

		if ( (int) $owner_id !== $user_id ) {
			return new \WP_Error(
				'forbidden',
				__( 'You do not have access to this conversation.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		// Delete messages first, then conversation.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$tables['messages'],
			array( 'conversation_id' => $conversation_id ),
			array( '%d' )
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$tables['conversations'],
			array(
				'id'      => $conversation_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $conversation_id,
			)
		);
	}

	/**
	 * POST /jarvis-ai/v1/history/bulk-delete
	 *
	 * Deletes multiple conversations and their messages. Verifies ownership of all IDs.
	 *
	 * @since 1.2.0
	 *
	 * @param \WP_REST_Request $request The request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function bulk_delete_conversations( $request ) {
		global $wpdb;

		$tables  = Database::get_table_names();
		$ids     = $request->get_param( 'ids' );
		$user_id = get_current_user_id();

		// Build safe placeholders for IN clause.
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// Verify all IDs belong to current user.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$owned_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$tables['conversations']} WHERE id IN ({$placeholders}) AND user_id = %d",
				array_merge( $ids, array( $user_id ) )
			)
		);

		if ( count( $ids ) !== $owned_count ) {
			return new \WP_Error(
				'forbidden',
				__( 'One or more conversations do not belong to you.', 'jarvis-ai' ),
				array( 'status' => 403 )
			);
		}

		// Delete messages for all conversations.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				"DELETE FROM {$tables['messages']} WHERE conversation_id IN ({$placeholders})",
				$ids
			)
		);

		// Delete conversations.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"DELETE FROM {$tables['conversations']} WHERE id IN ({$placeholders}) AND user_id = %d",
				array_merge( $ids, array( $user_id ) )
			)
		);

		return rest_ensure_response(
			array(
				'deleted' => true,
				'count'   => count( $ids ),
			)
		);
	}

	/**
	 * Define argument schema for GET /history.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_list_args() {
		return array(
			'page'     => array(
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'type'              => 'integer',
				'default'           => self::DEFAULT_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'post_id'  => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}
}
