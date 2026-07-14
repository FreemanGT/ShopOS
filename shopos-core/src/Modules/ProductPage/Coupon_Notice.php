<?php
/**
 * Product Page — coupon-price notice.
 *
 * "Enter coupon <code> and the product will cost you: <price>" under the
 * product price. The coupon code and discount percent are module settings;
 * the two sentence halves are owner-editable Labels. The notice renders only
 * while a live WooCommerce coupon with that exact code exists (existing +
 * not expired) — deleting or expiring the coupon hides the notice with no
 * settings change (owner decision, Wave 9).
 *
 * For variable products the notice ships a per-variation discounted-price
 * map (server-rendered through wc_price(), so currency format / RTL
 * placement are exact) and the JS swaps the shown price on WooCommerce's
 * `found_variation` / `reset_data` events — unlike the original snippet,
 * which froze on the minimum price.
 *
 * Renders via `woocommerce_single_product_summary` (priority 31 — directly
 * under the buy box at 30, above urgency at 35; owner request, Wave 9.3:
 * the notice must always sit under the swatches/buy box) AND the
 * `[shopos_discounted_price]` shortcode (legacy alias `[discounted_price]`),
 * because the pre-takeover Elementor-built product page renders widgets
 * directly and never fires the summary hook stack.
 *
 * Only constructed when the coupon_notice feature flag is on (Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Coupon notice.
 */
final class Coupon_Notice {

