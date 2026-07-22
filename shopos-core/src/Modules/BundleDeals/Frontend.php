<?php
/**
 * Bundle Deals — storefront wiring for the product page.
 *
 * Renders the offer block for the current product via BOTH
 * `woocommerce_single_product_summary` (priority 25 — between the price at 10
 * and the add-to-cart at 30, verified collision-free against the documented
 * summary stack) AND a `[shopos_bundle_deals]` shortcode, so an Elementor-built
 * PDP that never fires the summary hook still shows the block (the
 * Coupon_Notice / Stock_Urgency dual-render recipe). Assets head-enqueue on
 * product pages only.
 *
 * The block is data-only markup from {@see Display}; the live cart-aware
 * progress + the frequently-bought-together "add bundle" action are driven by
 * bundle-deals.js against the {@see Ajax} endpoint.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Product-page frontend.
 */
final class Frontend {

	const HANDLE = 'shopos-core-bundle-deals';

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
	 * Register hooks + the shortcode.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), 25 );
		add_shortcode( 'shopos_bundle_deals', array( $this, 'shortcode' ) );
	}

	/**
	 * Enqueue the block assets on single-product pages.
	 */
	public function enqueue() {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		wp_enqueue_style( self::HANDLE, $this->module->asset_min_url( 'css/bundle-deals.css' ), array(), SHOPOS_CORE_VERSION );
		wp_enqueue_script( self::HANDLE, $this->module->asset_min_url( 'js/bundle-deals.js' ), array(), SHOPOS_CORE_VERSION, true );
		wp_localize_script( self::HANDLE, 'ShopOSBundleDeals', $this->localized_payload() );
	}

	/**
	 * JS payload. Extracted so PHPUnit can assert its shape.
	 *
	 * @return array<string,mixed>
	 */
	public function localized_payload() {
		return array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => Ajax::ACTION,
			'nonce'   => wp_create_nonce( Ajax::NONCE ),
			// No 'labels' key: bundle-deals.js reads only ajaxUrl/action/nonce.
			// The mix-&-match progress wording is rendered server-side
			// (Frontend::mixmatch status), so the JS payload never read it
			// (B-5 dead-code sweep).
		);
	}

	/**
	 * Summary-hook renderer.
	 */
	public function render() {
		echo $this->for_current(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Display builds escaped markup.
	}

	/**
	 * Shortcode renderer.
	 *
	 * @return string
	 */
	public function shortcode() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return '';
		}
		return $this->for_current();
	}

	/**
	 * Resolve the current product (global → get_the_ID fallback) and render its
	 * applicable bundle offers.
	 *
	 * @return string
	 */
	private function for_current() {
		global $product;

		$candidate = $product;
		if ( ! $candidate instanceof \WC_Product && function_exists( 'wc_get_product' ) ) {
			$candidate = wc_get_product( get_the_ID() );
		}
		if ( ! $candidate instanceof \WC_Product ) {
			return '';
		}

		$html = '';
		foreach ( Bundle_Config::active() as $bundle ) {
			$html .= $this->card_for( $bundle, $candidate );
		}

		return '' !== $html ? '<div class="shopos-ui-bundle-deals">' . $html . '</div>' : '';
	}

	/**
	 * Render the right card for a bundle on this product, or '' if it does not
	 * apply here. Integration — needs WC.
	 *
	 * @param array       $bundle  Bundle.
	 * @param \WC_Product $product Current product.
	 * @return string
	 */
	private function card_for( array $bundle, $product ) {
		$type = (string) ( $bundle['type'] ?? '' );
		$pid  = (int) $product->get_id();

		if ( 'curated' === $type ) {
			$set = array_map( 'intval', (array) ( $bundle['curated']['products'] ?? array() ) );
			if ( ! in_array( $pid, $set, true ) ) {
				return '';
			}
			return $this->curated_card( $bundle );
		}

		if ( ! Targeting::matches( $pid, $bundle ) ) {
			return '';
		}

		switch ( $type ) {
			case 'tiered':
				return $this->tiered_card( $bundle );
			case 'bogo':
				return $this->bogo_card( $bundle );
			case 'mixmatch':
				return $this->mixmatch_card( $bundle );
		}

		return '';
	}

	/**
	 * Tiered discount table card.
	 *
	 * @param array $bundle Bundle.
	 * @return string
	 */
	private function tiered_card( array $bundle ) {
		$rows = array();
		foreach ( (array) ( $bundle['tiers'] ?? array() ) as $tier ) {
			$rows[] = array(
				/* translators: %d: quantity threshold. */
				'label'  => sprintf( _n( '%d item', '%d+ items', (int) $tier['min'], 'shopos-core' ), (int) $tier['min'] ),
				'value'  => Labels::get( 'save' ) . ' ' . $this->discount_text( $tier['kind'], (float) $tier['amount'] ),
				'active' => false,
			);
		}
		if ( empty( $rows ) ) {
			return '';
		}

		return Display::card( 'tiered', Labels::get( 'heading_tiered' ), Display::tier_rows( $rows ) );
	}

	/**
	 * BOGO offer card.
	 *
	 * @param array $bundle Bundle.
	 * @return string
	 */
	private function bogo_card( array $bundle ) {
		$bogo = (array) ( $bundle['bogo'] ?? array() );
		$buy  = (int) ( $bogo['buy'] ?? 1 );
		$get  = (int) ( $bogo['get'] ?? 1 );
		$disc = (float) ( $bogo['discount'] ?? 100 );

		$text = $disc >= 100
			/* translators: 1: buy qty, 2: free qty. */
			? sprintf( __( 'Buy %1$d, get %2$d free', 'shopos-core' ), $buy, $get )
			/* translators: 1: buy qty, 2: discounted qty, 3: percent. */
			: sprintf( __( 'Buy %1$d, get %2$d at %3$s off', 'shopos-core' ), $buy, $get, $this->percent_text( $disc ) );

		return Display::card( 'bogo', Labels::get( 'heading_bogo' ), Display::offer_line( $text ) );
	}

	/**
	 * Mix-&-match progress card. The initial state is "add N more"; bundle-
	 * deals.js updates it live from the cart.
	 *
	 * @param array $bundle Bundle.
	 * @return string
	 */
	private function mixmatch_card( array $bundle ) {
		$mm     = (array) ( $bundle['mixmatch'] ?? array() );
		$need   = max( 1, (int) ( $mm['need'] ?? 1 ) );
		$deal   = $this->mixmatch_deal_text( $mm );
		$status = sprintf( Labels::get( 'mixmatch_remaining' ), $need );

		$attr  = ' data-shopos-bundle-mixmatch="' . esc_attr( (string) wp_json_encode( array(
			'id'   => (string) ( $bundle['id'] ?? '' ),
			'need' => $need,
		) ) ) . '"';
		$inner = ( '' !== $deal ? Display::offer_line( $deal ) : '' ) . Display::progress( 0.0, $status, false );

		return Display::card( 'mixmatch', Labels::get( 'heading_mixmatch' ), $inner, $attr );
	}

	/**
	 * Frequently-bought-together card for a curated set.
	 *
	 * @param array $bundle Bundle.
	 * @return string
	 */
	private function curated_card( array $bundle ) {
		$curated = (array) ( $bundle['curated'] ?? array() );
		$ids     = array_map( 'intval', (array) ( $curated['products'] ?? array() ) );
		if ( count( $ids ) < 2 || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$items = array();
		$total = 0.0;
		foreach ( $ids as $id ) {
			$p = wc_get_product( $id );
			if ( ! $p instanceof \WC_Product ) {
				return ''; // a missing set member disables the offer.
			}
			$total  += (float) $p->get_price();
			$items[] = array(
				'id'    => $id,
				'name'  => $p->get_name(),
				'thumb' => $p->get_image( 'woocommerce_gallery_thumbnail' ),
				'price' => wc_price( (float) $p->get_price() ),
			);
		}

		$discounted = 'fixed' === ( $curated['kind'] ?? 'percent' )
			? max( 0.0, $total - (float) $curated['amount'] )
			: $total * ( 1 - min( 100.0, (float) $curated['amount'] ) / 100 );

		$total_html = '<del aria-hidden="true">' . wc_price( $total ) . '</del> <ins>' . wc_price( $discounted ) . '</ins>';
		$data_attr  = ' data-shopos-bundle-set="' . esc_attr( (string) wp_json_encode( $ids ) ) . '"';

		return Display::card(
			'curated',
			Labels::get( 'heading_curated' ),
			Display::fbt( $items, $total_html, Labels::get( 'add_bundle' ), $data_attr )
		);
	}

	/**
	 * Human discount text for a percent-or-fixed amount. Fixed amounts render
	 * as a plain currency string (Display escapes it as text). Integration —
	 * uses wc_price().
	 *
	 * @param string $kind   'percent'|'fixed'.
	 * @param float  $amount Amount.
	 * @return string
	 */
	private function discount_text( $kind, $amount ) {
		if ( 'fixed' === $kind ) {
			return function_exists( 'wp_strip_all_tags' ) && function_exists( 'wc_price' )
				? wp_strip_all_tags( wc_price( $amount ) )
				: (string) $amount;
		}
		return $this->percent_text( $amount );
	}

	/**
	 * A trimmed percent string ("10%" not "10.00%").
	 *
	 * @param float $amount Percent.
	 * @return string
	 */
	private function percent_text( $amount ) {
		return rtrim( rtrim( number_format( (float) $amount, 2 ), '0' ), '.' ) . '%';
	}

	/**
	 * The mix-&-match deal sentence ("Any 3 for ₪99" / "Any 3, save 15%").
	 *
	 * @param array $mm Mixmatch block.
	 * @return string
	 */
	private function mixmatch_deal_text( array $mm ) {
		$need   = max( 1, (int) ( $mm['need'] ?? 1 ) );
		$kind   = (string) ( $mm['kind'] ?? 'percent' );
		$amount = (float) ( $mm['amount'] ?? 0 );

		if ( 'fixed_price' === $kind && function_exists( 'wc_price' ) && function_exists( 'wp_strip_all_tags' ) ) {
			/* translators: 1: quantity, 2: bundle price. */
			return sprintf( __( 'Any %1$d for %2$s', 'shopos-core' ), $need, wp_strip_all_tags( wc_price( $amount ) ) );
		}
		if ( 'fixed' === $kind ) {
			/* translators: 1: quantity, 2: amount off each. */
			return sprintf( __( 'Any %1$d — %2$s off each', 'shopos-core' ), $need, $this->discount_text( 'fixed', $amount ) );
		}
		/* translators: 1: quantity, 2: percent. */
		return sprintf( __( 'Any %1$d — save %2$s', 'shopos-core' ), $need, $this->percent_text( $amount ) );
	}
}
