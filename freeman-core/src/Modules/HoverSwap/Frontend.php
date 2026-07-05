<?php
/**
 * Hover Image Swap — storefront wiring.
 *
 * Injects the overlay <img> right after WooCommerce's loop thumbnail via
 * `woocommerce_before_shop_loop_item_title` (priority 11 — WC renders the
 * thumbnail on the same hook at priority 10), so the overlay lands inside the
 * open product-link anchor, as a sibling of the primary image. The stylesheet
 * absolutely positions it over the thumbnail and cross-fades it in on card
 * hover. No JS — infinite-scroll-loaded cards carry the overlay too.
 *
 * Constructed whenever the module is active (always-on since 1.16.1, routed by the `card_image_mode` setting).
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\HoverSwap;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
final class Frontend {

	const HANDLE = 'freeman-core-hover-swap';

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
	 * Register hooks.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		// Priority 11: WC's loop thumbnail renders on this hook at priority 10,
		// so the overlay lands immediately after it, inside the product link.
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_overlay' ), 11 );
	}

	/**
	 * Enqueue the cross-fade stylesheet on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/hover-swap.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);
	}

	/**
	 * Echo the overlay image for the current loop product.
	 */
	public function render_overlay() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		/**
		 * Filters whether the hover-swap overlay renders for a product card.
		 *
		 * @since 1.15.0
		 *
		 * @param bool        $show    Whether to render the overlay.
		 * @param \WC_Product $product Current loop product.
		 */
		if ( ! apply_filters( 'freeman_core/hover_swap/show', true, $product ) ) {
			return;
		}

		echo $this->overlay_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() output.
	}

	/**
	 * The product's second image — the first gallery image. Pure, unit-tested.
	 *
	 * @param \WC_Product $product Product.
	 * @return int Attachment id, or 0 when the product has no gallery image.
	 */
	public function second_image_id( \WC_Product $product ) {
		$ids = $product->get_gallery_image_ids();
		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return 0;
		}
		return (int) reset( $ids );
	}

	/**
	 * Overlay <img> markup for a product — empty string when there is no
	 * gallery image (no-op). Pure, unit-tested.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function overlay_html( \WC_Product $product ) {
		$image_id = $this->second_image_id( $product );
		if ( ! $image_id ) {
			return '';
		}

		$img = wp_get_attachment_image(
			$image_id,
			'woocommerce_thumbnail',
			false,
			array(
				'class'       => 'fc-hover-swap__img',
				'loading'     => 'lazy',
				'aria-hidden' => 'true',
			)
		);

		return is_string( $img ) ? $img : '';
	}
}
