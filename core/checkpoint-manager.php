<?php
/**
 * Checkpoint Manager.
 *
 * Captures state snapshots before reversible actions execute and restores
 * them on undo. Each snapshot records the entity type, entity ID, and a
 * JSON-serializable representation of the entity's state at that moment.
 *
 * Supported entity types:
 * - post         Full WP_Post array + meta + thumbnail
 * - global_styles  The wp_global_styles post content JSON
 * - custom_css   The Additional CSS string
 * - option       A specific wp_options value
 * - plugin       Active plugins list snapshot
 * - theme        Active theme stylesheet
 * - menu         Nav menu structure (items + meta)
 * - taxonomy     Term data
 * - comment      Comment data
 * - template_part  Template part post content
 *
 * @package WPAgent\Core
 * @since   1.0.0
 */

namespace WPAgent\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Checkpoint_Manager
 *
 * @since 1.0.0
 */
class Checkpoint_Manager {

	/**
	 * Instance
	 *
	 * @access private
	 * @var Checkpoint_Manager|null
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Maximum checkpoints per conversation to prevent unbounded growth.
	 *
	 * @var int
	 */
	const MAX_CHECKPOINTS_PER_CONVERSATION = 50;

	/**
	 * Initiator
	 *
	 * @since 1.0.0
	 * @return Checkpoint_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Capture a before-snapshot for a reversible action.
	 *
	 * Inspects the action name and params to determine the entity type,
	 * entity ID, and current state. Returns null for actions where no
	 * meaningful snapshot can be captured (e.g. creation actions).
	 *
	 * @since 1.0.0
	 *
	 * @param string $action_name The action being executed.
	 * @param array  $params      The action parameters.
	 * @return array|null {
	 *     Snapshot data or null if not applicable.
	 *
	 *     @type string $entity_type Entity type identifier.
	 *     @type int    $entity_id   Entity ID (0 for global entities).
	 *     @type array  $snapshot    The captured state data.
	 * }
	 */
	public function capture_before( $action_name, array $params ) {
		switch ( $action_name ) {
			case 'edit_post':
			case 'delete_post':
			case 'set_page_template':
			case 'set_featured_image':
				$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
				if ( ! $post_id ) {
					return null;
				}
				return $this->snapshot_post( $post_id );

			case 'edit_global_styles':
				$operation = isset( $params['operation'] ) ? $params['operation'] : '';
				if ( 'update' !== $operation ) {
					return null;
				}
				return $this->snapshot_global_styles();

			case 'add_custom_css':
				$operation = isset( $params['operation'] ) ? $params['operation'] : '';
				if ( 'get' === $operation ) {
					return null;
				}
				return $this->snapshot_custom_css();

			case 'update_settings':
				return $this->snapshot_option( $params );

			case 'manage_permalinks':
				return $this->snapshot_permalinks();

			case 'activate_plugin':
			case 'deactivate_plugin':
				return $this->snapshot_active_plugins();

			case 'manage_theme':
				return $this->snapshot_active_theme();

			case 'manage_menus':
				return $this->snapshot_menu( $params );

			case 'manage_taxonomies':
				return $this->snapshot_taxonomy( $params );

			case 'manage_comments':
				return $this->snapshot_comment( $params );

			case 'manage_seo':
				$post_id = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
				if ( ! $post_id ) {
					return null;
				}
				return $this->snapshot_seo( $post_id );

			case 'edit_template_parts':
				return $this->snapshot_template_part( $params );

			// Creation actions: no "before" state to capture, but we still
			// record a checkpoint so the undo can delete the created entity.
			case 'create_post':
			case 'clone_post':
			case 'create_pattern':
			case 'create_user':
			case 'import_media':
			case 'generate_image':
			case 'install_plugin':
				return array(
					'entity_type' => $this->get_creation_entity_type( $action_name ),
					'entity_id'   => 0,
					'snapshot'    => array( '_creation' => true ),
				);

			default:
				return null;
		}
	}

