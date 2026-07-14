<?php
/**
 * Quick View drawer content.
 *
 * Theme override: shopos/quick_view/drawer-content.php
 *
 * Rendered server-side by the AJAX endpoint with the product globals set up
 * (Module::render_drawer_content()), so the standard single-product summary
 * hook stack fires exactly as it does inside any quick-view modal: WC's
 * title / rating / price / excerpt / add-to-cart / meta callbacks, plus
 * whatever plugins attach (VariationSwatches swaps the add-to-cart surface
 * for its buy box and de-duplicates the price line at priority 9).
 *
 * @package ShopOSCore
 *
 * @var \WC_Product $product     Product being quick-viewed.
 * @var int[]       $gallery_ids Ordered gallery image ids (featured first); ≥2 enables the slider.
 */

use ShopOS\Core\Modules\QuickView\Labels;

defined( 'ABSPATH' ) || exit;

$gallery_ids = isset( $gallery_ids ) ? (array) $gallery_ids : array();
?>
<div <?php wc_product_class( 'fc-quick-view__product', $product ); ?>>
	<div class="fc-quick-view__media">
		<?php if ( count( $gallery_ids ) >= 2 ) : ?>
			<?php // Reuses the HoverSwap card-slider markup/CSS/JS verbatim so the
				  // drawer gallery is the exact same component (arrows + scroll-snap
				  // + drag + loop). card-slider.js auto-inits it via its
				  // MutationObserver once this content is injected into the drawer. ?>
			<div class="fc-card-slider" data-fc-card-slider>
				<div class="fc-card-slider__viewport">
					<?php foreach ( $gallery_ids as $gid ) : ?>
						<div class="fc-card-slider__slide">
							<?php echo wp_get_attachment_image( (int) $gid, 'woocommerce_single', false, array( 'class' => 'fc-card-slider__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP-built img tag. ?>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="fc-card-slider__arrow fc-card-slider__arrow--prev" data-fc-slider-prev aria-label="<?php echo esc_attr__( 'Previous image', 'shopos-core' ); ?>">
					<svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M7 1.5L3 5.5l4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="fc-card-slider__arrow fc-card-slider__arrow--next" data-fc-slider-next aria-label="<?php echo esc_attr__( 'Next image', 'shopos-core' ); ?>">
					<svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M4 1.5l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			</div>
		<?php else : ?>
			<?php echo $product->get_image( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built img tag. ?>
		<?php endif; ?>
	</div>
	<div class="fc-quick-view__summary summary entry-summary">
		<?php
		/** This action is documented in woocommerce/templates/content-single-product.php */
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>
	<p class="fc-quick-view__details">
		<a class="fc-quick-view__details-link" href="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>">
			<?php echo esc_html( Labels::get( 'details' ) ); ?>
		</a>
	</p>
</div>
