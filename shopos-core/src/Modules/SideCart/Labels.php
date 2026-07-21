<?php
/**
 * Side Cart — storefront label resolver.
 *
 * Every user-facing string in the drawer is overridable from the
 * ShopOS → Side Cart settings page (§4.2 — English defaults, locale-specific
 * opt-in; the QuickView / ShopFilters / Search Labels precedent). Each label is
 * stored under `shopos_core_side_cart_label_<key>`; an unset / blank option
 * falls back to the English default here.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\SideCart;

use ShopOS\Core\Core\Labels_Base;

defined( 'ABSPATH' ) || exit;

/**
 * Label resolver. Resolution (option override → English default) lives in
 * Labels_Base::get(); this class only owns the prefix + canonical map.
 */
final class Labels extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_side_cart_label_';

	/**
	 * Canonical label map: short key => [ admin field label, storefront default ].
	 *
	 * @return array<string,array{label:string,default:string}>
	 */
	public static function defaults() {
		return array(
			'heading'          => array(
				'label'   => __( 'Drawer heading', 'shopos-core' ),
				'default' => __( 'Your cart', 'shopos-core' ),
			),
			'close'            => array(
				'label'   => __( 'Close button label', 'shopos-core' ),
				'default' => __( 'Close', 'shopos-core' ),
			),
			'empty'            => array(
				'label'   => __( 'Empty-cart message', 'shopos-core' ),
				'default' => __( 'Your cart is empty.', 'shopos-core' ),
			),
			'remove'           => array(
				'label'   => __( 'Remove-item label', 'shopos-core' ),
				'default' => __( 'Remove', 'shopos-core' ),
			),
			'removed'          => array(
				'label'   => __( 'Removed-item notice', 'shopos-core' ),
				'default' => __( 'Item removed.', 'shopos-core' ),
			),
			'undo'             => array(
				'label'   => __( 'Undo link', 'shopos-core' ),
				'default' => __( 'Undo', 'shopos-core' ),
			),
			'coupon_placeholder' => array(
				'label'   => __( 'Coupon field placeholder', 'shopos-core' ),
				'default' => __( 'Coupon code', 'shopos-core' ),
			),
			'apply'            => array(
				'label'   => __( 'Apply-coupon button', 'shopos-core' ),
				'default' => __( 'Apply', 'shopos-core' ),
			),
			'subtotal'         => array(
				'label'   => __( 'Subtotal label', 'shopos-core' ),
				'default' => __( 'Subtotal', 'shopos-core' ),
			),
			'checkout'         => array(
				'label'   => __( 'Checkout button', 'shopos-core' ),
				'default' => __( 'Checkout', 'shopos-core' ),
			),
			'view_cart'        => array(
				'label'   => __( 'View-cart link', 'shopos-core' ),
				'default' => __( 'View cart', 'shopos-core' ),
			),
			'recommends'       => array(
				'label'   => __( 'Recommendations heading', 'shopos-core' ),
				'default' => __( 'You may also like', 'shopos-core' ),
			),
			'add'              => array(
				'label'   => __( 'Add-recommendation button', 'shopos-core' ),
				'default' => __( 'Add', 'shopos-core' ),
			),
			'free_ship_remaining' => array(
				'label'   => __( 'Free-shipping remaining (use %s for the amount)', 'shopos-core' ),
				'default' => __( 'Add %s more for free shipping', 'shopos-core' ),
			),
			'free_ship_reached' => array(
				'label'   => __( 'Free-shipping reached message', 'shopos-core' ),
				'default' => __( 'You’ve unlocked free shipping!', 'shopos-core' ),
			),
			'loading'          => array(
				'label'   => __( 'Loading message', 'shopos-core' ),
				'default' => __( 'Updating…', 'shopos-core' ),
			),
			'error'            => array(
				'label'   => __( 'Error message', 'shopos-core' ),
				'default' => __( 'Something went wrong. Please try again.', 'shopos-core' ),
			),
		);
	}
}