	/**
	 * Save a checkpoint to the database.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param int    $message_id      Message ID (0 if not yet known).
	 * @param string $action_type     The action name.
	 * @param string $entity_type     Entity type.
	 * @param int    $entity_id       Entity ID.
	 * @param array  $snapshot_before Before state.
	 * @return int|false Checkpoint ID or false on failure.
	 */
	public function save_checkpoint( $conversation_id, $message_id, $action_type, $entity_type, $entity_id, array $snapshot_before ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// Enforce per-conversation limit by removing oldest checkpoints.
		$this->enforce_limit( $conversation_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$tables['checkpoints'],
			array(
				'conversation_id' => (int) $conversation_id,
				'message_id'      => (int) $message_id,
				'action_type'     => sanitize_text_field( $action_type ),
				'entity_type'     => sanitize_text_field( $entity_type ),
				'entity_id'       => (int) $entity_id,
				'snapshot_before' => wp_json_encode( $snapshot_before ),
				'is_restored'     => 0,
				'created_at'      => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s', '%d', '%s' )
		);

		return false !== $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Update a checkpoint's entity_id after a creation action completes.
	 *
	 * For create_post, clone_post, etc. the entity_id is only known after execution.
	 *
	 * @since 1.0.0
	 *
	 * @param int $checkpoint_id Checkpoint ID.
	 * @param int $entity_id     The newly created entity ID.
	 * @return void
	 */
	public function update_entity_id( $checkpoint_id, $entity_id ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$tables['checkpoints'],
			array( 'entity_id' => (int) $entity_id ),
			array( 'id' => (int) $checkpoint_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Get undoable checkpoints for a conversation.
	 *
	 * Returns checkpoints in reverse chronological order (newest first).
	 * Only includes checkpoints that haven't been restored yet.
	 *
	 * @since 1.0.0
	 *
	 * @param int $conversation_id Conversation ID.
	 * @param int $limit           Max checkpoints to return.
	 * @return array Array of checkpoint rows.
	 */
	public function get_conversation_checkpoints( $conversation_id, $limit = 10 ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id, action_type, entity_type, entity_id, is_restored, created_at
				FROM {$tables['checkpoints']}
				WHERE conversation_id = %d AND is_restored = 0
				ORDER BY id DESC
				LIMIT %d",
				(int) $conversation_id,
				(int) $limit
			),
			ARRAY_A
		);
	}

	/**
	 * Restore a checkpoint (undo an action).
	 *
	 * Reads the snapshot_before data and applies it to restore the entity
	 * to its previous state.
	 *
	 * @since 1.0.0
	 *
	 * @param int $checkpoint_id   Checkpoint ID to restore.
	 * @param int $conversation_id Conversation ID for ownership check.
	 * @return array {
	 *     @type bool   $success Whether the restore succeeded.
	 *     @type string $message Human-readable result.
	 * }
	 */
	public function restore_checkpoint( $checkpoint_id, $conversation_id ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$checkpoint = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$tables['checkpoints']}
				WHERE id = %d AND conversation_id = %d
				LIMIT 1",
				(int) $checkpoint_id,
				(int) $conversation_id
			),
			ARRAY_A
		);

		if ( ! $checkpoint ) {
			return array(
				'success' => false,
				'message' => __( 'Checkpoint not found or does not belong to this conversation.', 'wp-agent' ),
			);
		}

		if ( ! empty( $checkpoint['is_restored'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'This checkpoint has already been restored.', 'wp-agent' ),
			);
		}

		// Verify the current user has permission for the original action.
		$action_obj = \WPAgent\Actions\Action_Registry::get_instance()->get_action( $checkpoint['action_type'] );
		if ( $action_obj && ! current_user_can( $action_obj->get_capabilities_required() ) ) {
			return array(
				'success' => false,
				'message' => __( 'You do not have permission to undo this action.', 'wp-agent' ),
			);
		}

