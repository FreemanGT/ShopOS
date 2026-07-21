<?php
/**
 * Uninstall handler for ShopOS Core.
 *
 * Invoked by WP when the user chooses "Delete" on the plugin. We give every
 * module a chance to clean up its own options and tables.
 *
 * @package ShopOSCore
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/shopos-core.php';

$plugin = \ShopOS\Core\Core\Plugin::instance();
$plugin->boot_for_uninstall();

foreach ( $plugin->registry()->all() as $module ) {
	try {
		$module->on_uninstall();
	} catch ( \Throwable $e ) {
		// Hard Rule #8 sanctioned exception: uninstall runs in a minimal bootstrap
		// where Logger's hook/DB surface may already be torn down, so error_log is
		// the only dependable sink for a module cleanup failure here.
		error_log( '[ShopOSCore][uninstall] ' . $e->getMessage() );
	}
}

// Wipe the registry + global options last, so modules can reference them above.
delete_option( 'shopos_core_modules' );
delete_option( 'shopos_core_db_version' );
delete_option( 'shopos_core_legacy_imported' );
delete_option( 'shopos_core_onboarded' );

// Core-owned options written by Core classes (not modules), so no per-module
// id-prefix sweep reaches them: the logger buffer, the settings-backup store,
// and the ProductFeed last-generated stamp (whose `productfeed` key does not
// match the module's `product_feed_%` sweep prefix — a historical mismatch).
delete_option( 'shopos_core_log' );
delete_option( 'shopos_core_settings_backups' );
delete_option( 'shopos_core_productfeed_last_generated' );

// The Design panel writes several `shopos_core_design_*` options (user config);
// clear the whole family in one pass.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like( 'shopos_core_design_' ) . '%'
	)
);
