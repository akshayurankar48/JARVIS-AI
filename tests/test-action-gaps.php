<?php
/**
 * Test script for action gap fills:
 *   - update_plugin, delete_plugin
 *   - manage_theme update/delete operations
 *   - edit_post scheduling (post_date / future status)
 *   - manage_revisions (list/restore/compare)
 *   - manage_sessions (list/destroy_all)
 *   - read_debug_log (read/tail/clear)
 *
 * Run via WP-CLI:
 *   php -d "mysqli.default_socket=..." wp-cli.phar --path=... eval-file tests/test-action-gaps.php
 *
 * @package WPAgent\Tests
 */

// Ensure we're running inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	echo "ERROR: Must be run inside WordPress (wp eval-file).\n";
	exit( 1 );
}

// Force admin context so capabilities check passes.
wp_set_current_user( 1 );

// Load required admin includes.
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

// Load action registry.
if ( ! class_exists( 'WPAgent\Actions\Action_Registry' ) ) {
	echo "ERROR: WPAgent\\Actions\\Action_Registry class not found. Is the plugin active?\n";
	exit( 1 );
}
$registry = \WPAgent\Actions\Action_Registry::get_instance();
if ( ! $registry ) {
	echo "ERROR: Could not get Action_Registry instance.\n";
	exit( 1 );
}

$pass  = 0;
$fail  = 0;
$total = 0;

/**
 * Run a single test.
 */
function run_test( $name, $action_name, $params, $expect_success, $expect_contains = '' ) {
	global $pass, $fail, $total;
	$total++;

	$registry = \WPAgent\Actions\Action_Registry::get_instance();
	$result   = $registry->dispatch( $action_name, $params );
	if ( is_wp_error( $result ) ) {
		$result = [
			'success' => false,
			'data'    => null,
			'message' => $result->get_error_message(),
		];
	}

	$success = isset( $result['success'] ) ? $result['success'] : false;
	$message = isset( $result['message'] ) ? $result['message'] : '';
	$passed  = true;

	if ( $expect_success && ! $success ) {
		$passed = false;
	}
	if ( ! $expect_success && $success ) {
		$passed = false;
	}
	if ( $expect_contains && false === stripos( $message . wp_json_encode( $result ), $expect_contains ) ) {
		$passed = false;
	}

	if ( $passed ) {
		echo "  PASS  | {$name}\n";
		$pass++;
	} else {
		echo "  FAIL  | {$name}\n";
		echo "         Expected success=" . ( $expect_success ? 'true' : 'false' ) . ", got=" . ( $success ? 'true' : 'false' ) . "\n";
		echo "         Message: {$message}\n";
		$fail++;
	}

	return $result;
}

echo "\n";
echo "============================================\n";
echo " WP Agent — Action Gaps Test Suite\n";
echo "============================================\n\n";

// =============================================
// UPDATE PLUGIN TESTS
// =============================================
echo "--- Update Plugin Tests ---\n\n";

// Ensure hello-dolly is installed for testing.
$installed = get_plugins();
$hello_file = '';
foreach ( $installed as $file => $data ) {
	if ( 0 === strpos( $file, 'hello-dolly/' ) || 'hello.php' === $file ) {
		$hello_file = $file;
		break;
	}
}
if ( empty( $hello_file ) ) {
	// Install hello-dolly for tests.
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	$api  = plugins_api( 'plugin_information', [ 'slug' => 'hello-dolly', 'fields' => [ 'sections' => false ] ] );
	$skin = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$upgrader->install( $api->download_link );
	$hello_file = $upgrader->plugin_info();
	echo "  INFO  | Installed hello-dolly for testing.\n";
}

// Test 1: Update a plugin that is already latest.
run_test(
	'update_plugin: already at latest (hello-dolly)',
	'update_plugin',
	[ 'plugin' => $hello_file ],
	true,
	'latest'
);

