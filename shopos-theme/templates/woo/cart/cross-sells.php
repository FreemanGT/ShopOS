<?php
/**
 * ShopOS Line — cart cross-sells (§11-B surface 2, theme.template_cart).
 *
 * Forked from WooCommerce templates/cart/cross-sells.php @version 9.6.0.
 * Reached only via ShopOS_Theme::locate_cart_template() (flag on). The product
 * loop delegates to `content-product.php` (unchanged — the same card the shop /
 * PLP render), preserving every loop hook; a `shopos-cart__cross-sells` class
 * is added to the wrapper for token styling.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

if ( $cross_sells ) : ?>

	<div class="cross-sells shopos-cart__cross-sells">
		<?php
		/** This filter is documented in WooCommerce templates/cart/cross-sells.php. */
		$heading = apply_filters( 'woocommerce_product_cross_sells_products_heading', __( 'You may be interested in&hellip;', 'woocommerce' ) );

		if ( $heading ) :
			?>
			<h2><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php woocommerce_product_loop_start(); ?>

			<?php foreach ( $cross_sells as $cross_sell ) : ?>

				<?php
					$post_object = get_post( $cross_sell->get_id() );

					setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

					wc_get_template_part( 'content', 'product' );
				?>

			<?php endforeach; ?>

		<?php woocommerce_product_loop_end(); ?>

	</div>
	<?php
endif;

wp_reset_postdata();
