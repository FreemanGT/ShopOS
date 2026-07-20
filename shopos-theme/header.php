<?php
/**
 * ShopOS theme header — flag-gated (theme.template_chrome, decisions §11.4 / §11-B).
 *
 * OFF (default): require-parent passthrough (Ruling 6). WordPress loads the
 * child header.php over the parent's, so we hand off to Hello Elementor's own
 * header.php — which renders today's chrome byte-identical (the Elementor Pro
 * `header` location, falling back to Hello's default). `return` so the ShopOS
 * markup below never emits when the flag is off.
 *
 * ON: the ShopOS-owned classic header (logo, primary menu, search, cart). Fires
 * the same head/body hooks the parent does so every module lights up unaided.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! ShopOS_Theme::chrome_enabled() ) {
	$parent_header = get_template_directory() . '/header.php';
	if ( is_readable( $parent_header ) ) {
		require $parent_header;
		return;
	}
}

$viewport_content = apply_filters( 'hello_elementor_viewport_content', 'width=device-width, initial-scale=1' );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="<?php echo esc_attr( $viewport_content ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php echo esc_html__( 'Skip to content', 'shopos-theme' ); ?></a>

<header class="shopos-chrome" role="banner">
	<div class="shopos-chrome__inner">
		<div class="shopos-chrome__brand">
			<?php
			if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="shopos-chrome__site-name" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php
			}
			?>
		</div>

		<?php if ( has_nav_menu( 'menu-1' ) ) : ?>
			<nav class="shopos-chrome__nav" id="shopos-chrome-nav" aria-label="<?php esc_attr_e( 'Primary', 'shopos-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'container'      => false,
						'menu_class'     => 'shopos-chrome__menu',
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<div class="shopos-chrome__actions">
			<?php
			// Reuse the module's search field (skin-light — no bespoke markup).
			if ( shortcode_exists( 'shopos_search' ) ) {
				echo do_shortcode( '[shopos_search]' );
			}
			?>

			<?php if ( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_cart_url' ) ) : ?>
				<?php $cart_count = ( WC()->cart instanceof WC_Cart ) ? WC()->cart->get_cart_contents_count() : 0; ?>
				<a class="shopos-chrome__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'View cart', 'shopos-theme' ); ?>">
					<span class="shopos-chrome__cart-icon" aria-hidden="true"></span>
					<span class="shopos-chrome__cart-count"><?php echo esc_html( (string) $cart_count ); ?></span>
				</a>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'menu-1' ) ) : ?>
				<button class="shopos-chrome__toggle" type="button" aria-expanded="false" aria-controls="shopos-chrome-nav" aria-label="<?php esc_attr_e( 'Toggle menu', 'shopos-theme' ); ?>">
					<span class="shopos-chrome__toggle-bar" aria-hidden="true"></span>
				</button>
			<?php endif; ?>
		</div>
	</div>
</header>

<div id="content" class="shopos-chrome__content">
