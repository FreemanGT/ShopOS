<?php
/**
 * ShopOS theme footer — flag-gated (theme.template_chrome, decisions §11.4 / §11-B).
 *
 * OFF (default): require-parent passthrough (Ruling 6) — hand off to Hello
 * Elementor's footer.php so today's chrome renders byte-identical.
 *
 * ON: close the `#content` wrapper opened in header.php, then render the
 * ShopOS-owned footer (menu, widget area, copyright) before wp_footer().
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

if ( ! ShopOS_Theme::chrome_enabled() ) {
	$parent_footer = get_template_directory() . '/footer.php';
	if ( is_readable( $parent_footer ) ) {
		require $parent_footer;
		return;
	}
}
?>
		</div><?php // /#content (opened in header.php) ?>

<footer class="shopos-chrome shopos-chrome--footer" role="contentinfo">
	<div class="shopos-chrome__inner">
		<?php if ( has_nav_menu( 'menu-2' ) ) : ?>
			<nav class="shopos-chrome__footer-nav" aria-label="<?php esc_attr_e( 'Footer', 'shopos-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-2',
						'container'      => false,
						'menu_class'     => 'shopos-chrome__footer-menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'shopos-footer' ) ) : ?>
			<div class="shopos-chrome__widgets">
				<?php dynamic_sidebar( 'shopos-footer' ); ?>
			</div>
		<?php endif; ?>

		<p class="shopos-chrome__copyright">
			<?php
			printf(
				/* translators: 1: year, 2: site name. */
				esc_html__( '© %1$s %2$s. All rights reserved.', 'shopos-theme' ),
				esc_html( gmdate( 'Y' ) ),
				esc_html( get_bloginfo( 'name' ) )
			);
			?>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>

</body>
</html>
