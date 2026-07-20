<?php
/**
 * ShopOS product search-results page — the theme-owned search template
 * (ShopOS Line, §11-B surface 4).
 *
 * §11.4 / §11 Ruling 2 search carve-out: resolved ONLY by the shared theme
 * loader (inc/class-shopos-template-loader.php) when the permanent
 * kill-switch `shopos_core_theme_template_search_enabled` is on AND the
 * request is a product-archive main query carrying a search term
 * (should_claim_search()). Flag off = the current render, byte-identical.
 * Never auto-located by WordPress, WooCommerce, or Elementor — deliberately
 * NOT `{theme}/woocommerce/` and never a WC-recognized filename at the theme
 * root (§11.3) — so shipping this file changes nothing by presence alone.
 *
 * The Search module's Results_Query (shopos-core) has already constrained the
 * main query to the engine-ranked product ids (post__in) and fed the Shop
 * Filters facet universe by the time this template renders; this file only
 * reskins the surrounding archive. It is a near-verbatim fork of the theme's
 * archive-product.php (same WooCommerce hook order, @version 8.6.0 read at
 * WooCommerce 10.9.4 — re-diff on every WooCommerce release) with two search
 * deltas: a results heading naming the term, and the empty state is WC's own
 * `woocommerce_no_products_found` (its "no products matched" copy).
 *
 * The grid stays WC's `ul.products` / `li.product` with no `.cs-*` classes on
 * or above it — load-bearing for the mobile-cols wp_head CSS
 * (inc/woocommerce.php) and for InfiniteScroll's existing selector fallbacks.
 *
 * This template adds no translatable strings beyond the results heading;
 * everything else renders through WC / Core hook callbacks on their own
 * textdomains.
 *
 * @package ShopOSTheme
 */

defined( 'ABSPATH' ) || exit;

// The searched term for the heading. Read where it actually lives (the request,
// not the query var — on the live Elementor search page it never reaches the
// query vars; Results_Query / the theme loader precedent). request_search_term()
// already trims + sanitises; its unparseable-payload sentinels ('[array]',
// '[unparseable]') are degenerate inputs that never carry a real query, so the
// heading simply falls back to the generic label for them.
$shopos_search_term = '';
if ( class_exists( 'ShopOS_Theme_Template_Loader' ) ) {
	$shopos_search_term = ShopOS_Theme_Template_Loader::request_search_term();
	if ( '[array]' === $shopos_search_term || '[unparseable]' === $shopos_search_term ) {
		$shopos_search_term = '';
	}
}

get_header( 'shop' ); ?>

<main id="shopos-ui-search-main" class="shopos-ui-search">
	<div class="shopos-ui-search__container">

		<header class="shopos-ui-search__header">
			<?php if ( '' !== $shopos_search_term ) : ?>
				<h1 class="shopos-ui-search__title">
					<?php
					/* translators: %s: the search term. */
					printf( esc_html__( 'Search results for “%s”', 'shopos-theme' ), esc_html( $shopos_search_term ) );
					?>
				</h1>
			<?php else : ?>
				<h1 class="shopos-ui-search__title"><?php esc_html_e( 'Search results', 'shopos-theme' ); ?></h1>
			<?php endif; ?>
		</header>

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

		// ShopFilters slot: search results are filterable too — Results_Query
		// feeds the same engine ids into the facet base universe, so the panel
		// counts match this grid. The shortcode registers only while the module
		// is enabled; the guard keeps literal shortcode text off the page
		// otherwise. Mirrors archive-product.php (owner ask 3).
		if ( shortcode_exists( 'shopos_shop_filters' ) ) {
			echo '<div class="shopos-ui-search__filters">' . do_shortcode( '[shopos_shop_filters]' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode output, module-built and escaped there.
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
		 * Kept for hook parity with archive-product.php; the visual is judged at
		 * the R7.5 screenshot gate. Recorded fallback if the parent renders
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
