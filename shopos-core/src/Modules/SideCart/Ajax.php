<?php
/**
 * Side Cart public AJAX endpoint.
 *
 * One action `shopos_core_side_cart` (admin-AJAX, no REST — §6.3) with an `op`
 * selector: refresh, apply_coupon, remove_coupon, remove_item, restore_item.
 * Every op mutates `WC()->cart` (where applicable) and returns the freshly
 * rendered drawer body + the cart count, so the JS never has to reason about
 * cart state itself.
 *
 * Public (logged-in + logged-out), guarded by a nonce + a per-IP rate limit.
 * Only wired when the module is enabled (Module::boot()), so a disabled module
 * exposes no public surface. The cart mutations are exercised by live QA; the
 * op-whitelist and registration are unit-tested.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\SideCart;

use ShopOS\Core\Core\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Public AJAX handler.
 */
final class Ajax {

	const ACTION = 'shopos_core_side_cart';
	const NONCE  = 'shopos_core_side_cart';

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
	 * Register the public action (logged-in + logged-out).
	 */
	public function register() {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Normalise the requested op to a supported verb, or '' if unknown. Pure —
	 * unit-tested.
	 *
	 * @param mixed $raw Requested op.
	 * @return string
	 */
	public function sanitize_op( $raw ) {
		$op = is_string( $raw ) ? strtolower( trim( $raw ) ) : '';
		$allowed = array( 'refresh', 'apply_coupon', 'remove_coupon', 'remove_item', 'restore_item' );
		return in_array( $op, $allowed, true ) ? $op : '';
	}

	/**
	 * Handle a side-cart request: validate, run the op against the cart, return
	 * the rendered body + cart count as JSON.
	 */
	public function handle() {
		Security::verify_ajax_nonce( self::NONCE, '_ajax_nonce' );

		if ( ! Security::rate_limit( 'side_cart', 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please slow down.', 'shopos-core' ) ), 429 );
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Cart unavailable.', 'shopos-core' ) ), 400 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- nonce verified above.
		$op     = $this->sanitize_op( isset( $_REQUEST['op'] ) ? wp_unslash( $_REQUEST['op'] ) : '' );
		$coupon = isset( $_REQUEST['coupon'] ) ? wc_format_coupon_code( wp_unslash( $_REQUEST['coupon'] ) ) : '';
		$key    = isset( $_REQUEST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cart_item_key'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$cart = WC()->cart;

		switch ( $op ) {
			case 'apply_coupon':
				if ( '' !== $coupon && ! $cart->has_discount( $coupon ) ) {
					$cart->apply_coupon( $coupon );
				}
				break;

			case 'remove_coupon':
				if ( '' !== $coupon ) {
					$cart->remove_coupon( $coupon );
				}
				break;

			case 'remove_item':
				if ( '' !== $key ) {
					$cart->remove_cart_item( $key );
				}
				break;

			case 'restore_item':
				if ( '' !== $key ) {
					$cart->restore_cart_item( $key );
				}
				break;

			case 'refresh':
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown request.', 'shopos-core' ) ), 400 );
		}

		$cart->calculate_totals();

		wp_send_json_success(
			array(
				'html'  => $this->module->render_body(),
				'count' => $cart->get_cart_contents_count(),
			)
		);
	}
}
