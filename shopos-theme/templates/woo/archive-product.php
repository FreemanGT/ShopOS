<?php
/**
 * ShopOS product listing page (PLP) — the theme-owned archive template
 * (ShopOS Line).
 *
 * §11.4 row 5: resolved ONLY by the shared theme loader
 * (inc/class-shopos-template-loader.php) when the permanent kill-switch
 * `shopos_core_theme_template_plp_enabled` is on (flag off = the current
 * render, byte-identical). This path is never auto-located by WordPress,
 * WooCommerce, or Elementor — deliberately NOT `{theme}/woocommerce/` and
 * never a WC-recognized filename at the theme root (§11.3; both would render
 * flag-off at WC's priority 10 and join its outdated-template scan) — so
 * shipping this file changes nothing by presence alone.
 *
 * Provenance (§11.3 drift check — re-diff on every WooCommerce release):
 * - Verbatim hook-order copy of woocommerce/templates/archive-product.php
 *   (@version 8.6.0, read at WooCommerce 10.9.4), wrapped in ShopOS container
 *   markup. Every do_action the stock template fires, this one fires, in the
 *   same order; nothing is detached at claim time (unlike the PDP takeover).
 * - Loop items render via wc_get_template_part( 'content', 'product' ) —
 *   WooCommerce's own content-product.php (@version 9.4.0) — deliberately
 *   NOT copied: one fewer drift surface, and WC's own li.product markup is
 *   what keeps QuickView / HoverSwap / InfiniteScroll / the theme's
 *   mobile-cols CSS matching with zero detector changes (Ruling 7.2).
 * - Product-taxonomy archives route through taxonomy-product-cat.php
 *   (@version 4.7.0), which just re-renders archive-product.php — same hook
 *   surface, which is why the loader claims both with this one file.
 *
 * The grid stays WC's `ul.products` / `li.product` with no `.cs-*` classes on
 * or above it — load-bearing for the mobile-cols wp_head CSS
 * (inc/woocommerce.php) and for InfiniteScroll's existing selector fallbacks.
 *
 * The ShopFilters panel is re-rendered here (owner ask 3, 2026-07-17): on
 * Elementor archives the panel lives in the page content this template
 * displaces — without this slot, flag-on would silently drop the filters UI
 * while the module's query bridge keeps filtering server-side.
 *
 * This template adds no translatable strings of its own; everything visible
 * renders through WC / Core hook callbacks on their own textdomains.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' ); ?>

<main id="shopos-ui-plp-main" class="shopos-ui-plp">
	<div class="shopos-ui-plp__container">

		<?php
		/**
		 * Hook: woocommerce_before_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20
		 * @hooked WC_Structured_Data::generate_website_data() - 30
		 */
		do_action( 'woocommerce_before_main_content' );

		/**
		 * Hook: woocommerce_shop_loop_header.
		 *
		 * @hooked woocommerce_product_taxonomy_archive_header - 10
		 */
		do_action( 'woocommerce_shop_loop_header' );

		// ShopFilters slot (owner ask 3): the shortcode registers only while
		// the module is enabled — the guard keeps literal shortcode text off
		// the page otherwise. Placement above the loop is provisional pending
		// the R7.5 screenshot gate.
		if ( shortcode_exists( 'shopos_shop_filters' ) ) {
			echo '<div class="shopos-ui-plp__filters">' . do_shortcode( '[shopos_shop_filters]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output, module-built and escaped there.
		}

		if ( woocommerce_product_loop() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked woocommerce_output_all_notices - 10
			 * @hooked woocommerce_result_count - 20
			 * @hooked woocommerce_catalog_ordering - 30
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					/**
					 * Hook: woocommerce_shop_loop.
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}

		/**
		 * Hook: woocommerce_after_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'woocommerce_after_main_content' );

		/**
		 * Hook: woocommerce_sidebar.
		 *
		 * Kept for hook parity (owner ask 8, 2026-07-17); the visual is judged
		 * at the R7.5 screenshot gate. Recorded fallback if the parent renders
		 * unwanted widget content: claim-scoped
		 * remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 ).
		 *
		 * @hooked woocommerce_get_sidebar - 10
		 */
		do_action( 'woocommerce_sidebar' );
		?>

	</div>
</main>

<?php
get_footer( 'shop' );