	const HANDLE = 'shopos-core-pp-coupon';

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
	 * Register hooks + shortcodes.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 31 );
		add_shortcode( 'shopos_discounted_price', array( $this, 'shortcode' ) );
		// Legacy alias: the owner's original snippet registered this tag, so an
		// Elementor Shortcode widget already placed on the product page keeps
		// working once the snippet is removed.
		add_shortcode( 'discounted_price', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue the notice assets on product pages only.
	 */
	public function enqueue() {
		if ( is_admin() || ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/coupon-notice.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/coupon-notice.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
	}

	/**
	 * Summary-hook renderer.
	 */
	public function render() {
		echo $this->notice_for_current(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
	}

	/**
	 * Shortcode renderer.
	 *
	 * @return string
	 */
	public function shortcode() {
		if ( ! is_product() ) {
			return '';
		}
		return $this->notice_for_current();
	}

	/**
	 * Resolve the current product (the original snippet's global/get_the_ID
	 * fallback ladder) and render.
	 *
	 * @return string
	 */
	private function notice_for_current() {
		global $product;

		$candidate = $product;
		if ( ! $candidate instanceof \WC_Product && function_exists( 'wc_get_product' ) ) {
			$candidate = wc_get_product( get_the_ID() );
		}
		if ( ! $candidate instanceof \WC_Product ) {
			return '';
		}

		return $this->notice_for_product( $candidate );
	}

	/**
	 * Build the notice for a product — '' whenever anything disqualifies it
	 * (no code / invalid percent / no price / coupon missing or expired).
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function notice_for_product( $product ) {
		$code    = trim( (string) $this->module->get_option( 'coupon_code', '' ) );
		$percent = (float) $this->module->get_option( 'coupon_percent', 0 );
		$price   = $product->get_price();

		if ( ! self::should_render( $code, $percent, '' !== (string) $price ) ) {
			return '';
		}
		if ( ! $this->coupon_is_live( $code ) ) {
			return '';
		}

		/**
		 * Filters whether the coupon notice renders for a product.
		 *
		 * @since 1.22.0
		 *
		 * @param bool        $show    Whether to render the notice.
		 * @param \WC_Product $product Current product.
		 */
		if ( ! apply_filters( 'shopos_core/product_page/show_coupon_notice', true, $product ) ) {
			return '';
		}

		$display_price = function_exists( 'wc_get_price_to_display' )
			? (float) wc_get_price_to_display( $product )
			: (float) $price;

		$discounted = self::discounted_price( $display_price, $percent );
		if ( null === $discounted ) {
			return '';
		}

		$map  = $this->variation_price_map( $product, $percent );
		$html = self::notice_html(
			$code,
			wc_price( $discounted ),
			Labels::get( 'coupon_intro' ),
			Labels::get( 'coupon_outro' ),
			$map ? (string) wp_json_encode( $map ) : ''
		);

		/**
		 * Filters the rendered coupon-notice markup.
		 *
		 * @since 1.22.0
		 *
		 * @param string      $html    Notice HTML ('' = hidden).
		 * @param \WC_Product $product Current product.
		 */
		return apply_filters( 'shopos_core/product_page/coupon_notice_html', $html, $product );
	}

	/**
	 * Whether the configured state qualifies for rendering at all.
	 * Pure — unit-tested.
	 *
	 * @param string $code      Trimmed coupon code setting.
	 * @param float  $percent   Discount percent setting.
	 * @param bool   $has_price Whether the product has a price.
	 * @return bool
	 */
	public static function should_render( $code, $percent, $has_price ) {
		return '' !== $code && $percent > 0 && $percent < 100 && $has_price;
	}

	/**
	 * The price after applying the percent discount, or null when the inputs
	 * can't produce a meaningful price. Pure — unit-tested.
	 *
	 * @param float $price   Display price.
	 * @param float $percent Discount percent (exclusive 0–100).
	 * @return float|null
	 */
	public static function discounted_price( $price, $percent ) {
		if ( ! is_numeric( $price ) || (float) $price <= 0 ) {
			return null;
		}
		if ( ! is_numeric( $percent ) || (float) $percent <= 0 || (float) $percent >= 100 ) {
			return null;
		}
		return (float) $price * ( 1 - (float) $percent / 100 );
	}

	/**
	 * Notice markup. Pure — unit-tested.
	 *
	 * @param string $code        Coupon code (plain text).
	 * @param string $price_html  wc_price()-formatted discounted price (trusted HTML).
	 * @param string $intro       Intro label.
	 * @param string $outro       Outro label.
	 * @param string $prices_json Optional per-variation price-html map JSON ('' = none).
	 * @return string
	 */
	public static function notice_html( $code, $price_html, $intro, $outro, $prices_json = '' ) {
		$map_attr = '' !== $prices_json ? ' data-fm-coupon-prices="' . esc_attr( $prices_json ) . '"' : '';

		return '<div class="fm-coupon-notice"' . $map_attr . '>'
			. '<svg class="fm-coupon-notice__icon" width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">'
			. '<path d="M10.6 2.6a2 2 0 0 1 1.4-.6h4a2 2 0 0 1 2 2v4a2 2 0 0 1-.6 1.4l-7.8 7.8a2 2 0 0 1-2.8 0l-4-4a2 2 0 0 1 0-2.8l7.8-7.8Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>'
			. '<circle cx="14.5" cy="5.5" r="1.1" fill="currentColor"/>'
			. '</svg>'
			. '<p class="fm-coupon-notice__text">'
			. '<span class="fm-coupon-notice__sentence">'
			. '<span class="fm-coupon-notice__intro">' . esc_html( $intro ) . '</span> '
			. '<strong class="fm-coupon-notice__code">' . esc_html( $code ) . '</strong> '
			. '<span class="fm-coupon-notice__outro">' . esc_html( $outro ) . '</span>'
			. '</span> '
			. '<span class="fm-coupon-notice__price" data-fm-coupon-price>' . $price_html . '</span>'
			. '</p>'
			. '</div>';
	}

	/**
	 * Whether a live (existing, unexpired) WooCommerce coupon carries this
	 * code. Integration — needs WC.
	 *
	 * @param string $code Coupon code.
	 * @return bool
	 */
	private function coupon_is_live( $code ) {
		if ( ! function_exists( 'wc_get_coupon_id_by_code' ) || ! class_exists( '\WC_Coupon' ) ) {
			return false;
		}
		$coupon_id = wc_get_coupon_id_by_code( $code );
		if ( ! $coupon_id ) {
			return false;
		}
		$coupon  = new \WC_Coupon( $coupon_id );
		$expires = $coupon->get_date_expires();
		if ( $expires && $expires->getTimestamp() < time() ) {
			return false;
		}
		return true;
	}

	/**
	 * Per-variation discounted-price map (variation id => wc_price() HTML)
	 * for the JS live swap. Uses the light objects read (1.21.34 precedent) —
	 * WooCommerce's own availability filtering, none of the heavy payload
	 * assembly — via the per-request Variations memo shared with
	 * Stock_Urgency (one enumeration per product per pageview, Wave 9.3).
	 * Integration — needs WC.
	 *
	 * @param \WC_Product $product Product.
	 * @param float       $percent Discount percent.
	 * @return array<int,string>
	 */
	private function variation_price_map( $product, $percent ) {
		if ( ! $product->is_type( 'variable' ) ) {
			return array();
		}

		$map = array();
		foreach ( Variations::objects( $product ) as $variation ) {
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}
			$display    = function_exists( 'wc_get_price_to_display' )
				? (float) wc_get_price_to_display( $variation )
				: (float) $variation->get_price();
			$discounted = self::discounted_price( $display, $percent );
			if ( null === $discounted ) {
				continue;
			}
			$map[ $variation->get_id() ] = wc_price( $discounted );
		}

		return $map;
	}
}
