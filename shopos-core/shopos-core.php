<?php
/**
 * Plugin Name:       ShopOS Core
 * Plugin URI:        https://shoposdigital.com/shopos-core
 * Description:       Unified WooCommerce functionality for the ShopOS Theme. Hosts eight independently togglable modules: Variation Swatches, Restock Notify, Variable Stock Fix, Product Feed, Infinite Scroll, Cheapest Default Variation, Category Slider, Product Slider. Owns all data and business logic so features survive a theme switch.
 * Version:           1.34.0
 * Author:            ShopOS Digital
 * Author URI:        https://shoposdigital.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       shopos-core
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   9.5
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

define( 'SHOPOS_CORE_VERSION',  '1.34.0' );
define( 'SHOPOS_CORE_FILE',     __FILE__ );
define( 'SHOPOS_CORE_PATH',     plugin_dir_path( __FILE__ ) );
define( 'SHOPOS_CORE_URL',      plugin_dir_url( __FILE__ ) );
define( 'SHOPOS_CORE_BASENAME', plugin_basename( __FILE__ ) );
define( 'SHOPOS_CORE_ASSETS',   SHOPOS_CORE_URL . 'assets' );

/**
 * PSR-4 autoloader (dependency-free, no Composer required at runtime).
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'ShopOS\\Core\\';
		$base   = SHOPOS_CORE_PATH . 'src/';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$path     = $base . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

// Declare HPOS + Cart/Checkout Blocks compatibility — must be before
// WooCommerce inspects compatible plugins.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SHOPOS_CORE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', SHOPOS_CORE_FILE, true );
		}
	}
);

// Activation / deactivation hooks delegate to the plugin.
register_activation_hook( __FILE__, array( '\ShopOS\Core\Core\Plugin', 'on_activate' ) );
register_deactivation_hook( __FILE__, array( '\ShopOS\Core\Core\Plugin', 'on_deactivate' ) );

// Boot on plugins_loaded so WooCommerce is ready.
add_action(
	'plugins_loaded',
	static function () {
		\ShopOS\Core\Core\Plugin::instance()->boot();
	},
	5
);