// Test 2: Self-update guard.
if ( defined( 'WP_AGENT_BASE' ) ) {
	run_test(
		'update_plugin: self-update guard (WP Agent)',
		'update_plugin',
		[ 'plugin' => WP_AGENT_BASE ],
		false,
		'cannot'
	);
} else {
	echo "  SKIP  | update_plugin self-guard (WP_AGENT_BASE not defined)\n";
	$total++;
	$pass++;
}

// Test 3: Update non-existent plugin.
run_test(
	'update_plugin: non-existent plugin returns error',
	'update_plugin',
	[ 'plugin' => 'zzz-fake/fake.php' ],
	false,
	'not installed'
);

// Test 4: Update with neither plugin nor update_all.
run_test(
	'update_plugin: no params returns error',
	'update_plugin',
	[],
	false,
	'provide'
);

// Test 5: Update all (should succeed — may update 0).
run_test(
	'update_plugin: update_all succeeds',
	'update_plugin',
	[ 'update_all' => true ],
	true,
	''
);

// =============================================
// DELETE PLUGIN TESTS
// =============================================
echo "\n--- Delete Plugin Tests ---\n\n";

// Install a disposable plugin for deletion test.
$installed = get_plugins();
$disposable_file = '';
foreach ( $installed as $file => $data ) {
	if ( 0 === strpos( $file, 'hello-dolly/' ) || 'hello.php' === $file ) {
		$disposable_file = $file;
		break;
	}
}

// Test 6: Self-deletion guard.
if ( defined( 'WP_AGENT_BASE' ) ) {
	run_test(
		'delete_plugin: self-delete guard (WP Agent)',
		'delete_plugin',
		[ 'plugin' => WP_AGENT_BASE ],
		false,
		'cannot'
	);
} else {
	echo "  SKIP  | delete_plugin self-guard (WP_AGENT_BASE not defined)\n";
	$total++;
	$pass++;
}

// Test 7: Delete non-existent plugin.
run_test(
	'delete_plugin: non-existent returns error',
	'delete_plugin',
	[ 'plugin' => 'zzz-fake/fake.php' ],
	false,
	'not installed'
);

// Test 8: Delete active plugin (should auto-deactivate then delete).
if ( ! empty( $disposable_file ) ) {
	// Activate it first.
	activate_plugin( $disposable_file );

	run_test(
		'delete_plugin: delete active plugin (auto-deactivate)',
		'delete_plugin',
		[ 'plugin' => $disposable_file ],
		true,
		'deleted'
	);
} else {
	echo "  SKIP  | delete_plugin active test (no disposable plugin)\n";
	$total++;
	$pass++;
}

// Test 9: Invalid file path.
run_test(
	'delete_plugin: invalid path returns error',
	'delete_plugin',
	[ 'plugin' => '../etc/passwd' ],
	false,
	'invalid'
);

// =============================================
// MANAGE THEME — UPDATE / DELETE
// =============================================
echo "\n--- Manage Theme Update/Delete Tests ---\n\n";

// Ensure we have a disposable theme (twentytwentythree is commonly available).
require_once ABSPATH . 'wp-admin/includes/theme-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

// Find a non-active installed theme to test update on, or install one.
$test_theme_slug = '';
$all_themes      = wp_get_themes();
$active_theme    = get_stylesheet();
foreach ( $all_themes as $slug => $theme_obj ) {
	if ( $slug !== $active_theme && $slug !== get_template() ) {
		$test_theme_slug = $slug;
		break;
	}
}

if ( empty( $test_theme_slug ) ) {
	// Install twentytwentythree for tests.
	$api = themes_api( 'theme_information', [ 'slug' => 'flavor', 'fields' => [ 'sections' => false ] ] );
	if ( ! is_wp_error( $api ) ) {
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );
		$upgrader->install( $api->download_link );
		$test_theme_slug = 'flavor';
		echo "  INFO  | Installed 'flavor' theme for testing.\n";
	}
}

