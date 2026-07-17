<?php
/**
 * Plugin Name: ShopOS Digital
 * Plugin URI: https://shopos.digital
 * Description: All-in-one WordPress & WooCommerce optimization — database indexes, query tuning, autoload optimizer, security hardening, speed tuning, transient management, and bloat removal. Built for Elementor + WooCommerce stores.
 * Version: 1.7.8
 * Author: ShopOS Digital
 * Author URI: https://shopos.digital
 * License: GPL v3
 * Text Domain: shopos-digital
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) exit;

define('SHOPOS_DIGITAL_VERSION', '1.7.8');
define('SHOPOS_DIGITAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SHOPOS_DIGITAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SHOPOS_DIGITAL_PLUGIN_FILE', __FILE__);
define('SHOPOS_DIGITAL_OPT', 'shopos_digital_settings');

// Load core classes
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-core.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-admin.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-indexes.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-query-optimizer.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-woocommerce.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-security.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-speed.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-database.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-bloat.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-autoload.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-frontend.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-profiler.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-admin-cache.php';
require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-activity-log.php';

if (defined('WP_CLI') && WP_CLI) {
    require_once SHOPOS_DIGITAL_PLUGIN_DIR . 'includes/class-shopos-digital-cli.php';
}

// Load translations from the bundled /languages directory (falls back to WP language packs).
add_action('init', function () {
    load_plugin_textdomain('shopos-digital', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Boot
add_action('plugins_loaded', function () {
    ShopOS_Digital_Core::get_instance();
}, 5);

// Activation
register_activation_hook(__FILE__, function () {
    if (!get_option(SHOPOS_DIGITAL_OPT)) {
        update_option(SHOPOS_DIGITAL_OPT, ShopOS_Digital_Core::get_defaults());
    }
    if (!wp_next_scheduled('shopos_digital_daily_maintenance')) {
        // Schedule for 3 AM in the site's configured timezone, always in the future.
        // strtotime('03:00') returns today 3am in server local time, which may be in the past
        // (WP-Cron fires immediately) AND wrong timezone. Use wp_timezone() + "tomorrow".
        try {
            $tz = wp_timezone();
            $next = new DateTime('tomorrow 03:00', $tz);
            $timestamp = $next->getTimestamp();
        } catch (Exception $e) {
            // Fallback: 24 hours from now
            $timestamp = time() + DAY_IN_SECONDS;
        }
        wp_schedule_event($timestamp, 'daily', 'shopos_digital_daily_maintenance');
    }
    // Create profiler table
    ShopOS_Digital_Profiler::maybe_create_table();
});

// Deactivation
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('shopos_digital_daily_maintenance');
});
