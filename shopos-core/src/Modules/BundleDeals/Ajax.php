<?php
/**
 * Bundle Deals — the "add bundle to cart" endpoint.
 *
 * Backs the frequently-bought-together card's Add button: the JS posts the set
 * of product ids the shopper left checked, and this handler adds each to the
 * cart (one each). Public (logged-in + logged-out), guarded by a nonce and a
 * per-IP rate limit — the QuickView Ajax contract (admin-AJAX, no REST).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

use ShopOS\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Add-bundle AJAX handler.
 */
final class Ajax {

	const ACTION = 'shopos_core_bundle_deals_add';
	const NONCE  = 'shopos_core_bundle_deals_add';

	/**
	 * Register the public action.
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_add' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle_add' ) );
	}

	/**
	 * Validate + add the checked set members to the cart.
	 */
	public function handle_add() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );

		if ( ! Security::rate_limit( 'bundle_deals_add', 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'shopos-core' ) ), 429 );
		}

		$ids = $this->requested_ids();
		if ( empty( $ids ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Nothing to add.', 'shopos-core' ) ), 400 );
		}

		$added = 0;
		foreach ( $ids as $id ) {
			if ( WC()->cart->add_to_cart( $id, 1 ) ) {
				++$added;
			}
		}

		if ( $added < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Could not add the bundle.', 'shopos-core' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'added'      => $added,
				'cart_count' => WC()->cart->get_cart_contents_count(),
			)
		);
	}

	/**
	 * The requested, sanitised, published-product id list. Pure given WP stubs.
	 *
	 * @return int[]
	 */
	public function requested_ids() {
		$raw = isset( $_REQUEST['products'] ) ? wp_unslash( $_REQUEST['products'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$ids = array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) );

		return array_values(
			array_filter(
				$ids,
				static function ( $id ) {
					return 'publish' === get_post_status( $id );
				}
			)
		);
	}
}
