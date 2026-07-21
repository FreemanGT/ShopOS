<?php
/**
 * Side Cart module.
 *
 * A slide-out cart drawer that opens on add-to-cart (and when a cart link is
 * clicked), showing line items with a live subtotal, a free-shipping progress
 * meter, a coupon field, remove-with-undo, cross-sell recommendations and a
 * quick checkout button.
 *
 * Thin over WooCommerce: the drawer body is rendered from `WC()->cart`; every
 * mutation (coupon apply/remove, item remove/restore) delegates to the WC cart
 * API and re-renders the body. Recommendations are the product's own
 * cross-sells. The drawer body is also registered as a WC cart fragment so
 * stores already refreshing fragments keep it live.
 *
 * Default OFF on existing stores — a newly-added module is absent from
 * `shopos_core_modules` (the PageTransitions / Bundle Deals pattern); the
 * module-enable toggle is the single kill-switch (module-off boots nothing:
 * no markup, no assets, no public endpoint).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\SideCart;

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
		return 'side_cart';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Side Cart', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Slide-out cart drawer with a live subtotal, free-shipping meter, coupon field, remove-with-undo, recommendations and a quick checkout button.', 'shopos-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' => true );
	}

	/**
	 * Settings schema: two behaviour toggles followed by one text field per
	 * storefront string (blank falls back to the English default).
	 *
	 * @return array
	 */
	public function settings_schema() {
		$toggles = array(
			'free_shipping_meter' => array(
				'label'       => __( 'Free-shipping progress meter', 'shopos-core' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'Show a progress bar toward the free-shipping threshold. Hidden automatically when no free-shipping method has a minimum amount.', 'shopos-core' ),
			),
			'show_recommendations' => array(
				'label'       => __( 'Recommended products', 'shopos-core' ),
				'type'        => 'checkbox',
				'default'     => 'yes',
				'description' => __( 'Show the cart products’ cross-sells in the drawer.', 'shopos-core' ),
			),
		);

		return array_merge(
			$toggles,
			$this->label_fields(
				Labels::defaults(),
				__( 'Side-cart wording — leave a field blank to use its English default.', 'shopos-core' )
			)
		);
	}

	/**
	 * Boot — storefront + AJAX surfaces. Always active when the module is
	 * enabled (the module-enable toggle is the kill-switch).
	 */
	public function boot() {
		( new Frontend( $this ) )->register();
		( new Ajax( $this ) )->register();
	}

	/* -----------------------------------------------------------------
	 * Settings readers
	 * ----------------------------------------------------------------- */

	/**
	 * Read a checkbox setting as a bool. Settings_Hub persists checkboxes as
	 * 1/0 but the schema default is 'yes'/'no', so coerce both shapes (the
	 * 1.21.9 Search-toggle fix).
	 *
	 * @param string $key     Setting key.
	 * @param bool   $default Fallback when unset.
	 * @return bool
	 */
	public function bool_option( $key, $default = true ) {
		$raw = $this->get_option( $key, $default ? 'yes' : 'no' );
		return filter_var( $raw, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * @return bool
	 */
	public function meter_enabled() {
		return $this->bool_option( 'free_shipping_meter', true );
	}

	/**
	 * @return bool
	 */
	public function recommendations_enabled() {
		return $this->bool_option( 'show_recommendations', true );
	}

	/* -----------------------------------------------------------------
	 * Cart data (integration — live-QA)
	 * ----------------------------------------------------------------- */

	/**
	 * The free-shipping threshold to measure the meter against: the smallest
	 * `min_amount` across enabled free-shipping methods that actually require a
	 * minimum. 0 = no threshold (meter hidden). Overridable so a store can pin
	 * a fixed number without depending on zone introspection.
	 *
	 * @return float
	 */
	public function free_shipping_min() {
		$min = 0.0;

		if ( class_exists( '\\WC_Shipping_Zones' ) ) {
			$zones   = \WC_Shipping_Zones::get_zones();
			$zones[] = array( 'shipping_methods' => \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods() );

			foreach ( $zones as $zone ) {
				$methods = isset( $zone['shipping_methods'] ) ? $zone['shipping_methods'] : array();
				foreach ( $methods as $method ) {
					if ( ! isset( $method->id ) || 'free_shipping' !== $method->id ) {
						continue;
					}
					if ( isset( $method->enabled ) && 'yes' !== $method->enabled ) {
						continue;
					}
					$requires = isset( $method->requires ) ? $method->requires : '';
					if ( ! in_array( $requires, array( 'min_amount', 'either', 'both' ), true ) ) {
						continue;
					}
					$amount = isset( $method->min_amount ) ? (float) $method->min_amount : 0.0;
					if ( $amount > 0.0 && ( 0.0 === $min || $amount < $min ) ) {
						$min = $amount;
					}
				}
			}
		}

		/**
		 * Filter the free-shipping threshold the meter measures against.
		 *
		 * @since 1.55.0
		 * @param float $min Threshold in the shop currency (0 = no meter).
		 */
		return (float) apply_filters( 'shopos_core/side_cart/free_ship_min', $min );
	}

	/**
	 * Cross-sell product ids for the current cart, capped. Overridable so a
	 * recommendation engine can supply its own list.
	 *
	 * @return int[]
	 */
	public function recommended_ids() {
		$ids = array();
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$ids = array_map( 'intval', (array) WC()->cart->get_cross_sells() );
		}
		$ids = array_values( array_unique( array_filter( $ids ) ) );

		/**
		 * Filter the side-cart recommendation product ids.
		 *
		 * @since 1.55.0
		 * @param int[] $ids Product ids (default: the cart's cross-sells).
		 */
		$ids = (array) apply_filters( 'shopos_core/side_cart/recommendations', $ids );

		return array_slice( array_values( array_unique( array_map( 'intval', $ids ) ) ), 0, 4 );
	}

	/**
	 * Render the drawer body from the current cart. This is the payload both
	 * the AJAX endpoint and the WC cart fragment return. Integration — live-QA.
	 *
	 * @return string
	 */
	public function render_body() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}

		$cart      = WC()->cart;
		$meter     = $this->meter_enabled()
			? Meter::compute( (float) $cart->get_displayed_subtotal(), $this->free_shipping_min() )
			: array( 'active' => false );
		$recommend = $this->recommendations_enabled() ? $this->recommended_ids() : array();

		ob_start();
		$this->load_template(
			'body.php',
			array(
				'module'    => $this,
				'cart'      => $cart,
				'meter'     => $meter,
				'recommend' => $recommend,
			)
		);
		return (string) ob_get_clean();
	}
}
