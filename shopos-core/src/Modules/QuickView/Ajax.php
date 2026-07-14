<?php
/**
 * Quick View public AJAX endpoint.
 *
 * Action `shopos_core_quick_view_product` (admin-AJAX per decision §6.3 — no
 * REST). Public: registered for both logged-in and logged-out visitors,
 * guarded by a nonce + a per-IP rate limit. Only wired when the frontend
 * feature flag is on (Module::boot()), so flag-off leaves no public surface.
 *
 * The handler echoes JSON, so it is exercised by live QA; the registration
 * wiring and the validation seams are unit-tested.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\QuickView;

use ShopOS\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Public AJAX handler.
 */
final class Ajax {

	const ACTION = 'shopos_core_quick_view_product';
	const NONCE  = 'shopos_core_quick_view_product';

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Register the public product action (logged-in + logged-out).
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_product' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle_product' ) );
	}

	/**
	 * Handle a quick-view request: validate, render the drawer content,
	 * send JSON.
	 */
	public function handle_product() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );

		if ( ! Security::rate_limit( 'quick_view_product', 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'shopos-core' ) ), 429 );
		}

		$product_id = isset( $_REQUEST['product_id'] ) ? absint( wp_unslash( $_REQUEST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$product    = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $this->is_viewable( $product, $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Product not found.', 'shopos-core' ) ), 404 );
		}

		$html = $this->module->render_drawer_content( $product );

		/**
		 * Filter the rendered quick-view drawer markup before it is returned.
		 *
		 * @since 1.13.0
		 *
		 * @param string $html       Drawer content HTML.
		 * @param int    $product_id Product id.
		 */
		$html = (string) apply_filters( 'shopos_core/quick_view/drawer_html', $html, $product_id );

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Whether a product may be served to the public endpoint: a real,
	 * published, catalog-visible product. Pure given WP stubs — unit-tested.
	 *
	 * @param mixed $product    wc_get_product() result.
	 * @param int   $product_id Requested id.
	 * @return bool
	 */
	public function is_viewable( $product, $product_id ) {
		if ( ! $product instanceof \WC_Product ) {
			return false;
		}
		if ( 'publish' !== get_post_status( (int) $product_id ) ) {
			return false;
		}
		return (bool) $product->is_visible();
	}
}