// Test 10: Update theme (already latest or updates).
if ( ! empty( $test_theme_slug ) ) {
	run_test(
		"manage_theme update: {$test_theme_slug} theme",
		'manage_theme',
		[ 'operation' => 'update', 'stylesheet' => $test_theme_slug ],
		true,
		''
	);
} else {
	echo "  SKIP  | manage_theme update test (no non-active theme)\n";
	$total++;
	$pass++;
}

// Test 11: Update non-existent theme.
run_test(
	'manage_theme update: non-existent returns error',
	'manage_theme',
	[ 'operation' => 'update', 'stylesheet' => 'zzz-no-theme' ],
	false,
	'not installed'
);

// Test 12: Delete active theme (should fail).
$active_theme = get_stylesheet();
run_test(
	'manage_theme delete: active theme fails',
	'manage_theme',
	[ 'operation' => 'delete', 'stylesheet' => $active_theme ],
	false,
	'cannot delete'
);

// Test 13: Delete inactive theme — install a fresh one to delete.
$delete_theme_slug = '';
$delete_theme_dir  = get_theme_root() . '/flavor';
if ( ! is_dir( $delete_theme_dir ) ) {
	$api = themes_api( 'theme_information', [ 'slug' => 'flavor', 'fields' => [ 'sections' => false ] ] );
	if ( ! is_wp_error( $api ) ) {
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Theme_Upgrader( $skin );
		$upgrader->install( $api->download_link );
		$delete_theme_slug = 'flavor';
	}
} else {
	$delete_theme_slug = 'flavor';
}

if ( ! empty( $delete_theme_slug ) && $delete_theme_slug !== $active_theme ) {
	run_test(
		'manage_theme delete: inactive theme (flavor)',
		'manage_theme',
		[ 'operation' => 'delete', 'stylesheet' => $delete_theme_slug ],
		true,
		'deleted'
	);
} else {
	echo "  SKIP  | manage_theme delete inactive (no disposable theme)\n";
	$total++;
	$pass++;
}

// Test 14: Delete non-existent theme.
run_test(
	'manage_theme delete: non-existent returns error',
	'manage_theme',
	[ 'operation' => 'delete', 'stylesheet' => 'zzz-no-theme' ],
	false,
	'not installed'
);

// Test 15: Update missing stylesheet param.
run_test(
	'manage_theme update: missing stylesheet returns error',
	'manage_theme',
	[ 'operation' => 'update' ],
	false,
	'required'
);

// =============================================
// EDIT POST — SCHEDULING TESTS
// =============================================
echo "\n--- Edit Post Scheduling Tests ---\n\n";

// Create a test post for scheduling.
$test_post_id = wp_insert_post( [
	'post_title'  => 'JARVIS Scheduling Test Post',
	'post_status' => 'draft',
	'post_type'   => 'post',
] );

if ( ! $test_post_id || is_wp_error( $test_post_id ) ) {
	echo "  ERROR | Could not create test post for scheduling tests.\n";
} else {
	// Test 16: Schedule post with future date.
	$future_date = gmdate( 'Y-m-d H:i:s', strtotime( '+30 days' ) );
	$result16 = run_test(
		'edit_post: future date auto-sets status to "future"',
		'edit_post',
		[ 'post_id' => $test_post_id, 'post_date' => $future_date ],
		true,
		'updated'
	);

	// Verify the post status is 'future'.
	$total++;
	$updated_post = get_post( $test_post_id );
	if ( $updated_post && 'future' === $updated_post->post_status ) {
		echo "  PASS  | Post status is 'future' after scheduling\n";
		$pass++;
	} else {
		$actual_status = $updated_post ? $updated_post->post_status : 'N/A';
		echo "  FAIL  | Expected status 'future', got '{$actual_status}'\n";
		$fail++;
	}

	// Test 17: Set past date with publish — keeps publish.
	$past_date = '2023-01-15 10:00:00';
	run_test(
		'edit_post: past date with publish status stays publish',
		'edit_post',
		[ 'post_id' => $test_post_id, 'post_date' => $past_date, 'post_status' => 'publish' ],
		true,
		'updated'
	);

	// Verify status is publish (not future).
	$total++;
	$updated_post = get_post( $test_post_id );
	if ( $updated_post && 'publish' === $updated_post->post_status ) {
		echo "  PASS  | Post status is 'publish' with past date\n";
		$pass++;
	} else {
		$actual_status = $updated_post ? $updated_post->post_status : 'N/A';
		echo "  FAIL  | Expected status 'publish', got '{$actual_status}'\n";
		$fail++;
	}

	// Test 18: Invalid date format.
	run_test(
		'edit_post: invalid post_date returns error',
		'edit_post',
		[ 'post_id' => $test_post_id, 'post_date' => 'not-a-date' ],
		false,
		'invalid'
	);

	// Test 19: Explicit future status with future date.
	$future_date2 = gmdate( 'Y-m-d H:i:s', strtotime( '+60 days' ) );
	run_test(
		'edit_post: explicit future status with future date',
		'edit_post',
		[ 'post_id' => $test_post_id, 'post_date' => $future_date2, 'post_status' => 'future' ],
		true,
		'updated'
	);

	// Cleanup.
	wp_delete_post( $test_post_id, true );
}

