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
 * the Elementor Pro archive grid and the Freeman ProductSlider (slider +
 * grid modes) render through `content-product.php` — one injection point
 * covers every product card site-wide (decisions §6.2). The drawer content
 * renders the standard single-product summary, so VariationSwatches' PDP
 * buy-box hooks light up unaided (§6.7). Transport is admin-AJAX (§6.3).
 *
 * Everything is gated by the `freeman_core_quick_view_frontend_enabled`
 * feature flag (default off, §6.5): flag-off boots nothing — no markup, no
 * assets, no public endpoint.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\QuickView;

use Freeman\Core\Core\Feature_Flags;
use Freeman\Core\Core\Module_Base;

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
		return __( 'Quick View', 'freeman-core' );
	}

	/**
	 * @return string
	 */
	public function description() {
		return __( 'Quick-view icon on product cards opening a slide-in drawer with image, price, short description, add-to-cart and a product-page link.', 'freeman-core' );
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
		$schema = array();

		$first = true;
		foreach ( Labels::defaults() as $key => $def ) {
			/* translators: %s: the English default wording for this field. */
			$desc = sprintf( __( 'Default: %s', 'freeman-core' ), $def['default'] );
			if ( $first ) {
				$desc = __( 'Quick-view wording — leave a field blank to use its English default.', 'freeman-core' ) . ' ' . $desc;
			}
			$schema[ 'label_' . $key ] = array(
				'label'       => $def['label'],
				'type'        => 'text',
				'default'     => '',
				'description' => $desc,
			);
			$first = false;
		}

		return $schema;
	}

	/**
	 * Boot — everything sits behind the frontend feature flag so flag-off
	 * leaves no storefront markup, no assets, and no public AJAX surface.
	 */
	public function boot() {
		if ( ! Feature_Flags::is_enabled( 'quick_view', 'frontend' ) ) {
			return;
		}
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
	 * Template is theme-overridable at `freeman/quick_view/drawer-content.php`
	 * via Module_Base::load_template().
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function render_drawer_content( $product ) {
		global $post;
		$original_post    = $post;
		$original_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null;

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
