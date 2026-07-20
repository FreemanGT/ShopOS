<?php
/**
 * ShopOS Line — proceed to checkout button (§11-B surface 2, theme.template_cart).
 *
 * Forked from WooCommerce templates/cart/proceed-to-checkout-button.php
 * @version 7.0.1. Reached only via ShopOS_Theme::locate_cart_template() (flag
 * on). The `checkout-button` / `wc-forward` classes are preserved (WC binds to
 * them); a `shopos-cart__checkout-button` class is added for token styling.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;
?>

<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> shopos-cart__checkout-button">
	<?php esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?>
</a>
