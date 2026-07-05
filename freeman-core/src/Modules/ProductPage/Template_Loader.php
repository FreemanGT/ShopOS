<?php
/**
 * Product Page — designed-layout template takeover.
 *
 * Swaps the single-product template for the module's designed page via
 * `template_include` at priority 9999 — late enough to win over Elementor
 * Pro's Theme Builder single-product location (priority 12), which is what
 * renders the current product page. Flag-off, nothing registers and the
 * existing page renders untouched (the rollback path).
 *
 * The takeover template renders the standard WooCommerce single-product
 * hook stack (`woocommerce_before_single_product_summary` for sale flash +
 * gallery, `woocommerce_single_product_summary` for title / price /
 * add-to-cart / meta), so VariationSwatches' buy box, RestockNotify's OOS
 * form, WC structured data and this module's own coupon / urgency widgets
 * all light up unaided. WooCommerce's default tabs / upsells / related
 * callbacks on `woocommerce_after_single_product_summary` are detached at
 * takeover time because the template renders its own accordion + sections
 * in their place; third-party attachments to that hook still fire where the
 * template calls it.
 *
 * Also declares the WC product-gallery theme supports (zoom / lightbox /
 * thumbnail slider) so the native gallery ships fully featured — the theme
 * itself never declared them because it never styled the PDP.
 *
 * Template is theme-overridable at `freeman/product_page/single-product.php`.
 *
 * Only constructed when the layout feature flag is on (Module::boot()).
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Template loader.
 */
final class Template_Loader {

	const HANDLE = 'freeman-core-product-page';

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
		add_filter( 'template_include', array( $this, 'maybe_takeover' ), 9999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
		// Modules boot on plugins_loaded — before the theme's setup — so the
		// gallery supports attach after the theme has declared its own.
		add_action( 'after_setup_theme', array( $this, 'add_gallery_supports' ), 11 );
	}

	/**
	 * Declare the WC product-gallery features the designed page relies on.
	 *
	 * Deliberately NO `wc-product-gallery-slider`: without flexslider the
	 * gallery images render as a plain stacked wrapper, which the stylesheet
	 * lays out editorially (first image full-width + 2-up grid on desktop,
	 * scroll-snap strip with dots on mobile) instead of the stock
	 * main-image-plus-thumbnails chrome. Zoom + lightbox init per image
	 * independent of the slider.
	 */
	public function add_gallery_supports() {
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
	}

	/**
	 * Swap in the designed template on single product pages.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function maybe_takeover( $template ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $template;
		}

		$file = $this->template_file();
		if ( '' === $file ) {
			return $template;
		}

		// The template renders its own accordion / upsells / related sections,
		// so WC's defaults on after_single_product_summary would double them.
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

		return $file;
	}

	/**
	 * Resolve the takeover template: theme override first, then the module
	 * copy; '' when neither is readable (falls back to the current template).
	 *
	 * @return string
	 */
	public function template_file() {
		$override = function_exists( 'locate_template' )
			? locate_template( 'freeman/product_page/single-product.php' )
			: '';
		$file     = $override ? $override : FREEMAN_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';

		return is_readable( $file ) ? $file : '';
	}

	/**
	 * The reassurance line under the add-to-cart button: shipping + returns
	 * wording from Labels, each item hidden while its label is empty, the
	 * whole line '' when both are. Pure given the options — unit-tested.
	 *
	 * @return string
	 */
	public static function trust_html() {
		$icons = array(
			// Truck (shipping).
			'trust_shipping' => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M1.5 4.5h10v9h-10zM11.5 7.5h3.6l3.4 3.2v2.8h-3" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><circle cx="5.5" cy="14.5" r="1.6" stroke="currentColor" stroke-width="1.4"/><circle cx="14.8" cy="14.5" r="1.6" stroke="currentColor" stroke-width="1.4"/></svg>',
			// Arrows (returns).
			'trust_returns'  => '<svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4.5 8.5a6 6 0 0 1 11 2.5m0-2.5v3h-3M15.5 11.5a6 6 0 0 1-11-2.5m0 2.5v-3h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
		);

		$items = '';
		foreach ( $icons as $key => $icon ) {
			$text = Labels::get( $key );
			if ( '' === trim( $text ) ) {
				continue;
			}
			$items .= '<span class="fm-pdp__trust-item">' . $icon
				. '<span>' . esc_html( $text ) . '</span></span>';
		}

		if ( '' === $items ) {
			return '';
		}

		return '<div class="fm-pdp__trust">' . $items . '</div>';
	}

	/**
	 * Scope class for the designed page.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( function_exists( 'is_product' ) && is_product() && '' !== $this->template_file() ) {
			$classes[] = 'fm-pdp-active';
		}
		return $classes;
	}

	/**
	 * Enqueue the designed-page assets on product pages only.
	 */
	public function enqueue() {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/product-page.css' ),
			array(),
			FREEMAN_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/product-page.js' ),
			array(),
			FREEMAN_CORE_VERSION,
			true
		);
	}
}