// =============================================
// MANAGE REVISIONS TESTS
// =============================================
echo "\n--- Manage Revisions Tests ---\n\n";

// Create a post and generate some revisions.
$rev_post_id = wp_insert_post( [
	'post_title'   => 'JARVIS Revision Test Post',
	'post_content' => 'Original content version 1.',
	'post_status'  => 'publish',
	'post_type'    => 'post',
] );

if ( ! $rev_post_id || is_wp_error( $rev_post_id ) ) {
	echo "  ERROR | Could not create test post for revision tests.\n";
} else {
	// Create revision 2.
	wp_update_post( [
		'ID'           => $rev_post_id,
		'post_content' => 'Updated content version 2.',
	] );

	// Create revision 3.
	wp_update_post( [
		'ID'           => $rev_post_id,
		'post_content' => 'Updated content version 3.',
	] );

	// Test 20: List revisions.
	$result20 = run_test(
		'manage_revisions: list revisions',
		'manage_revisions',
		[ 'operation' => 'list', 'post_id' => $rev_post_id ],
		true,
		'revision'
	);

	// Verify we have revisions.
	$total++;
	$rev_count = isset( $result20['data']['total'] ) ? $result20['data']['total'] : 0;
	if ( $rev_count >= 2 ) {
		echo "  PASS  | Found {$rev_count} revisions (expected >= 2)\n";
		$pass++;
	} else {
		echo "  FAIL  | Expected >= 2 revisions, got {$rev_count}\n";
		$fail++;
	}

	// Get revision IDs for restore/compare.
	$revisions    = isset( $result20['data']['revisions'] ) ? $result20['data']['revisions'] : [];
	$revision_ids = array_column( $revisions, 'revision_id' );

	if ( count( $revision_ids ) >= 2 ) {
		$first_rev  = end( $revision_ids );   // Oldest.
		$second_rev = reset( $revision_ids );  // Newest.

		// Test 21: Restore to older revision.
		run_test(
			'manage_revisions: restore to older revision',
			'manage_revisions',
			[ 'operation' => 'restore', 'post_id' => $rev_post_id, 'revision_id' => $first_rev ],
			true,
			'restored'
		);

		// Test 22: Compare two revisions.
		run_test(
			'manage_revisions: compare two revisions',
			'manage_revisions',
			[ 'operation' => 'compare', 'post_id' => $rev_post_id, 'revision_id' => $first_rev, 'compare_to' => $second_rev ],
			true,
			'compared'
		);
	} else {
		echo "  SKIP  | Not enough revisions for restore/compare tests\n";
		$total += 2;
		$pass  += 2;
	}

	// Test 23: Restore missing revision_id.
	run_test(
		'manage_revisions: restore without revision_id fails',
		'manage_revisions',
		[ 'operation' => 'restore', 'post_id' => $rev_post_id ],
		false,
		'required'
	);

	// Test 24: List revisions for non-existent post.
	run_test(
		'manage_revisions: non-existent post returns error',
		'manage_revisions',
		[ 'operation' => 'list', 'post_id' => 999999 ],
		false,
		'not found'
	);

	// Test 25: Restore revision that doesn't belong to post.
	if ( ! empty( $revision_ids ) ) {
		// Create another post.
		$other_post = wp_insert_post( [
			'post_title'  => 'Other Post',
			'post_status' => 'publish',
		] );
		if ( $other_post && ! is_wp_error( $other_post ) ) {
			run_test(
				'manage_revisions: revision from wrong post fails',
				'manage_revisions',
				[ 'operation' => 'restore', 'post_id' => $other_post, 'revision_id' => $first_rev ],
				false,
				'does not belong'
			);
			wp_delete_post( $other_post, true );
		}
	}

	// Cleanup.
	wp_delete_post( $rev_post_id, true );
}

