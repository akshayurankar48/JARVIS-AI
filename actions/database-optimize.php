<?php
/**
 * Database Optimize Action.
 *
 * Analyzes and cleans the WordPress database: post revisions, transients,
 * spam/trash comments, and table optimization.
 *
 * @package JarvisAI\Actions
 * @since   1.1.0
 */

namespace JarvisAI\Actions;

defined( 'ABSPATH' ) || exit;

/**
 * Class Database_Optimize
 *
 * @since 1.1.0
 */
class Database_Optimize implements Action_Interface {

	/**
	 * Get the action name.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_name(): string {
		return 'database_optimize';
	}

	/**
	 * Get the action description.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_description(): string {
		return 'Analyze and optimize the WordPress database. Can clean post revisions, expired transients, '
			. 'spam/trash comments, trashed posts, and optimize database tables. '
			. 'Use "analyze" first to see what can be cleaned, then run specific clean operations.';
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
				'operation'      => array(
					'type'        => 'string',
					'enum'        => array( 'analyze', 'clean_revisions', 'clean_transients', 'clean_spam', 'clean_trash', 'optimize_tables' ),
					'description' => 'Operation: "analyze" counts cleanable items, others perform the cleanup.',
				),
				'keep_revisions' => array(
					'type'        => 'integer',
					'description' => 'Number of revisions to keep per post when cleaning. Defaults to 5.',
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
		$operation = $params['operation'] ?? '';

		switch ( $operation ) {
			case 'analyze':
				return $this->analyze();
			case 'clean_revisions':
				return $this->clean_revisions( $params );
			case 'clean_transients':
				return $this->clean_transients();
			case 'clean_spam':
				return $this->clean_spam_comments();
			case 'clean_trash':
				return $this->clean_trash();
			case 'optimize_tables':
				return $this->optimize_tables();
			default:
				return array(
					'success' => false,
					'data'    => null,
					'message' => __( 'Invalid operation.', 'jarvis-ai' ),
				);
		}
	}

	/**
	 * Analyze the database for cleanable items.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function analyze() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$revisions = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		$transients = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()"
		);

		$spam_comments = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam' )
		);

		$trash_comments = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'trash' )
		);

		$trash_posts = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'trash' )
		);

		$auto_drafts = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft' )
		);

		// Table sizes.
		$db_name = DB_NAME;
		$tables  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT table_name AS name,
					ROUND(data_length / 1024 / 1024, 2) AS data_mb,
					ROUND(index_length / 1024 / 1024, 2) AS index_mb,
					ROUND(data_free / 1024 / 1024, 2) AS overhead_mb
				FROM information_schema.TABLES
				WHERE table_schema = %s AND table_name LIKE %s',
				$db_name,
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			),
			ARRAY_A
		);

		$total_overhead = 0;
		foreach ( $tables as $table ) {
			$total_overhead += (float) $table['overhead_mb'];
		}

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array(
				'revisions'      => $revisions,
				'transients'     => $transients,
				'spam_comments'  => $spam_comments,
				'trash_comments' => $trash_comments,
				'trash_posts'    => $trash_posts,
				'auto_drafts'    => $auto_drafts,
				'overhead_mb'    => round( $total_overhead, 2 ),
				'table_count'    => count( $tables ),
			),
			'message' => sprintf(
				/* translators: 1: revisions, 2: transients, 3: spam, 4: overhead */
				__( 'Database analysis: %1$d revisions, %2$d expired transients, %3$d spam comments, %4$s MB overhead.', 'jarvis-ai' ),
				$revisions,
				$transients,
				$spam_comments,
				round( $total_overhead, 2 )
			),
		);
	}

	/**
	 * Clean post revisions.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params Parameters.
	 * @return array Result.
	 */
	private function clean_revisions( array $params ) {
		global $wpdb;

		$keep = isset( $params['keep_revisions'] ) ? absint( $params['keep_revisions'] ) : 5;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( 0 === $keep ) {
			$deleted = $wpdb->query(
				"DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
			);
		} else {
			// Delete revisions beyond the keep limit per post.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE r FROM {$wpdb->posts} r
					INNER JOIN (
						SELECT id, post_parent,
							ROW_NUMBER() OVER (PARTITION BY post_parent ORDER BY post_date DESC) AS rn
						FROM {$wpdb->posts}
						WHERE post_type = 'revision'
					) ranked ON r.id = ranked.id
					WHERE ranked.rn > %d",
					$keep
				)
			);
		}

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array(
				'deleted' => (int) $deleted,
				'kept'    => $keep,
			),
			'message' => sprintf(
				/* translators: 1: deleted count, 2: kept per post */
				__( 'Deleted %1$d revision(s), keeping %2$d per post.', 'jarvis-ai' ),
				(int) $deleted,
				$keep
			),
		);
	}

	/**
	 * Clean expired transients.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function clean_transients() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Delete expired transient timeouts.
		$expired_timeouts = $wpdb->get_col(
			"SELECT option_name FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_timeout_%'
			AND option_value < UNIX_TIMESTAMP()"
		);

		$deleted = 0;
		foreach ( $expired_timeouts as $timeout_key ) {
			$transient_key = str_replace( '_transient_timeout_', '_transient_', $timeout_key );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $timeout_key ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s", $transient_key ) );
			++$deleted;
		}

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array( 'deleted' => $deleted ),
			'message' => sprintf(
				/* translators: %d: deleted count */
				__( 'Deleted %d expired transient(s).', 'jarvis-ai' ),
				$deleted
			),
		);
	}

	/**
	 * Clean spam comments.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function clean_spam_comments() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam' )
		);

		return array(
			'success' => true,
			'data'    => array( 'deleted' => (int) $deleted ),
			'message' => sprintf(
				/* translators: %d: deleted count */
				__( 'Deleted %d spam comment(s).', 'jarvis-ai' ),
				(int) $deleted
			),
		);
	}

	/**
	 * Clean trashed content.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function clean_trash() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$trash_posts = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'trash' )
		);

		$trash_comments = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->comments} WHERE comment_approved = %s", 'trash' )
		);

		$auto_drafts = $wpdb->query(
			$wpdb->prepare( "DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft' )
		);

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array(
				'trash_posts'    => (int) $trash_posts,
				'trash_comments' => (int) $trash_comments,
				'auto_drafts'    => (int) $auto_drafts,
			),
			'message' => sprintf(
				/* translators: 1: posts, 2: comments, 3: auto-drafts */
				__( 'Cleaned: %1$d trashed post(s), %2$d trashed comment(s), %3$d auto-draft(s).', 'jarvis-ai' ),
				(int) $trash_posts,
				(int) $trash_comments,
				(int) $auto_drafts
			),
		);
	}

	/**
	 * Optimize database tables.
	 *
	 * @since 1.1.0
	 * @return array Result.
	 */
	private function optimize_tables() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$tables = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT table_name FROM information_schema.TABLES WHERE table_schema = %s AND table_name LIKE %s',
				DB_NAME,
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);

		$optimized = 0;
		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "OPTIMIZE TABLE `{$table}`" );
			++$optimized;
		}

		// phpcs:enable

		return array(
			'success' => true,
			'data'    => array( 'tables_optimized' => $optimized ),
			'message' => sprintf(
				/* translators: %d: table count */
				__( 'Optimized %d database table(s).', 'jarvis-ai' ),
				$optimized
			),
		);
	}
}
