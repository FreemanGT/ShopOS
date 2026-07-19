<?php
/**
 * Bundle Deals module.
 *
 * Four discount types — volume/tiered, BOGO, curated (frequently-bought-
 * together) and mix-&-match — configured in a visual builder on the ShopOS →
 * Bundle Deals page, targeted by product / category / tag, and applied as
 * per-line price adjustments in the cart. It is the first module in the suite
 * to price the cart; the money math lives in the pure {@see Pricing} engine and
 * the WooCommerce integration in {@see Cart_Pricing}.
 *
 * Default OFF (absent from the seeded modules map); the module-enable toggle is
 * the sole kill switch — disabled ⇒ boot() never runs ⇒ no cart hook, no
 * markup, no assets, no public endpoint.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

use ShopOS\Core\Core\Module_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Module.
 */
final class Module extends Module_Base {

	/**
	 * @return string
	 */
	public function id() {
		return 'bundle_deals';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Bundle Deals', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Volume/tiered, BOGO, frequently-bought-together and mix-&-match bundles with automatic per-line cart pricing and savings tags.', 'shopos-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' => true );
	}

	/**
	 * Settings schema — the owner-editable storefront wording. The bundles
	 * themselves are managed by the {@see Admin_Builder} repeater injected on
	 * this page via `shopos_core/module_page/bundle_deals`.
	 *
	 * @return array
	 */
	public function settings_schema() {
		return $this->label_fields(
			Labels::defaults(),
			__( 'Bundle wording — leave a field blank to use its default.', 'shopos-core' )
		);
	}

	/**
	 * Boot — cart pricing, storefront block, add-bundle endpoint, admin builder
	 * and (when Elementor is active) the widget. Only runs when the module is
	 * enabled.
	 */
	public function boot() {
		( new Cart_Pricing() )->register();
		( new Frontend( $this ) )->register();
		( new Ajax() )->register();
		( new Admin_Builder() )->register();

		add_action(
			'elementor/widgets/register',
			function ( $widgets_manager ) {
				$widgets_manager->register( new Bundle_Deals_Widget() );
			}
		);
	}
}