		$snapshot = json_decode( $checkpoint['snapshot_before'], true );
		if ( ! is_array( $snapshot ) ) {
			return array(
				'success' => false,
				'message' => __( 'Snapshot data is corrupt.', 'wp-agent' ),
			);
		}

		$entity_type = $checkpoint['entity_type'];
		$entity_id   = (int) $checkpoint['entity_id'];
		$action_type = $checkpoint['action_type'];

		// Handle creation undo (delete the created entity).
		if ( ! empty( $snapshot['_creation'] ) ) {
			$result = $this->undo_creation( $action_type, $entity_id );
		} else {
			$result = $this->apply_snapshot( $entity_type, $entity_id, $snapshot );
		}

		if ( $result['success'] ) {
			// Mark checkpoint as restored.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$tables['checkpoints'],
				array( 'is_restored' => 1 ),
				array( 'id' => (int) $checkpoint_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		return $result;
	}

	/**
	 * Delete a checkpoint row (e.g. when a creation action fails).
	 *
	 * @since 1.0.0
	 *
	 * @param int $checkpoint_id Checkpoint ID.
	 * @return void
	 */
	public function delete_checkpoint( $checkpoint_id ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$tables['checkpoints'],
			array( 'id' => (int) $checkpoint_id ),
			array( '%d' )
		);
	}

	// -------------------------------------------------------------------------
	// Snapshot capture methods
	// -------------------------------------------------------------------------

	/**
	 * Snapshot a WordPress post (full data + meta + thumbnail).
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_post( $post_id ) {
		$post = get_post( $post_id, ARRAY_A );
		if ( ! $post ) {
			return null;
		}

		$meta         = get_post_meta( $post_id );
		$thumbnail_id = get_post_thumbnail_id( $post_id );

		return array(
			'entity_type' => 'post',
			'entity_id'   => $post_id,
			'snapshot'    => array(
				'post'         => $post,
				'meta'         => $meta,
				'thumbnail_id' => $thumbnail_id ? (int) $thumbnail_id : 0,
			),
		);
	}

	/**
	 * Snapshot the global styles.
	 *
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_global_styles() {
		$stylesheet = get_stylesheet();
		$query      = new \WP_Query(
			array(
				'post_type'      => 'wp_global_styles',
				'post_status'    => array( 'publish', 'draft' ),
				'name'           => 'wp-global-styles-' . urlencode( $stylesheet ),
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		wp_reset_postdata();

		if ( ! $query->have_posts() ) {
			return array(
				'entity_type' => 'global_styles',
				'entity_id'   => 0,
				'snapshot'    => array(
					'content' => null,
					'exists'  => false,
				),
			);
		}

		$post = $query->posts[0];

		return array(
			'entity_type' => 'global_styles',
			'entity_id'   => (int) $post->ID,
			'snapshot'    => array(
				'content' => $post->post_content,
				'exists'  => true,
			),
		);
	}

	/**
	 * Snapshot the custom CSS.
	 *
	 * @return array Snapshot data.
	 */
	private function snapshot_custom_css() {
		return array(
			'entity_type' => 'custom_css',
			'entity_id'   => 0,
			'snapshot'    => array(
				'css' => wp_get_custom_css(),
			),
		);
	}

	/**
	 * Snapshot a single option before settings update.
	 *
	 * Only snapshots options from the Update_Settings ALLOWED_OPTIONS whitelist.
	 *
	 * @param array $params Action parameters (option_name, option_value).
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_option( array $params ) {
		$option_name = isset( $params['option_name'] ) ? sanitize_key( $params['option_name'] ) : '';
		if ( empty( $option_name ) ) {
			return null;
		}

		// Only snapshot whitelisted options.
		if ( ! in_array( $option_name, \WPAgent\Actions\Update_Settings::ALLOWED_OPTIONS, true ) ) {
			return null;
		}

		return array(
			'entity_type' => 'option',
			'entity_id'   => 0,
			'snapshot'    => array(
				$option_name => get_option( $option_name ),
			),
		);
	}

	/**
	 * Snapshot the permalink structure.
	 *
	 * @return array Snapshot data.
	 */
	private function snapshot_permalinks() {
		return array(
			'entity_type' => 'option',
			'entity_id'   => 0,
			'snapshot'    => array(
				'permalink_structure' => get_option( 'permalink_structure' ),
			),
		);
	}

