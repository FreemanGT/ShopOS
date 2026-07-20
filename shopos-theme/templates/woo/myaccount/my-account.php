<?php
/**
 * ShopOS Line — My Account shell (§11-B surface 3, theme.template_account).
 *
 * Forked from WooCommerce templates/myaccount/my-account.php @version 3.5.0.
 * Reached ONLY via ShopOS_Theme::locate_woo_template() when the account surface
 * flag is on — never by file presence (it lives at templates/woo/myaccount/,
 * not the auto-located {theme}/woocommerce/ path; §11.3). Flag off ⇒
 * WooCommerce's own copy renders, byte-identical (§11 Ruling 6).
 *
 * Additive reskin: the two account `do_action`s are preserved exactly; ShopOS
 * only adds a `.shopos-account` wrapper so `navigation.php` renders as a sidebar
 * beside the content (a grid CSS can scope). Every OTHER myaccount template
 * (dashboard, orders, view-order, downloads, payment-methods, addresses, and
 * the auth/payment forms) is CSS-skinned under this scope rather than forked —
 * so WooCommerce keeps ownership of the form nonces and gateway fields.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="shopos-account">
	<?php
	/**
	 * My Account navigation.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_navigation' );
	?>

	<div class="woocommerce-MyAccount-content shopos-account__content">
		<?php
			/**
			 * My Account content.
			 *
			 * @since 2.6.0
			 */
			do_action( 'woocommerce_account_content' );
		?>
	</div>
</div>
