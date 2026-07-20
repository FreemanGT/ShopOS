<?php
/**
 * ShopOS Line — empty cart (§11-B surface 2, theme.template_cart).
 *
 * Forked from WooCommerce templates/cart/cart-empty.php @version 7.0.1. Reached
 * only via ShopOS_Theme::locate_cart_template() (flag on). Additive reskin: the
 * `woocommerce_cart_is_empty` action + return-to-shop filters/link are
 * preserved; a `shopos-cart shopos-cart--empty` scope wrapper is added.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked wc_empty_cart_message - 10
 */
?>
<div class="shopos-cart shopos-cart--empty">
<?php do_action( 'woocommerce_cart_is_empty' ); ?>

<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
	<p class="return-to-shop shopos-cart__return">
		<a class="button wc-backward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?> shopos-cart__return-link" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php
				/** This filter is documented in WooCommerce templates/cart/cart-empty.php. */
				echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Return to shop', 'woocommerce' ) ) );
			?>
		</a>
	</p>
<?php endif; ?>
</div><?php // /.shopos-cart--empty ?>
