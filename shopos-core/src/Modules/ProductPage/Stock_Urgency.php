<?php
/**
 * Product Page — low-stock urgency badge.
 *
 * Scarcity badge for variable products: when the shopper picks a variation
 * whose managed stock sits between 1 and the configured ceiling, a badge
 * appears ("Last one in stock" / "Only {count} left in stock" — both
 * owner-editable Labels). Hidden while nothing is picked and on variations
 * outside the band, mirroring the original snippet's `found_variation` /
 * `reset_data` behaviour.
 *
 * Improvements over the snippet it productizes: CSS/JS live in enqueued
 * assets instead of inline tags, the payload travels on a data attribute via
 * wp_json_encode(), the palette routes through the `--fm-*` theme tokens,
 * and typography inherits the page font (the hardcoded "Ploni" stack is
 * gone — owner request).
 *
 * Renders via `woocommerce_single_product_summary` (priority 35 — after
 * WC's add-to-cart at 30, before meta at 40) AND the
 * `[shopos_stock_urgency]` shortcode (legacy alias `[stock_urgency]`),
 * because the pre-takeover Elementor-built product page renders widgets
 * directly and never fires the summary hook stack.
 *
 * Only constructed when the stock_urgency feature flag is on (Module::boot()).
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Stock urgency badge.
 */
final class Stock_Urgency {

	const HANDLE = 'shopos-core-pp-urgency';