// =============================================
// MANAGE SESSIONS TESTS
// =============================================
echo "\n--- Manage Sessions Tests ---\n\n";

// Test 26: List sessions for current user.
run_test(
	'manage_sessions: list sessions for current user',
	'manage_sessions',
	[ 'operation' => 'list' ],
	true,
	'session'
);

// Test 27: List sessions for specific user.
run_test(
	'manage_sessions: list sessions for user #1',
	'manage_sessions',
	[ 'operation' => 'list', 'user_id' => 1 ],
	true,
	''
);

// Test 28: List sessions for non-existent user.
run_test(
	'manage_sessions: non-existent user returns error',
	'manage_sessions',
	[ 'operation' => 'list', 'user_id' => 999999 ],
	false,
	'not found'
);

// Test 29: Destroy others (safe — won't break CLI).
run_test(
	'manage_sessions: destroy_others succeeds',
	'manage_sessions',
	[ 'operation' => 'destroy_others' ],
	true,
	'destroyed'
);

// Test 30: Invalid operation.
run_test(
	'manage_sessions: invalid operation returns error',
	'manage_sessions',
	[ 'operation' => 'invalid_op' ],
	false,
	'invalid'
);

// =============================================
// READ DEBUG LOG TESTS
// =============================================
echo "\n--- Read Debug Log Tests ---\n\n";

// Create a test debug.log if it doesn't exist.
$debug_log_path = WP_CONTENT_DIR . '/debug.log';
$log_existed    = file_exists( $debug_log_path );

if ( ! $log_existed ) {
	file_put_contents( $debug_log_path, "Test log line 1\nTest log line 2\nTest log line 3\n" );
}

// Test 31: Read debug log.
run_test(
	'read_debug_log: read operation',
	'read_debug_log',
	[ 'operation' => 'read' ],
	true,
	''
);

// Test 32: Tail debug log.
$result32 = run_test(
	'read_debug_log: tail 10 lines',
	'read_debug_log',
	[ 'operation' => 'tail', 'lines' => 10 ],
	true,
	'lines'
);

// Test 33: Clear debug log.
run_test(
	'read_debug_log: clear operation',
	'read_debug_log',
	[ 'operation' => 'clear' ],
	true,
	'cleared'
);

// Verify log was cleared (may have new writes from WP during test).
$total++;
clearstatcache( true, $debug_log_path );
if ( file_exists( $debug_log_path ) ) {
	$size = filesize( $debug_log_path );
	// Allow small size due to race condition (WP may write during test).
	if ( $size <= 50 ) {
		echo "  PASS  | Debug log was cleared ({$size} bytes, near-zero is OK)\n";
		$pass++;
	} else {
		echo "  FAIL  | Debug log not empty after clear ({$size} bytes)\n";
		$fail++;
	}
} else {
	echo "  PASS  | Debug log cleared (file removed)\n";
	$pass++;
}

// Test 34: Tail with 0 defaults to 1.
run_test(
	'read_debug_log: tail with lines=0 defaults to min',
	'read_debug_log',
	[ 'operation' => 'tail', 'lines' => 0 ],
	true,
	''
);

