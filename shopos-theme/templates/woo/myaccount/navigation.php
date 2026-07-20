<?php
/**
 * ShopOS Line — My Account navigation (§11-B surface 3, theme.template_account).
 *
 * Forked from WooCommerce templates/myaccount/navigation.php @version 9.3.0.
 * Reached only via ShopOS_Theme::locate_woo_template() (flag on). Additive
 * reskin: the before/after navigation hooks, the `wc_get_account_menu_items()`
 * loop and WooCommerce's `.woocommerce-MyAccount-navigation` class + endpoint
 * classes are preserved verbatim (WC targets them); a `shopos-account__nav`
 * class is added for the sidebar styling scope.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );
?>

<nav class="woocommerce-MyAccount-navigation shopos-account__nav" aria-label="<?php esc_html_e( 'Account pages', 'woocommerce' ); ?>">
	<ul>
		<?php foreach ( wc_get_account_menu_items() as $endpoint => $label ) : ?>
			<li class="<?php echo wc_get_account_menu_item_classes( $endpoint ); ?>">
				<a href="<?php echo esc_url( wc_get_account_endpoint_url( $endpoint ) ); ?>" <?php echo wc_is_current_account_menu_item( $endpoint ) ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action( 'woocommerce_after_account_navigation' ); ?>
