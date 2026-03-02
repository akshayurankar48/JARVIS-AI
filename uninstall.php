<?php
/**
 * Uninstall WP Agent.
 *
 * Removes all plugin data when uninstalled via WP Admin > Plugins.
 *
 * @package WPAgent
 * @since 1.0.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Drop custom tables.
$tables = array(
	$wpdb->prefix . 'agent_conversations',
	$wpdb->prefix . 'agent_messages',
	$wpdb->prefix . 'agent_checkpoints',
	$wpdb->prefix . 'agent_history',
	$wpdb->prefix . 'agent_scheduled_tasks',
	$wpdb->prefix . 'agent_memory',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Delete all plugin options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'wp_agent_' ) . '%'
	)
);

// Delete all plugin transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wp_agent_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wp_agent_' ) . '%'
	)
);
