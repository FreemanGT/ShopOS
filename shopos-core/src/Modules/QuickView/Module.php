<?php
/**
 * Quick View module.
 *
 * A small icon on every product-loop card opens a slide-in drawer (anchored
 * to the inline-end edge — the left edge on this RTL store) with the
 * product's image, title, price, short description, meta, the standard
 * add-to-cart surface and a link to the full product page.
 *
 * The trigger is injected via the standard WC loop hook stack, which both
 * the Elementor Pro archive grid and the ShopOS ProductSlider (slider +
 * grid modes) render through `content-product.php` — one injection point
 * covers every product card site-wide (decisions §6.2). The drawer content
 * renders the standard single-product summary, so VariationSwatches' PDP
 * buy-box hooks light up unaided (§6.7). Transport is admin-AJAX (§6.3).
 *
 * Always-on since 1.23.0 (the frontend feature flag graduated): the
 * module-enable toggle is the kill-switch — module-off boots nothing, no
 * markup, no assets, no public endpoint.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\QuickView;

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
		return 'quick_view';
	}

	/**
	 * @return string
	 */
	public function label() {
		return __( 'Quick View', 'shopos-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Quick-view icon on product cards opening a slide-in drawer with image, price, short description, add-to-cart and a product-page link.', 'shopos-core' );
	}

	/**
	 * @return array
	 */
	public function dependencies() {
		return array( 'woocommerce' => true );
	}

	/**
	 * Settings schema — one text field per storefront string, blank falls
	 * back to the English default (Labels precedent from ShopFilters 6.3c).
	 *
	 * @return array
	 */
	public function settings_schema() {
		// One text field per storefront string, built by the shared helper
		// (byte-identical to the loop this replaced).
		return $this->label_fields(
			Labels::defaults(),
			__( 'Quick-view wording — leave a field blank to use its English default.', 'shopos-core' )
		);
	}

	/**
	 * Boot — storefront + AJAX surfaces; always-on since 1.23.0 (the
	 * frontend flag graduated; the module-enable toggle is the kill-switch).
	 */
	public function boot() {
		( new Frontend( $this ) )->register();
		( new Ajax( $this ) )->register();
	}

	/**
	 * Render the drawer content for a product — the payload the AJAX
	 * endpoint returns. Sets up the product globals so the standard
	 * single-product summary hook stack (title / price / excerpt /
	 * add-to-cart / meta — and VariationSwatches' buy-box swap) renders
	 * exactly as it would inside any quick-view modal.
	 *
	 * Template is theme-overridable at `shopos/quick_view/drawer-content.php`
	 * via Module_Base::load_template().
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function render_drawer_content( $product ) {
		global $post;
		$original_post    = $post;
		$original_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;

		// admin-ajax runs in an admin context ( is_admin() === true ), so WP and
		// plugins resolve the admin/user locale (often English) instead of the
		// site front-end locale. That makes the WooCommerce summary plus the
		// VariationSwatches buy box — which keys its He/En labels off
		// determine_locale() — render in English inside the drawer while the rest
		// of the storefront is in the site language (Add to cart / Buy now /
		// Categories / SKU / In stock). Render in the site locale so the drawer
		// matches the front end. switch_to_locale() pushes onto a stack; only
		// restore when it actually switched, to keep the stack balanced.
		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( get_locale() );

		$post = get_post( $product->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		if ( $post ) {
			setup_postdata( $post );
		}
		$GLOBALS['product'] = $product;

		ob_start();
		$this->load_template(
			'drawer-content.php',
			array(
				'product'     => $product,
				'gallery_ids' => $this->gallery_image_ids( $product ),
			)
		);
		$html = (string) ob_get_clean();

		$post               = $original_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		$GLOBALS['product'] = $original_product;
		wp_reset_postdata();

		if ( $switched_locale && function_exists( 'restore_previous_locale' ) ) {
			restore_previous_locale();
		}

		return $html;
	}

	/**
	 * Ordered, de-duplicated gallery image ids for the drawer slider:
	 * the featured image first, then the product gallery, zeros dropped.
	 * Pure — unit-tested. Fewer than two ids → the template renders the
	 * single featured image with no arrows.
	 *
	 * @param \WC_Product $product Product.
	 * @return int[]
	 */
	public function gallery_image_ids( $product ) {
		$ids      = array();
		$featured = (int) $product->get_image_id();
		if ( $featured > 0 ) {
			$ids[] = $featured;
		}
		foreach ( (array) $product->get_gallery_image_ids() as $gid ) {
			$gid = (int) $gid;
			if ( $gid > 0 ) {
				$ids[] = $gid;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