	/**
	 * Fallback ceiling when the setting is unset/invalid — the original
	 * snippet's hardcoded 2–5 band.
	 */
	const DEFAULT_MAX = 5;

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
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 35 );
		add_shortcode( 'shopos_stock_urgency', array( $this, 'shortcode' ) );
		// Legacy alias: the owner's original snippet registered this tag, so an
		// Elementor Shortcode widget already placed on the product page keeps
		// working once the snippet is removed.
		add_shortcode( 'stock_urgency', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue the badge assets on product pages only.
	 */
	public function enqueue() {
		if ( is_admin() || ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			self::HANDLE,
			$this->module->asset_min_url( 'css/stock-urgency.css' ),
			array(),
			SHOPOS_CORE_VERSION
		);

		wp_enqueue_script(
			self::HANDLE,
			$this->module->asset_min_url( 'js/stock-urgency.js' ),
			array(),
			SHOPOS_CORE_VERSION,
			true
		);
	}

	/**
	 * Summary-hook renderer.
	 */
	public function render() {
		echo $this->badge_for_current(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts.
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
		return $this->badge_for_current();
	}

	/**
	 * Resolve the current product and render.
	 *
	 * @return string
	 */
	private function badge_for_current() {
		global $product;

		$candidate = $product;
		if ( ! $candidate instanceof \WC_Product && function_exists( 'wc_get_product' ) ) {
			$candidate = wc_get_product( get_the_ID() );
		}
		if ( ! $candidate instanceof \WC_Product || ! $candidate->is_type( 'variable' ) ) {
			return '';
		}

		return $this->badge_for_product( $candidate );
	}

	/**
	 * Build the badge shell (hidden until JS reveals it on found_variation)
	 * for a variable product — '' when no variation is inside the band.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return string
	 */
	public function badge_for_product( $product ) {
		/**
		 * Filters whether the stock-urgency badge renders for a product.
		 *
		 * @since 1.22.0
		 *
		 * @param bool        $show    Whether to render the badge.
		 * @param \WC_Product $product Current product.
		 */
		if ( ! apply_filters( 'shopos_core/product_page/show_stock_urgency', true, $product ) ) {
			return '';
		}

		$messages = $this->messages_for_product( $product );

		/**
		 * Filters the per-variation urgency messages (variation id => text).
		 *
		 * @since 1.22.0
		 *
		 * @param array<int,string> $messages Message map.
		 * @param \WC_Product       $product  Current product.
		 */
		$messages = (array) apply_filters( 'shopos_core/product_page/urgency_messages', $messages, $product );

		if ( empty( $messages ) ) {
			return '';
		}

		return self::badge_html( (string) wp_json_encode( $messages ) );
	}

	/**
	 * Per-variation urgency map from live variation objects. Uses the light
	 * objects read (1.21.34 precedent) via the per-request Variations memo
	 * shared with Coupon_Notice (one enumeration per product per pageview,
	 * Wave 9.3). Integration — needs WC.
	 *
	 * @param \WC_Product $product Variable product.
	 * @return array<int,string>
	 */
	private function messages_for_product( $product ) {
		$rows = array();
		foreach ( Variations::objects( $product ) as $variation ) {
			if ( ! $variation instanceof \WC_Product_Variation ) {
				continue;
			}
			$rows[] = array(
				'id'       => $variation->get_id(),
				'managing' => (bool) $variation->managing_stock(),
				'qty'      => $variation->get_stock_quantity(),
			);
		}

		return self::messages(
			$rows,
			$this->max_units(),
			Labels::get( 'urgency_last_unit' ),
			Labels::get( 'urgency_units_left' )
		);
	}

	/**
	 * The configured band ceiling, clamped to sane bounds.
	 *
	 * @return int
	 */
	public function max_units() {
		$max = (int) $this->module->get_option( 'urgency_max', self::DEFAULT_MAX );
		return $max >= 1 ? $max : self::DEFAULT_MAX;
	}

	/**
	 * Map variation stock rows to urgency messages. Pure — unit-tested.
	 *
	 * A row only produces a message when stock is managed and the quantity
	 * sits in [1, $max]: 1 => $last_unit, 2..$max => $units_left with
	 * `{count}` replaced.
	 *
	 * @param array<int,array{id:int,managing:bool,qty:int|null}> $rows       Variation stock rows.
	 * @param int                                                 $max        Band ceiling (inclusive).
	 * @param string                                              $last_unit  Quantity-1 wording.
	 * @param string                                              $units_left Few-left wording with {count}.
	 * @return array<int,string>
	 */
	public static function messages( array $rows, $max, $last_unit, $units_left ) {
		$messages = array();

		foreach ( $rows as $row ) {
			if ( empty( $row['managing'] ) || ! isset( $row['qty'] ) || ! is_numeric( $row['qty'] ) ) {
				continue;
			}
			$qty = (int) $row['qty'];
			$id  = (int) ( $row['id'] ?? 0 );
			if ( $id <= 0 || $qty < 1 || $qty > (int) $max ) {
				continue;
			}
			$messages[ $id ] = 1 === $qty
				? (string) $last_unit
				: str_replace( '{count}', (string) $qty, (string) $units_left );
		}

		return $messages;
	}

	/**
	 * Badge shell markup — hidden by default; JS fills the text and reveals
	 * it when the picked variation has a message. Pure — unit-tested.
	 *
	 * @param string $messages_json Variation-id => text map JSON.
	 * @return string
	 */
	public static function badge_html( $messages_json ) {
		return '<div class="fm-stock-urgency" data-fm-urgency="' . esc_attr( $messages_json ) . '" hidden>'
			. '<svg class="fm-stock-urgency__icon" width="14" height="16" viewBox="0 0 14 16" fill="none" aria-hidden="true">'
			. '<path d="M7.6.9c.3 2.3-.6 3.6-1.7 4.9C4.7 7.2 3 8.6 3 11a4.5 4.5 0 0 0 9 0c0-1.5-.6-2.8-1.4-3.9-.3.5-.8 1-1.3 1.3.2-1.9-.4-4.6-1.7-6.3A6.4 6.4 0 0 0 7.6.9Z" fill="currentColor"/>'
			. '</svg>'
			. '<span class="fm-stock-urgency__text" data-fm-urgency-text aria-live="polite"></span>'
			. '</div>';
	}
}