	/**
	 * Snapshot the active plugins list.
	 *
	 * @return array Snapshot data.
	 */
	private function snapshot_active_plugins() {
		return array(
			'entity_type' => 'plugin',
			'entity_id'   => 0,
			'snapshot'    => array(
				'active_plugins' => get_option( 'active_plugins', array() ),
			),
		);
	}

	/**
	 * Snapshot the active theme.
	 *
	 * @return array Snapshot data.
	 */
	private function snapshot_active_theme() {
		return array(
			'entity_type' => 'theme',
			'entity_id'   => 0,
			'snapshot'    => array(
				'stylesheet' => get_option( 'stylesheet' ),
				'template'   => get_option( 'template' ),
			),
		);
	}

	/**
	 * Snapshot a navigation menu.
	 *
	 * @param array $params Action parameters.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_menu( array $params ) {
		$menu_id = isset( $params['menu_id'] ) ? absint( $params['menu_id'] ) : 0;

		if ( ! $menu_id ) {
			// No specific menu to snapshot — capture menu locations.
			return array(
				'entity_type' => 'menu',
				'entity_id'   => 0,
				'snapshot'    => array(
					'locations' => get_nav_menu_locations(),
				),
			);
		}

		$menu = wp_get_nav_menu_object( $menu_id );
		if ( ! $menu ) {
			return null;
		}

		$items = wp_get_nav_menu_items( $menu_id, array( 'update_post_term_cache' => false ) );

		return array(
			'entity_type' => 'menu',
			'entity_id'   => $menu_id,
			'snapshot'    => array(
				'menu'  => array(
					'name'        => $menu->name,
					'description' => $menu->description,
				),
				'items' => $items ? array_map(
					function ( $item ) {
						return get_object_vars( $item );
					},
					$items
				) : array(),
			),
		);
	}

	/**
	 * Snapshot a taxonomy term.
	 *
	 * @param array $params Action parameters.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_taxonomy( array $params ) {
		$term_id = isset( $params['term_id'] ) ? absint( $params['term_id'] ) : 0;

		if ( ! $term_id ) {
			return null;
		}

		$term = get_term( $term_id );
		if ( is_wp_error( $term ) || ! $term ) {
			return null;
		}

		return array(
			'entity_type' => 'taxonomy',
			'entity_id'   => $term_id,
			'snapshot'    => array(
				'term' => get_object_vars( $term ),
			),
		);
	}

	/**
	 * Snapshot a comment.
	 *
	 * @param array $params Action parameters.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_comment( array $params ) {
		$comment_id = isset( $params['comment_id'] ) ? absint( $params['comment_id'] ) : 0;

		if ( ! $comment_id ) {
			return null;
		}

		$comment = get_comment( $comment_id, ARRAY_A );
		if ( ! $comment ) {
			return null;
		}

		return array(
			'entity_type' => 'comment',
			'entity_id'   => $comment_id,
			'snapshot'    => array(
				'comment' => $comment,
			),
		);
	}

	/**
	 * Snapshot SEO meta for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_seo( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		$seo_keys = array(
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'_yoast_wpseo_focuskw',
			'rank_math_title',
			'rank_math_description',
			'rank_math_focus_keyword',
			'_aioseo_title',
			'_aioseo_description',
		);

		$meta = array();
		foreach ( $seo_keys as $key ) {
			$value = get_post_meta( $post_id, $key, true );
			if ( '' !== $value ) {
				$meta[ $key ] = $value;
			}
		}

		return array(
			'entity_type' => 'seo',
			'entity_id'   => $post_id,
			'snapshot'    => array(
				'meta' => $meta,
			),
		);
	}

	/**
	 * Snapshot a template part.
	 *
	 * @param array $params Action parameters.
	 * @return array|null Snapshot data or null.
	 */
	private function snapshot_template_part( array $params ) {
		$slug = isset( $params['slug'] ) ? sanitize_title( $params['slug'] ) : '';

		if ( empty( $slug ) ) {
			return null;
		}

		// Look up the template part post.
		$query = new \WP_Query(
			array(
				'post_type'      => 'wp_template_part',
				'post_status'    => array( 'publish', 'draft' ),
				'name'           => $slug,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);

		wp_reset_postdata();

		if ( ! $query->have_posts() ) {
			return null;
		}

		$post = $query->posts[0];

		return array(
			'entity_type' => 'template_part',
			'entity_id'   => (int) $post->ID,
			'snapshot'    => array(
				'post_content' => $post->post_content,
				'post_title'   => $post->post_title,
				'slug'         => $slug,
			),
		);
	}

	// -------------------------------------------------------------------------
	// Restore methods
	// -------------------------------------------------------------------------

	/**
	 * Apply a snapshot to restore an entity to its previous state.
	 *
	 * @param string $entity_type Entity type.
	 * @param int    $entity_id   Entity ID.
	 * @param array  $snapshot    Snapshot data.
	 * @return array { success, message }
	 */
	private function apply_snapshot( $entity_type, $entity_id, array $snapshot ) {
		switch ( $entity_type ) {
			case 'post':
				return $this->restore_post( $entity_id, $snapshot );

			case 'global_styles':
				return $this->restore_global_styles( $entity_id, $snapshot );

			case 'custom_css':
				return $this->restore_custom_css( $snapshot );

			case 'option':
				return $this->restore_options( $snapshot );

			case 'plugin':
				return $this->restore_active_plugins( $snapshot );

			case 'theme':
				return $this->restore_theme( $snapshot );

			case 'menu':
				return $this->restore_menu( $entity_id, $snapshot );

			case 'taxonomy':
				return $this->restore_taxonomy( $entity_id, $snapshot );

			case 'comment':
				return $this->restore_comment( $entity_id, $snapshot );

			case 'seo':
				return $this->restore_seo( $entity_id, $snapshot );

			case 'template_part':
				return $this->restore_template_part( $entity_id, $snapshot );

			default:
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: entity type */
						__( 'Unknown entity type for restore: %s', 'wp-agent' ),
						$entity_type
					),
				);
		}
	}

	/**
	 * Restore a post from snapshot.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_post( $post_id, array $snapshot ) {
		if ( empty( $snapshot['post'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No post data in snapshot.', 'wp-agent' ),
			);
		}

		$post_data = $snapshot['post'];

		// If the post was trashed (delete_post undo), untrash it.
		$current = get_post( $post_id );
		if ( $current && 'trash' === $current->post_status && isset( $post_data['post_status'] ) && 'trash' !== $post_data['post_status'] ) {
			wp_untrash_post( $post_id );
		}

		// Restore post fields.
		$update_args = array(
			'ID'           => $post_id,
			'post_title'   => $post_data['post_title'] ?? '',
			'post_content' => $post_data['post_content'] ?? '',
			'post_excerpt' => $post_data['post_excerpt'] ?? '',
			'post_status'  => $post_data['post_status'] ?? 'draft',
			'post_name'    => $post_data['post_name'] ?? '',
			'post_parent'  => $post_data['post_parent'] ?? 0,
		);

		$result = wp_update_post( $update_args, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		// Restore post meta.
		if ( ! empty( $snapshot['meta'] ) && is_array( $snapshot['meta'] ) ) {
			$protected_keys = array( '_edit_lock', '_edit_last' );
			foreach ( $snapshot['meta'] as $meta_key => $meta_values ) {
				if ( in_array( $meta_key, $protected_keys, true ) ) {
					continue;
				}
				delete_post_meta( $post_id, $meta_key );
				foreach ( (array) $meta_values as $meta_value ) {
					add_post_meta( $post_id, $meta_key, maybe_unserialize( $meta_value ) );
				}
			}
		}

		// Restore thumbnail.
		if ( isset( $snapshot['thumbnail_id'] ) ) {
			if ( $snapshot['thumbnail_id'] > 0 ) {
				set_post_thumbnail( $post_id, $snapshot['thumbnail_id'] );
			} else {
				delete_post_thumbnail( $post_id );
			}
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: post ID */
				__( 'Restored post #%d to its previous state.', 'wp-agent' ),
				$post_id
			),
		);
	}

	/**
	 * Restore global styles from snapshot.
	 *
	 * @param int   $post_id  The wp_global_styles post ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_global_styles( $post_id, array $snapshot ) {
		if ( ! $snapshot['exists'] ) {
			// The global styles post didn't exist before — delete it.
			if ( $post_id > 0 ) {
				wp_delete_post( $post_id, true );
			}
			if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
				wp_clean_theme_json_cache();
			}
			return array(
				'success' => true,
				'message' => __( 'Restored global styles (removed custom overrides).', 'wp-agent' ),
			);
		}

		if ( $post_id > 0 ) {
			$result = wp_update_post(
				array(
					'ID'           => $post_id,
					'post_content' => $snapshot['content'],
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return array(
					'success' => false,
					'message' => $result->get_error_message(),
				);
			}
		}

		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}

		return array(
			'success' => true,
			'message' => __( 'Restored global styles to their previous state.', 'wp-agent' ),
		);
	}

	/**
	 * Restore custom CSS from snapshot.
	 *
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_custom_css( array $snapshot ) {
		$css    = isset( $snapshot['css'] ) ? $snapshot['css'] : '';
		$result = wp_update_custom_css_post( $css );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Restored custom CSS to its previous state.', 'wp-agent' ),
		);
	}

	/**
	 * Restore option values from snapshot.
	 *
	 * Only restores options in the Update_Settings ALLOWED_OPTIONS whitelist.
	 *
	 * @param array $snapshot Key-value pairs of option names and values.
	 * @return array { success, message }
	 */
	private function restore_options( array $snapshot ) {
		$allowed  = array_flip( \WPAgent\Actions\Update_Settings::ALLOWED_OPTIONS );
		$restored = 0;

		foreach ( $snapshot as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}
			update_option( $key, $value );
			++$restored;
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: number of options restored */
				__( 'Restored %d option(s) to their previous values.', 'wp-agent' ),
				$restored
			),
		);
	}

	/**
	 * Restore active plugins list from snapshot.
	 *
	 * Uses activate_plugins/deactivate_plugins to properly fire lifecycle hooks
	 * instead of raw option writes.
	 *
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_active_plugins( array $snapshot ) {
		if ( ! isset( $snapshot['active_plugins'] ) || ! is_array( $snapshot['active_plugins'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No plugin data in snapshot.', 'wp-agent' ),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$target  = array_map( 'sanitize_text_field', $snapshot['active_plugins'] );
		$current = get_option( 'active_plugins', array() );

		// Validate each slug looks like a valid plugin file path.
		$target = array_filter(
			$target,
			static function ( $slug ) {
				return (bool) preg_match( '#^[a-z0-9_\-]+/[a-z0-9_\-]+\.php$#i', $slug );
			}
		);

		$to_deactivate = array_diff( $current, $target );
		$to_activate   = array_diff( $target, $current );

		if ( ! empty( $to_deactivate ) ) {
			deactivate_plugins( array_values( $to_deactivate ) );
		}
		if ( ! empty( $to_activate ) ) {
			activate_plugins( array_values( $to_activate ) );
		}

		return array(
			'success' => true,
			'message' => __( 'Restored active plugins to their previous state.', 'wp-agent' ),
		);
	}

	/**
	 * Restore the active theme from snapshot.
	 *
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_theme( array $snapshot ) {
		if ( empty( $snapshot['stylesheet'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No theme data in snapshot.', 'wp-agent' ),
			);
		}

		switch_theme( $snapshot['stylesheet'] );

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: theme stylesheet */
				__( 'Restored theme to "%s".', 'wp-agent' ),
				$snapshot['stylesheet']
			),
		);
	}

	/**
	 * Restore a menu from snapshot.
	 *
	 * @param int   $menu_id  Menu ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_menu( $menu_id, array $snapshot ) {
		// Restore menu locations if that's what was captured.
		if ( isset( $snapshot['locations'] ) && ! $menu_id ) {
			set_theme_mod( 'nav_menu_locations', $snapshot['locations'] );
			return array(
				'success' => true,
				'message' => __( 'Restored menu locations to their previous state.', 'wp-agent' ),
			);
		}

		if ( isset( $snapshot['menu'] ) && $menu_id ) {
			wp_update_nav_menu_object( $menu_id, $snapshot['menu'] );
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: menu ID */
				__( 'Restored menu #%d to its previous state.', 'wp-agent' ),
				$menu_id
			),
		);
	}

	/**
	 * Restore a taxonomy term from snapshot.
	 *
	 * @param int   $term_id  Term ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_taxonomy( $term_id, array $snapshot ) {
		if ( empty( $snapshot['term'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No term data in snapshot.', 'wp-agent' ),
			);
		}

		$term_data = $snapshot['term'];
		$taxonomy  = $term_data['taxonomy'] ?? 'category';

		wp_update_term(
			$term_id,
			$taxonomy,
			array(
				'name'        => $term_data['name'] ?? '',
				'slug'        => $term_data['slug'] ?? '',
				'description' => $term_data['description'] ?? '',
				'parent'      => $term_data['parent'] ?? 0,
			)
		);

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: term ID */
				__( 'Restored term #%d to its previous state.', 'wp-agent' ),
				$term_id
			),
		);
	}

	/**
	 * Restore a comment from snapshot.
	 *
	 * @param int   $comment_id Comment ID.
	 * @param array $snapshot   Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_comment( $comment_id, array $snapshot ) {
		if ( empty( $snapshot['comment'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No comment data in snapshot.', 'wp-agent' ),
			);
		}

		$result = wp_update_comment( $snapshot['comment'] );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: comment ID */
				__( 'Restored comment #%d to its previous state.', 'wp-agent' ),
				$comment_id
			),
		);
	}

	/**
	 * Restore SEO meta from snapshot.
	 *
	 * @param int   $post_id  Post ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_seo( $post_id, array $snapshot ) {
		if ( ! isset( $snapshot['meta'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No SEO meta in snapshot.', 'wp-agent' ),
			);
		}

		foreach ( $snapshot['meta'] as $key => $value ) {
			update_post_meta( $post_id, sanitize_key( $key ), $value );
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %d: post ID */
				__( 'Restored SEO meta for post #%d.', 'wp-agent' ),
				$post_id
			),
		);
	}

	/**
	 * Restore a template part from snapshot.
	 *
	 * @param int   $post_id  Template part post ID.
	 * @param array $snapshot Snapshot data.
	 * @return array { success, message }
	 */
	private function restore_template_part( $post_id, array $snapshot ) {
		if ( ! isset( $snapshot['post_content'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'No template part content in snapshot.', 'wp-agent' ),
			);
		}

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $snapshot['post_content'],
				'post_title'   => $snapshot['post_title'] ?? '',
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => sprintf(
				/* translators: %s: template part slug */
				__( 'Restored template part "%s" to its previous state.', 'wp-agent' ),
				$snapshot['slug'] ?? $post_id
			),
		);
	}

	// -------------------------------------------------------------------------
	// Undo creation actions (delete the created entity)
	// -------------------------------------------------------------------------

	/**
	 * Undo a creation action by deleting the created entity.
	 *
	 * @param string $action_type Original action that created the entity.
	 * @param int    $entity_id   The ID of the created entity.
	 * @return array { success, message }
	 */
	private function undo_creation( $action_type, $entity_id ) {
		if ( ! $entity_id ) {
			return array(
				'success' => false,
				'message' => __( 'Cannot undo creation: entity ID is unknown.', 'wp-agent' ),
			);
		}

		switch ( $action_type ) {
			case 'create_post':
			case 'clone_post':
				$result = wp_trash_post( $entity_id );
				return $result
					? array(
						'success' => true,
						'message' => sprintf(
							/* translators: %d: post ID */
							__( 'Moved created post #%d to trash.', 'wp-agent' ),
							$entity_id
						),
					)
					: array(
						'success' => false,
						'message' => __( 'Failed to trash the created post.', 'wp-agent' ),
					);

			case 'create_pattern':
				$result = wp_delete_post( $entity_id, true );
				return $result
					? array(
						'success' => true,
						'message' => sprintf(
							/* translators: %d: pattern post ID */
							__( 'Deleted created pattern #%d.', 'wp-agent' ),
							$entity_id
						),
					)
					: array(
						'success' => false,
						'message' => __( 'Failed to delete the created pattern.', 'wp-agent' ),
					);

			case 'import_media':
			case 'generate_image':
				$result = wp_delete_attachment( $entity_id, true );
				return $result
					? array(
						'success' => true,
						'message' => sprintf(
							/* translators: %d: attachment ID */
							__( 'Deleted created media #%d.', 'wp-agent' ),
							$entity_id
						),
					)
					: array(
						'success' => false,
						'message' => __( 'Failed to delete the created media.', 'wp-agent' ),
					);

			case 'create_user':
				// Reassign content to the current user before deletion.
				$reassign = get_current_user_id();
				$result   = wp_delete_user( $entity_id, $reassign );
				return $result
					? array(
						'success' => true,
						'message' => sprintf(
							/* translators: %d: user ID */
							__( 'Deleted created user #%d.', 'wp-agent' ),
							$entity_id
						),
					)
					: array(
						'success' => false,
						'message' => __( 'Failed to delete the created user.', 'wp-agent' ),
					);

			case 'install_plugin':
				// Cannot safely uninstall — just report.
				return array(
					'success' => false,
					'message' => __( 'Cannot automatically uninstall a plugin. Please remove it manually via Plugins > Installed Plugins.', 'wp-agent' ),
				);

			default:
				return array(
					'success' => false,
					'message' => sprintf(
						/* translators: %s: action type */
						__( 'Undo not supported for creation action: %s', 'wp-agent' ),
						$action_type
					),
				);
		}
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Get the entity type for a creation action.
	 *
	 * @param string $action_name Action name.
	 * @return string Entity type identifier.
	 */
	private function get_creation_entity_type( $action_name ) {
		$map = array(
			'create_post'    => 'post',
			'clone_post'     => 'post',
			'create_pattern' => 'pattern',
			'create_user'    => 'user',
			'import_media'   => 'attachment',
			'generate_image' => 'attachment',
			'install_plugin' => 'plugin',
		);

		return isset( $map[ $action_name ] ) ? $map[ $action_name ] : 'unknown';
	}

	/**
	 * Enforce the maximum checkpoints per conversation.
	 *
	 * Deletes the oldest checkpoints when the limit is exceeded.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return void
	 */
	private function enforce_limit( $conversation_id ) {
		global $wpdb;

		$tables = Database::get_table_names();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$tables['checkpoints']} WHERE conversation_id = %d",
				(int) $conversation_id
			)
		);

		if ( $count >= self::MAX_CHECKPOINTS_PER_CONVERSATION ) {
			$excess = $count - self::MAX_CHECKPOINTS_PER_CONVERSATION + 1;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"DELETE FROM {$tables['checkpoints']}
					WHERE conversation_id = %d
					ORDER BY id ASC
					LIMIT %d",
					(int) $conversation_id,
					(int) $excess
				)
			);
		}
	}
}
