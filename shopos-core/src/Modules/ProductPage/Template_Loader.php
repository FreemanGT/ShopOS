<?php
/**
 * Product Page — designed-layout template takeover.
 *
 * Swaps the single-product template for the module's designed page via
 * `template_include` at priority 9999 — late enough to win over Elementor
 * Pro's Theme Builder single-product location (priority 12), which is what
 * renders the current product page. Module disabled, nothing registers and
 * the existing page renders untouched (the permanent rollback path).
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
 * Template is theme-overridable at `shopos/product_page/single-product.php` —
 * a public contract the ShopOS theme never ships a file at (§11.3). This loader
 * is permanent: the theme-PDP flag's off-state renderer (§11 Ruling 5).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Template loader.
 */
final class Template_Loader {

	const HANDLE = 'shopos-core-product-page';

	/**
	 * True once maybe_takeover() has returned the designed template for this
	 * request — the guard that keeps the summary-hook trust / additional-info
	 * renders scoped to the full takeover page (and out of QuickView drawers,
	 * which fire the same summary stack without going through template_include).
	 *
	 * @var bool
	 */
	private static $is_takeover = false;

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
		// gallery supports reconcile after the theme (and, at priority 99,
		// after late adders like Elementor Pro) have declared their own.
		add_action( 'after_setup_theme', array( $this, 'add_gallery_supports' ), 99 );
		// Trust line (36, after urgency 35) + Additional-information block (38,
		// before meta 40) render inside the summary stack so they sit directly
		// under the buy box on the takeover page. Both gate on is_takeover() so
		// they never leak into QuickView drawers or other summary contexts.
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_trust' ), 36 );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render_additional_information' ), 38 );
	}

	/**
	 * Reconcile the WC product-gallery theme supports for the designed page.
	 *
	 * The theme (and Elementor Pro) declare `wc-product-gallery-slider`, which
	 * boots flexslider on the gallery — its inline width/float/transform fight
	 * the editorial `display:grid` layout and leave the selected slide blank
	 * (Wave 9.2). So we actively REMOVE the slider here (priority 99, after the
	 * late adders) to keep the wrapper a plain stacked container the stylesheet
	 * lays out editorially (first image full-width + 2-up grid on desktop,
	 * scroll-snap strip with a progress bar on mobile).
	 *
	 * We also remove `wc-product-gallery-lightbox` — the owner wants no
	 * full-screen PhotoSwipe modal — and keep `wc-product-gallery-zoom` so the
	 * jQuery hover-magnify ("zoom inside") stays. `remove_theme_support` runs
	 * globally (is_product() is not resolvable at after_setup_theme), but the
	 * product gallery only renders on single-product pages, which this module
	 * takes over anyway.
	 */
	public function add_gallery_supports() {
		add_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
		remove_theme_support( 'wc-product-gallery-slider' );
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

		// With the slider support removed (add_gallery_supports), WC's
		// wc_get_gallery_image_html() silently drops every non-main gallery
		// image to the ~100px gallery_thumbnail size — stretched to full
		// width in the editorial layout they render blurry (Wave 9.3 root
		// cause). Serve the same size the main image gets.
		add_filter( 'woocommerce_gallery_image_size', array( $this, 'gallery_image_size' ) );

		self::$is_takeover = true;

		return $file;
	}

	/**
	 * Gallery image size for ALL gallery slots on the takeover page — the
	 * main image already renders at woocommerce_single; this lifts the
	 * secondary images to match (they'd otherwise fall back to the tiny
	 * gallery_thumbnail once the flexslider support is gone).
	 *
	 * @return string
	 */
	public function gallery_image_size() {
		return 'woocommerce_single';
	}

	/**
	 * Whether the designed template is driving the current request — used to
	 * scope the summary-hook trust / additional-information renders to the full
	 * takeover page.
	 *
	 * @return bool
	 */
	public static function is_takeover() {
		return self::$is_takeover;
	}

	/**
	 * Trust line under the buy box (summary priority 36). Takeover-only.
	 */
	public function render_trust() {
		if ( ! self::$is_takeover ) {
			return;
		}
		echo self::trust_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts; '' when both labels are empty.
	}

	/**
	 * Additional-information attribute table surfaced directly under the buy
	 * box (summary priority 38, before meta at 40) instead of only in the
	 * accordion below. Takeover-only, and a no-op when the product exposes no
	 * displayable attributes so an empty block never appears.
	 */
	public function render_additional_information() {
		if ( ! self::$is_takeover || ! function_exists( 'wc_display_product_attributes' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		ob_start();
		wc_display_product_attributes( $product );
		$table = trim( (string) ob_get_clean() );
		if ( '' === $table ) {
			return;
		}

		$heading = function_exists( 'apply_filters' )
			? (string) apply_filters( 'woocommerce_product_additional_information_heading', __( 'Additional information', 'shopos-core' ) )
			: __( 'Additional information', 'shopos-core' );

		echo self::additional_information_html( $heading, $table ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- heading escaped inside; table is WC-built.
	}

	/**
	 * The under-buy-box additional-information block: a collapsed <details>
	 * (owner request, Wave 9.3 — "a tab, not just open") whose summary line
	 * is a <span>, not a heading element, so an Elementor kit's global
	 * `h2 { font-size }` rule can never outrank the scoped style (the Wave
	 * 9.2 <h2> rendered kit-huge on the live store). Pure — unit-tested.
	 *
	 * @param string $heading Block heading (plain text).
	 * @param string $table   WC-built attributes table (trusted HTML).
	 * @return string
	 */
	public static function additional_information_html( $heading, $table ) {
		return '<details class="shopos-ui-pdp__addl-info">'
			. '<summary class="shopos-ui-pdp__addl-info-summary">'
			. '<span class="shopos-ui-pdp__addl-info-title">' . esc_html( $heading ) . '</span>'
			. '<svg class="shopos-ui-pdp__addl-info-chevron" width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">'
			. '<path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
			. '</svg>'
			. '</summary>'
			. '<div class="shopos-ui-pdp__addl-info-body">' . $table . '</div>'
			. '</details>';
	}

	/**
	 * Inline CSS injecting the owner-chosen button hex into the VariationSwatches
	 * buy box, scoped to the takeover page. Drives VS's own --shopos-primary
	 * custom property (which its action buttons read) plus an explicit override
	 * for the mobile sticky CTA, whose red is a hardcoded literal, not the var.
	 * Returns '' for an empty / invalid hex (keeps VS's native red). Pure —
	 * unit-tested.
	 *
	 * @param string $hex Sanitised hex colour ('' = no override).
	 * @return string
	 */
	public static function button_color_css( $hex ) {
		$hex = trim( (string) $hex );
		if ( '' === $hex || ! preg_match( '/^#[0-9a-fA-F]{3,8}$/', $hex ) ) {
			return '';
		}

		return '.shopos-ui-pdp .shopos-buy-box{--shopos-primary:' . $hex . ';--shopos-primary-hover:' . $hex . ';--shopos-primary-active:' . $hex . '}'
			. '.shopos-ui-pdp .shopos-buy-box .shopos-sticky-bar__buy,.shopos-ui-pdp .shopos-sticky-bar__buy{background:' . $hex . ' !important}';
	}

	/**
	 * Resolve the takeover template: theme override first, then the module
	 * copy; '' when neither is readable (falls back to the current template).
	 *
	 * @return string
	 */
	public function template_file() {
		$override = function_exists( 'locate_template' )
			? locate_template( 'shopos/product_page/single-product.php' )
			: '';
		$file     = $override ? $override : SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';

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
			$items .= '<span class="shopos-ui-pdp__trust-item">' . $icon
				. '<span>' . esc_html( $text ) . '</span></span>';
		}

		if ( '' === $items ) {
			return '';
		}

		return '<div class="shopos-ui-pdp__trust">' . $items . '</div>';
	}

	/**
	 * Scope class for the designed page.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function body_class( $classes ) {
		if ( function_exists( 'is_product' ) && is_product() && '' !== $this->template_file() ) {
			$classes[] = 'shopos-ui-pdp-active';
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
			SHOPOS_CORE_VERSION
		);

		$button_css = self::button_color_css( (string) $this->module->get_option( 'button_color', '' ) );
		if ( '' !== $button_css ) {
			wp_add_inline_style( self::HANDLE, $button_css );
		}

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/product-page.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
	}
}