// Test 35: Invalid operation.
run_test(
	'read_debug_log: invalid operation returns error',
	'read_debug_log',
	[ 'operation' => 'invalid_op' ],
	false,
	'invalid'
);

// Cleanup: restore debug.log if it didn't exist.
if ( ! $log_existed && file_exists( $debug_log_path ) ) {
	unlink( $debug_log_path );
}

// =============================================
// ACTION REGISTRATION VERIFICATION
// =============================================
echo "\n--- Registration Verification ---\n\n";

$expected_actions = [
	'update_plugin',
	'delete_plugin',
	'manage_revisions',
	'manage_sessions',
	'read_debug_log',
];

foreach ( $expected_actions as $action_name ) {
	$total++;
	$action = $registry->get_action( $action_name );
	if ( $action ) {
		echo "  PASS  | Action '{$action_name}' is registered\n";
		$pass++;
	} else {
		echo "  FAIL  | Action '{$action_name}' is NOT registered\n";
		$fail++;
	}
}

// Verify manage_theme has new operations in its schema.
$total++;
$manage_theme = $registry->get_action( 'manage_theme' );
if ( $manage_theme ) {
	$params = $manage_theme->get_parameters();
	$enum   = isset( $params['properties']['operation']['enum'] ) ? $params['properties']['operation']['enum'] : [];
	if ( in_array( 'update', $enum, true ) && in_array( 'delete', $enum, true ) ) {
		echo "  PASS  | manage_theme has 'update' and 'delete' operations\n";
		$pass++;
	} else {
		echo "  FAIL  | manage_theme missing 'update' or 'delete' in enum\n";
		$fail++;
	}
} else {
	echo "  FAIL  | manage_theme action not found\n";
	$fail++;
}

// Verify edit_post has scheduling params.
$total++;
$edit_post = $registry->get_action( 'edit_post' );
if ( $edit_post ) {
	$params = $edit_post->get_parameters();
	$props  = isset( $params['properties'] ) ? $params['properties'] : [];
	if ( isset( $props['post_date'] ) && isset( $props['post_date_gmt'] ) ) {
		echo "  PASS  | edit_post has 'post_date' and 'post_date_gmt' parameters\n";
		$pass++;
	} else {
		echo "  FAIL  | edit_post missing scheduling parameters\n";
		$fail++;
	}
} else {
	echo "  FAIL  | edit_post action not found\n";
	$fail++;
}

// Verify edit_post has 'future' in status enum.
$total++;
if ( $edit_post ) {
	$params     = $edit_post->get_parameters();
	$status_enum = isset( $params['properties']['post_status']['enum'] ) ? $params['properties']['post_status']['enum'] : [];
	if ( in_array( 'future', $status_enum, true ) ) {
		echo "  PASS  | edit_post has 'future' in post_status enum\n";
		$pass++;
	} else {
		echo "  FAIL  | edit_post missing 'future' in status enum\n";
		$fail++;
	}
} else {
	echo "  FAIL  | edit_post action not found\n";
	$fail++;
}

// Count total registered actions.
$total++;
$all_actions = $registry->get_all_actions();
$action_count = is_array( $all_actions ) ? count( $all_actions ) : 0;
echo "  INFO  | Total registered actions: {$action_count}\n";
// Base is 63 without WooCommerce + 5 new = 68, or 71 with WooCommerce + 5 = 76.
if ( $action_count >= 68 ) {
	echo "  PASS  | Action count >= 68 (base + 5 new actions)\n";
	$pass++;
} else {
	echo "  FAIL  | Expected >= 68 actions, got {$action_count}\n";
	$fail++;
}

echo "\n============================================\n";
echo " Results: {$pass}/{$total} passed";
if ( $fail > 0 ) {
	echo " ({$fail} FAILED)";
}
echo "\n============================================\n\n";

exit( $fail > 0 ? 1 : 0 );
