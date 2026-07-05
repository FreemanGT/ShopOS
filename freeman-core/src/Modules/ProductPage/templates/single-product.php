<?php
/**
 * Freeman-designed single product page (template takeover).
 *
 * Theme override: freeman/product_page/single-product.php
 *
 * Structure: breadcrumb → two-column layout (gallery | sticky summary) →
 * accordion sections (the product-tabs stack rendered as <details>) →
 * upsells / related → mobile sticky add-to-cart bar. The gallery and
 * summary render the standard WooCommerce hook stacks, so everything that
 * hooks a stock PDP (VariationSwatches buy box, RestockNotify, structured
 * data, this module's coupon / urgency widgets) lights up unaided.
 *
 * WC's default tabs / upsells / related callbacks were detached at takeover
 * time (Template_Loader::maybe_takeover()); the after-summary hook still
 * fires below for third parties.
 *
 * @package FreemanCore
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' ); ?>

<main id="fm-pdp-main" class="fm-pdp">
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
		<div class="fm-pdp__container">

			<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
				<nav class="fm-pdp__breadcrumb" aria-label="<?php echo esc_attr__( 'Breadcrumb', 'freeman-core' ); ?>">
					<?php
					woocommerce_breadcrumb(
						array(
							'delimiter'   => '<span class="fm-pdp__crumb-sep" aria-hidden="true">/</span>',
							'wrap_before' => '<div class="fm-pdp__crumbs">',
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

			<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'fm-pdp__product', $product ); ?>>

				<div class="fm-pdp__layout">
					<div class="fm-pdp__gallery">
						<?php
						/** Sale flash (10) + product gallery (20). Documented in woocommerce/templates/content-single-product.php */
						do_action( 'woocommerce_before_single_product_summary' );
						?>
					</div>

					<div class="fm-pdp__summary-col">
						<div class="fm-pdp__summary summary entry-summary">
							<?php
							/** Title (5) / price (10) / excerpt (20) / add-to-cart (30) / meta (40) / sharing (50). Documented in woocommerce/templates/content-single-product.php */
							do_action( 'woocommerce_single_product_summary' );
							?>
						</div>
						<?php echo \Freeman\Core\Modules\ProductPage\Template_Loader::trust_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts; '' when both labels are empty. ?>
					</div>
				</div>

				<div class="fm-pdp__sections">
					<?php
					// The product-tabs stack (description / additional information /
					// reviews + whatever plugins add) rendered as an accordion —
					// the same filter WC's tabs template reads.
					$fm_tabs  = apply_filters( 'woocommerce_product_tabs', array() );
					$fm_first = true;
					if ( ! empty( $fm_tabs ) ) :
						?>
						<section class="fm-pdp__accordion">
							<?php foreach ( $fm_tabs as $fm_key => $fm_tab ) : ?>
								<details class="fm-pdp__acc-item" <?php echo $fm_first ? 'open' : ''; ?>>
									<summary class="fm-pdp__acc-summary">
										<span class="fm-pdp__acc-title">
											<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $fm_key . '_tab_title', $fm_tab['title'], $fm_key ) ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- WC's own tab-title filter, documented in woocommerce/templates/single-product/tabs/tabs.php ?>
										</span>
										<svg class="fm-pdp__acc-chevron" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
											<path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
										</svg>
									</summary>
									<div class="fm-pdp__acc-body">
										<?php
										if ( isset( $fm_tab['callback'] ) ) {
											call_user_func( $fm_tab['callback'], $fm_key, $fm_tab );
										}
										?>
									</div>
								</details>
								<?php
								$fm_first = false;
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
					$fm_large_thumbs = static function () {
						return 'large';
					};
					add_filter( 'single_product_archive_thumbnail_size', $fm_large_thumbs );
					if ( function_exists( 'woocommerce_upsell_display' ) ) {
						woocommerce_upsell_display();
					}
					if ( function_exists( 'woocommerce_output_related_products' ) ) {
						woocommerce_output_related_products();
					}
					remove_filter( 'single_product_archive_thumbnail_size', $fm_large_thumbs );
					?>
				</div>

			</div>

			<?php
			/** This action is documented in woocommerce/templates/content-single-product.php */
			do_action( 'woocommerce_after_single_product' );
			?>

		</div>

		<div class="fm-pdp__sticky-bar" data-fm-sticky-bar hidden>
			<div class="fm-pdp__sticky-info">
				<?php echo $product->get_image( 'woocommerce_gallery_thumbnail', array( 'class' => 'fm-pdp__sticky-thumb' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built img tag. ?>
				<div class="fm-pdp__sticky-text">
					<span class="fm-pdp__sticky-title"><?php echo esc_html( get_the_title() ); ?></span>
					<span class="fm-pdp__sticky-price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built price HTML. ?></span>
				</div>
			</div>
			<button type="button" class="fm-pdp__sticky-cta" data-fm-sticky-cta>
				<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
			</button>
		</div>
	<?php endwhile; ?>
</main>

<?php
get_footer( 'shop' );
