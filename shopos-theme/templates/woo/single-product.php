<?php
/**
 * ShopOS-designed single product page — the theme-owned copy (ShopOS Line).
 *
 * §11.4 row 4: resolved ONLY by Core's ProductPage Template_Loader when the
 * permanent kill-switch `shopos_core_theme_template_pdp_enabled` is on (flag
 * off = the Core module copy renders, byte-identical). This path is never
 * auto-located by WordPress, WooCommerce, or Elementor — deliberately NOT
 * `{theme}/woocommerce/` (§11.3) — so shipping this file changes nothing by
 * presence alone, and the file only ever loads with Core active.
 *
 * Provenance (§11.3 drift check — re-diff on every WooCommerce release):
 * - Verbatim body copy of shopos-core/src/Modules/ProductPage/templates/
 *   single-product.php as of shopos-core 1.43.0 (the flag's off-state
 *   renderer; keep the two in lockstep until they intentionally diverge).
 * - Renders the standard WC single-product hook stacks as documented in
 *   woocommerce/templates/content-single-product.php (@version 3.6.0) and
 *   woocommerce/templates/single-product.php (@version 1.6.4).
 *
 * Structure: breadcrumb → two-column layout (gallery | sticky summary) →
 * accordion sections (the product-tabs stack rendered as <details>) →
 * upsells / related → mobile sticky add-to-cart bar. The gallery and
 * summary render the standard WooCommerce hook stacks, so everything that
 * hooks a stock PDP (VariationSwatches buy box, RestockNotify, structured
 * data, the ProductPage module's coupon / urgency widgets) lights up
 * unaided. WC's default tabs / upsells / related callbacks were detached at
 * takeover time (Template_Loader::maybe_takeover()); the after-summary hook
 * still fires below for third parties.
 *
 * Strings stay on the `shopos-core` textdomain on purpose: identical msgids
 * to the module copy resolve through Core's loaded translations, keeping the
 * rendered (Hebrew) output byte-identical — and Core is always present when
 * this file loads.
 *
 * @package ShopOSTheme
 */


defined( 'ABSPATH' ) || exit;

get_header( 'shop' ); ?>

