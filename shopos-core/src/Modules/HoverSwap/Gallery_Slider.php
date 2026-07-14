<?php
/**
 * Card Image Effects — gallery-slider mode.
 *
 * Replaces WooCommerce's single loop thumbnail with a small swipeable slider
 * of all the product's images (primary first, then the gallery). It swaps the
 * default `woocommerce_template_loop_product_thumbnail` for its own render on
 * the same hook, so the slider lands inside the product-link anchor exactly
 * where the thumbnail was — on every loop card site-wide. (Deliberately not
 * gated by `is_shop()` / `is_product_taxonomy()`: arba4's Elementor archive
 * grid runs on a swapped query where those conditional tags lie.)
 *
 * Single-image products fall back to a plain image (no slider chrome). The
 * swipe interaction is native CSS scroll-snap (touch + trackpad, RTL-safe);
 * the optional hover arrows and mouse drag-to-scroll are progressive
 * enhancements driven by card-slider.js. Only constructed in `gallery_slider`
 * mode (Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\HoverSwap;

defined( 'ABSPATH' ) || exit;

/**
 * Gallery_Slider.
 */
final class Gallery_Slider {

	const HANDLE = 'shopos-core-card-slider';

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * @param Module $module Owning module.
	 */
	public function __construct( Module $module ) {
		$this->module = $module;
	}

	/**
	 * Register hooks. Swaps WC's loop thumbnail for the slider on the same
	 * hook + priority so the slider occupies the thumbnail's slot.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_slider' ), 10 );
	}

	/**
	 * Enqueue the slider assets on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/card-slider.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/card-slider.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
	}

	/**
	 * Echo the slider for the current loop product.
	 */
	public function render_slider() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}
		echo $this->slider_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from wp_get_attachment_image() + escaped parts.
	}

	/**
	 * Image ids for the slides — primary image first, then the gallery, deduped.
	 * Pure, unit-tested.
	 *
	 * @param \WC_Product $product Product.
	 * @return int[]
	 */
	public function slide_image_ids( \WC_Product $product ) {
		$ids     = array();
		$primary = (int) $product->get_image_id();
		if ( $primary ) {
			$ids[] = $primary;
		}
		foreach ( (array) $product->get_gallery_image_ids() as $gid ) {
			$gid = (int) $gid;
			if ( $gid && ! in_array( $gid, $ids, true ) ) {
				$ids[] = $gid;
			}
		}
		return $ids;
	}

	/**
	 * Slider markup for a product. One image → a plain image (no chrome); two
	 * or more → the swipeable slider, with hover arrows when that setting is on.
	 * Empty string when the product has no image at all. Pure, unit-tested.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function slider_html( \WC_Product $product ) {
		$ids = $this->slide_image_ids( $product );
		if ( empty( $ids ) ) {
			return '';
		}

		$slides = '';
		foreach ( $ids as $i => $id ) {
			$img     = wp_get_attachment_image(
				$id,
				'woocommerce_thumbnail',
				false,
				array(
					'class'   => 'fc-card-slider__img',
					'loading' => 0 === $i ? 'eager' : 'lazy',
				)
			);
			$slides .= '<div class="fc-card-slider__slide">' . ( is_string( $img ) ? $img : '' ) . '</div>';
		}

		// Single image — a plain thumbnail, no slider chrome.
		if ( count( $ids ) < 2 ) {
			return '<div class="fc-card-slider fc-card-slider--single"><div class="fc-card-slider__viewport">' . $slides . '</div></div>';
		}

		return '<div class="fc-card-slider" data-fc-card-slider>'
			. '<div class="fc-card-slider__viewport">' . $slides . '</div>'
			. $this->arrows_html()
			. '</div>';
	}

	/**
	 * Prev / next arrow markup, or '' when the arrows setting is off.
	 *
	 * @return string
	 */
	private function arrows_html() {
		if ( ! (bool) $this->module->get_option( 'slider_arrows', 1 ) ) {
			return '';
		}

		$prev = esc_attr__( 'Previous image', 'shopos-core' );
		$next = esc_attr__( 'Next image', 'shopos-core' );

		return '<button type="button" class="fc-card-slider__arrow fc-card-slider__arrow--prev" data-fc-slider-prev aria-label="' . $prev . '">'
			. '<svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M7 1.5L3 5.5l4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			. '</button>'
			. '<button type="button" class="fc-card-slider__arrow fc-card-slider__arrow--next" data-fc-slider-next aria-label="' . $next . '">'
			. '<svg width="11" height="11" viewBox="0 0 11 11" fill="none" aria-hidden="true"><path d="M4 1.5l4 4-4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			. '</button>';
	}
}
