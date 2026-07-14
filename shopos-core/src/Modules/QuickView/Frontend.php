<?php
/**
 * Quick View — storefront wiring.
 *
 * Injects the trigger button into every product-loop card via
 * `woocommerce_after_shop_loop_item` (a direct child of the `<li>`, valid
 * HTML, absolutely positioned over the image corner by the stylesheet — the
 * WooSQ placement convention the slider CSS already accommodates), enqueues
 * the drawer assets, and prints one drawer shell in the footer when at least
 * one trigger rendered on the page.
 *
 * Only constructed when the frontend feature flag is on (Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\QuickView;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend.
 */
final class Frontend {

	const HANDLE = 'shopos-core-quick-view';

	/**
	 * @var Module
	 */
	private $module;

	/**
	 * Whether at least one trigger rendered this request — gates the footer
	 * drawer shell so non-product pages carry no dead DOM.
	 *
	 * @var bool
	 */
	private $trigger_rendered = false;

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
		// Priority 15: after WC's loop add-to-cart (10), so plugins that
		// reorder the card footer have settled before the trigger lands.
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'render_trigger' ), 15 );
		add_action( 'wp_footer', array( $this, 'render_drawer_shell' ) );
	}

	/**
	 * Enqueue drawer assets on the front end (skipping admin/feed).
	 */
	public function enqueue() {
		if ( is_admin() || is_feed() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/quick-view.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/quick-view.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);

		wp_localize_script( self::HANDLE, 'ShopOSQuickView', $this->localized_payload() );

		// The drawer gallery reuses the HoverSwap card-slider component verbatim
		// (markup + CSS + JS), so the in-drawer slider is identical to the one on
		// product cards. Same handle as Gallery_Slider so WP de-dupes when
		// HoverSwap is also in gallery-slider mode; loaded from the HoverSwap
		// module path via the shared min-picker.
		$cs_handle  = 'shopos-core-card-slider';
		$cs_fs_base = SHOPOS_CORE_PATH . 'src/Modules/HoverSwap/assets/';
		$cs_url     = SHOPOS_CORE_URL . 'src/Modules/HoverSwap/assets/';
		wp_enqueue_style(
			$cs_handle,
			\ShopOS\Core\Core\Module_Base::pick_min_url( $cs_fs_base, $cs_url, 'css/card-slider.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);
		wp_enqueue_script(
			$cs_handle,
			\ShopOS\Core\Core\Module_Base::pick_min_url( $cs_fs_base, $cs_url, 'js/card-slider.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
	}

	/**
	 * Build the ShopOSQuickView JS payload. Extracted so PHPUnit can assert
	 * the payload shape (InfiniteScroll precedent).
	 *
	 * @return array<string,mixed>
	 */
	public function localized_payload() {
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => Ajax::ACTION,
			'nonce'   => wp_create_nonce( Ajax::NONCE ),
			'labels'  => array(
				'loading' => Labels::get( 'loading' ),
				'error'   => Labels::get( 'error' ),
			),
		);
	}

	/**
	 * Echo the trigger button for the current loop product.
	 */
	public function render_trigger() {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		/**
		 * Filters whether the quick-view trigger renders for a product card.
		 *
		 * @since 1.13.0
		 *
		 * @param bool        $show    Whether to render the trigger.
		 * @param \WC_Product $product Current loop product.
		 */
		if ( ! apply_filters( 'shopos_core/quick_view/show_trigger', true, $product ) ) {
			return;
		}

		echo $this->trigger_html( $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
		$this->trigger_rendered = true;
	}

	/**
	 * Trigger button markup for a product id. Pure — unit-tested.
	 *
	 * @param int $product_id Product id.
	 * @return string
	 */
	public function trigger_html( $product_id ) {
		$label = Labels::get( 'trigger' );
		return '<button type="button" class="fc-qv-trigger" data-fc-qv="' . (int) $product_id . '"'
			. ' aria-label="' . esc_attr( $label ) . '" title="' . esc_attr( $label ) . '">'
			. '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">'
			. '<circle cx="7" cy="7" r="4.5" stroke="currentColor" stroke-width="1.3"/>'
			. '<path d="M10.5 10.5L14 14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/>'
			. '</svg>'
			. '</button>';
	}

	/**
	 * Print the drawer shell once in the footer — only when a trigger
	 * rendered on this page. The content container is filled by JS from the
	 * AJAX response.
	 */
	public function render_drawer_shell() {
		if ( ! $this->trigger_rendered ) {
			return;
		}
		echo $this->drawer_shell_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}

	/**
	 * Drawer shell markup. Pure — unit-tested.
	 *
	 * `fc-quick-view` deliberately contains "quick-view": VariationSwatches'
	 * `isInsideModal()` heuristic matches `[class*="quick-view"]`, which
	 * suppresses its viewport-fixed sticky bar inside the drawer.
	 *
	 * @return string
	 */
	public function drawer_shell_html() {
		$title = Labels::get( 'drawer_title' );
		$close = Labels::get( 'close' );

		return '<div class="fc-quick-view" id="fc-quick-view" aria-hidden="true">'
			. '<div class="fc-quick-view__overlay" data-fc-qv-close></div>'
			. '<aside class="fc-quick-view__panel" role="dialog" aria-modal="true" aria-label="' . esc_attr( $title ) . '" tabindex="-1">'
			. '<header class="fc-quick-view__head">'
			. '<h2 class="fc-quick-view__title">' . esc_html( $title ) . '</h2>'
			. '<button type="button" class="fc-quick-view__close" data-fc-qv-close aria-label="' . esc_attr( $close ) . '">'
			. '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M2 2L12 12M12 2L2 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>'
			. '</button>'
			. '</header>'
			. '<div class="fc-quick-view__body woocommerce" data-fc-qv-content aria-live="polite"></div>'
			. '</aside>'
			. '</div>';
	}
}
