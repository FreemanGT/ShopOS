<?php
/**
 * Legacy helper-function shims.
 *
 * The bundled Restock Notify classes reference the global functions
 * `shopos_restock_option_defaults()` and `shopos_restock_get_option()`. Those functions used to live
 * in the plugin's root file; we re-declare them here (idempotently) so the
 * bundled classes keep working under the module.
 *
 * @package ShopOSCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'shopos_restock_option_defaults' ) ) {
	/**
	 * Default option values for the Restock Notify module.
	 *
	 * @return array
	 */
	function shopos_restock_option_defaults() {
		return \ShopOS\Core\Modules\RestockNotify\Module::defaults();
	}
}

if ( ! function_exists( 'shopos_restock_get_option' ) ) {
	/**
	 * Get a module option with guaranteed fallback.
	 *
	 * @param string      $key              Option key (without the `shopos_restock_` prefix).
	 * @param string|null $override_default Optional explicit default.
	 * @return mixed
	 */
	function shopos_restock_get_option( $key, $override_default = null ) {
		$defaults = shopos_restock_option_defaults();
		$default  = null === $override_default ? ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' ) : $override_default;
		$value    = get_option( 'shopos_restock_' . $key, null );

		if ( null === $value || false === $value ) {
			if ( 'from_name' === $key && empty( $default ) ) {
				$default = get_bloginfo( 'name' );
			}
			if ( 'from_email' === $key && empty( $default ) ) {
				$default = get_option( 'admin_email' );
			}
			update_option( 'shopos_restock_' . $key, $default );
			return $default;
		}

		return $value;
	}
}
