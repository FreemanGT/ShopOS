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