<main id="shopos-ui-pdp-main" class="shopos-ui-pdp">
	<?php
	while ( have_posts() ) :
		the_post();
		global $product;
		if ( ! $product instanceof WC_Product && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		?>
		<div class="shopos-ui-pdp__container">

			<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
				<nav class="shopos-ui-pdp__breadcrumb" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'shopos-core' ); ?>">
					<?php
					woocommerce_breadcrumb(
						array(
							'delimiter'   => '<span class="shopos-ui-pdp__crumb-sep" aria-hidden="true">/</span>',
							'wrap_before' => '<div class="shopos-ui-pdp__crumbs">',
							'wrap_after'  => '</div>',
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<?php
			/** This action is documented in woocommerce/templates/content-single-product.php */
			do_action( 'woocommerce_before_single_product' );
			?>

			<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'shopos-ui-pdp__product', $product ); ?>>

				<div class="shopos-ui-pdp__layout">
					<div class="shopos-ui-pdp__gallery">
						<?php
						/** Sale flash (10) + product gallery (20). Documented in woocommerce/templates/content-single-product.php */
						do_action( 'woocommerce_before_single_product_summary' );
						?>
					</div>

					<div class="shopos-ui-pdp__summary-col">
						<div class="shopos-ui-pdp__summary summary entry-summary">
							<?php
							/** Title (5) / price (10) / excerpt (20) / add-to-cart (30) / meta (40) / sharing (50). Documented in woocommerce/templates/content-single-product.php */
							do_action( 'woocommerce_single_product_summary' );
							// Trust line (36) + additional-information (38) render
							// inside the stack above via Template_Loader, so they
							// sit directly under the buy box.
							?>
						</div>
					</div>
				</div>

				<div class="shopos-ui-pdp__sections">
					<?php
					// The product-tabs stack (description / additional information /
					// reviews + whatever plugins add) rendered as an accordion —
					// the same filter WC's tabs template reads.
					$shopos_tabs = apply_filters( 'woocommerce_product_tabs', array() );
					// Additional information is surfaced under the buy box
					// (Template_Loader::render_additional_information at summary
					// priority 38), so drop it from the accordion to avoid a
					// duplicate; description / reviews / plugin tabs stay here.
					unset( $shopos_tabs['additional_information'] );
					$shopos_first = true;
					if ( ! empty( $shopos_tabs ) ) :
						?>
						<section class="shopos-ui-pdp__accordion">
							<?php foreach ( $shopos_tabs as $shopos_key => $shopos_tab ) : ?>
								<details class="shopos-ui-pdp__acc-item" <?php echo $shopos_first ? 'open' : ''; ?>>
									<summary class="shopos-ui-pdp__acc-summary">
										<span class="shopos-ui-pdp__acc-title">
											<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $shopos_key . '_tab_title', $shopos_tab['title'], $shopos_key ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WC's own tab-title filter, documented in woocommerce/templates/single-product/tabs/tabs.php ?>
										</span>
										<svg class="shopos-ui-pdp__acc-chevron" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
											<path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</summary>
									<div class="shopos-ui-pdp__acc-body">
										<?php
										if ( isset( $shopos_tab['callback'] ) ) {
											call_user_func( $shopos_tab['callback'], $shopos_key, $shopos_tab );
										}
										?>
									</div>
								</details>
								<?php
								$shopos_first = false;
							endforeach;
							?>
						</section>
					<?php endif; ?>

					<?php
					/** Third-party attachments only — WC's tabs / upsells / related were detached at takeover. Documented in woocommerce/templates/content-single-product.php */
					do_action( 'woocommerce_after_single_product_summary' );

					// Scoped size bump for the upsell/related loops: the default
					// woocommerce_thumbnail (~324px) upscales blurry in this grid
					// on hi-DPI (the ProductSlider 1.21.18 lesson). Consumer-only
					// filter use — added and removed around the two loops.
					$shopos_large_thumbs = static function () {
						return 'large';
					};
					// Companion to the `large` request: emit the real card slot so
					// the browser doesn't download the viewport-wide srcset
					// candidate for a ~2-of-4-column tile (the ProductSlider
					// 1.21.23 lesson). 2 cols < 768px, 4 cols above, capped by the
					// 1360px container → ~320px per card on wide screens.
					$shopos_related_sizes = static function () {
						return '(max-width: 767px) 45vw, (max-width: 1360px) 23vw, 320px';
					};
					// Pin the related loop to the 4-up grid the stylesheet
					// draws (2-up on mobile): a site-level columns/count
					// filter (e.g. 3 columns) would re-introduce the 3+1 row
					// wrap the owner reported (Wave 9.3). Consumer-only,
					// added late and removed after the loop.
					$shopos_related_args = static function ( $args ) {
						$args['posts_per_page'] = 4;
						$args['columns']        = 4;
						return $args;
					};
					add_filter( 'single_product_archive_thumbnail_size', $shopos_large_thumbs );
					add_filter( 'wp_calculate_image_sizes', $shopos_related_sizes );
					add_filter( 'woocommerce_output_related_products_args', $shopos_related_args, 9999 );
					if ( function_exists( 'woocommerce_upsell_display' ) ) {
						woocommerce_upsell_display();
					}
					if ( function_exists( 'woocommerce_output_related_products' ) ) {
						woocommerce_output_related_products();
					}
					remove_filter( 'woocommerce_output_related_products_args', $shopos_related_args, 9999 );
					remove_filter( 'wp_calculate_image_sizes', $shopos_related_sizes );
					remove_filter( 'single_product_archive_thumbnail_size', $shopos_large_thumbs );
					?>
				</div>

			</div>

			<?php
			/** This action is documented in woocommerce/templates/content-single-product.php */
			do_action( 'woocommerce_after_single_product' );
			?>

		</div>

		<div class="shopos-ui-pdp__sticky-bar" data-shopos-ui-sticky-bar hidden>
			<div class="shopos-ui-pdp__sticky-info">
				<?php echo $product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'shopos-ui-pdp__sticky-thumb' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built img tag. ?>
				<div class="shopos-ui-pdp__sticky-text">
					<span class="shopos-ui-pdp__sticky-title"><?php echo esc_html( get_the_title() ); ?></span>
					<span class="shopos-ui-pdp__sticky-price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built price HTML. ?></span>
				</div>
			</div>
			<button type="button" class="shopos-ui-pdp__sticky-cta" data-shopos-ui-sticky-cta>
				<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
			</button>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer( 'shop' );
