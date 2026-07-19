<?php
/**
 * Bundle Deals — the WooCommerce cart integration.
 *
 * This is the only class in the module that touches live cart money, and the
 * first in the whole suite to do so. It turns the cart into the plain line
 * list + participation map the pure {@see Pricing} engine consumes, then
 * writes the winning per-line discounts back with `set_price()`.
 *
 * Recalc-safety: WooCommerce fires `woocommerce_before_calculate_totals`
 * several times per request, re-running our listener against the SAME product
 * objects — so reading `$cart_item['data']->get_price()` would compound the
 * discount on the second pass. Instead every base price is read from a FRESH
 * `wc_get_product()` object (catalogue/sale price, never the cart-mutated one),
 * memoised per request. The compute is therefore idempotent: pass N always
 * lands on the same prices as pass 1.
 *
 * Display: the discounted lines show the struck original + new unit price and
 * a "you save" tag, all driven off the attribution we stash on the cart item.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Cart pricing engine (integration — needs WooCommerce).
 */
final class Cart_Pricing {

	/** Cart-item key under which we stash the winning-bundle attribution. */
	const META = 'shopos_bundle';

	/**
	 * Per-request pristine base-price memo (product/variation id => float).
	 *
	 * @var array<int,float>
	 */
	private $base_memo = array();

	/**
	 * Register cart hooks.
	 */
	public function register() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply' ), 20 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 20, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_item_subtotal' ), 20, 3 );
	}

	/**
	 * Re-price the cart. Idempotent — always computed from pristine base prices.
	 *
	 * @param \WC_Cart $cart Cart.
	 */
	public function apply( $cart ) {
		if ( ! is_object( $cart ) || ! method_exists( $cart, 'get_cart' ) ) {
			return;
		}
		$items = $cart->get_cart();
		if ( empty( $items ) ) {
			return;
		}

		/**
		 * Filter the bundles evaluated for this cart calculation.
		 *
		 * @since 1.46.0
		 * @param array $bundles Active bundle definitions.
		 */
		$bundles = apply_filters( 'shopos_core/bundle_deals/active_bundles', Bundle_Config::active() );
		if ( empty( $bundles ) ) {
			return;
		}

		$lines         = $this->lines( $items );
		$participation = $this->participation( $bundles, $items );
		$discounts     = Pricing::compute( $bundles, $lines, $participation );

		foreach ( $items as $key => $cart_item ) {
			// Clear any prior attribution so a removed/expired bundle can't leave
			// a stale "you save" tag on a now-full-price line.
			unset( $cart->cart_contents[ $key ][ self::META ] );

			if ( ! isset( $discounts[ $key ] ) || ! is_object( $cart_item['data'] ) ) {
				continue;
			}

			/**
			 * Filter (or veto) a single line's resolved bundle discount.
			 *
			 * @since 1.46.0
			 * @param array $discount  { unit, bundle_id, saved }.
			 * @param array $cart_item The cart item.
			 * @param array $bundles   Active bundles.
			 */
			$discount = apply_filters( 'shopos_core/bundle_deals/apply_discount', $discounts[ $key ], $cart_item, $bundles );
			if ( empty( $discount ) || ! isset( $discount['unit'] ) ) {
				continue;
			}

			$cart_item['data']->set_price( (float) $discount['unit'] );

			$cart->cart_contents[ $key ][ self::META ] = array(
				'bundle_id' => (string) ( $discount['bundle_id'] ?? '' ),
				'saved'     => (float) ( $discount['saved'] ?? 0 ),
				'base'      => (float) $lines[ $key ]['base'],
			);
		}
	}

	/**
	 * Build the plain line list for the pricing engine.
	 *
	 * @param array $items Cart contents.
	 * @return array<string,array{product_id:int,qty:int,base:float}>
	 */
	private function lines( array $items ) {
		$lines = array();
		foreach ( $items as $key => $item ) {
			$pid = (int) ( $item['product_id'] ?? 0 );
			$vid = (int) ( $item['variation_id'] ?? 0 );
			$lines[ $key ] = array(
				'product_id' => $pid,
				'qty'        => (int) ( $item['quantity'] ?? 0 ),
				'base'       => $this->base_price( $vid > 0 ? $vid : $pid ),
			);
		}

		return $lines;
	}

	/**
	 * Map each bundle to the cart-item keys it acts on. Curated bundles act on
	 * their named product set; every other type acts on scope matches.
	 *
	 * @param array $bundles Active bundles.
	 * @param array $items   Cart contents.
	 * @return array<string,array<int,string>>
	 */
	private function participation( array $bundles, array $items ) {
		$participation = array();
		foreach ( $bundles as $bundle ) {
			$id   = (string) ( $bundle['id'] ?? '' );
			$keys = array();
			foreach ( $items as $key => $item ) {
				if ( $this->participates( $bundle, (int) ( $item['product_id'] ?? 0 ) ) ) {
					$keys[] = $key;
				}
			}
			$participation[ $id ] = $keys;
		}

		return $participation;
	}

	/**
	 * Whether a product participates in a bundle. Curated = the named set;
	 * everything else = the shared targeting scope.
	 *
	 * @param array $bundle     Bundle.
	 * @param int   $product_id Product id.
	 * @return bool
	 */
	private function participates( array $bundle, $product_id ) {
		if ( 'curated' === ( $bundle['type'] ?? '' ) ) {
			$set = array_map( 'intval', (array) ( $bundle['curated']['products'] ?? array() ) );
			return in_array( (int) $product_id, $set, true );
		}

		return Targeting::matches( $product_id, $bundle );
	}

	/**
	 * Pristine per-unit base price for a product/variation — a fresh product
	 * object so the cart's own mutated price can never feed back in. Memoised.
	 *
	 * @param int $id Product or variation id.
	 * @return float
	 */
	private function base_price( $id ) {
		$id = (int) $id;
		if ( ! isset( $this->base_memo[ $id ] ) ) {
			$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
			$price   = ( $product && is_object( $product ) ) ? $product->get_price() : 0;
			$this->base_memo[ $id ] = is_numeric( $price ) ? (float) $price : 0.0;
		}

		return $this->base_memo[ $id ];
	}

	/**
	 * Show the struck original + discounted unit price on a bundled line.
	 *
	 * @param string $price_html Default per-unit price HTML.
	 * @param array  $cart_item  Cart item.
	 * @param string $cart_key   Cart item key.
	 * @return string
	 */
	public function cart_item_price( $price_html, $cart_item, $cart_key ) {
		if ( empty( $cart_item[ self::META ] ) || ! is_object( $cart_item['data'] ) ) {
			return $price_html;
		}
		$base = (float) $cart_item[ self::META ]['base'];
		$unit = (float) $cart_item['data']->get_price();
		if ( $unit >= $base || ! function_exists( 'wc_price' ) ) {
			return $price_html;
		}

		return '<span class="shopos-ui-bundle-cart-price">'
			. '<del aria-hidden="true">' . wc_price( $base ) . '</del> '
			. '<ins>' . wc_price( $unit ) . '</ins>'
			. '</span>';
	}

	/**
	 * Append a "you save" tag to a bundled line's subtotal.
	 *
	 * @param string $subtotal_html Default subtotal HTML.
	 * @param array  $cart_item     Cart item.
	 * @param string $cart_key      Cart item key.
	 * @return string
	 */
	public function cart_item_subtotal( $subtotal_html, $cart_item, $cart_key ) {
		if ( empty( $cart_item[ self::META ] ) ) {
			return $subtotal_html;
		}
		$saved = (float) $cart_item[ self::META ]['saved'];
		if ( $saved <= 0 || ! function_exists( 'wc_price' ) ) {
			return $subtotal_html;
		}

		$tag = Display::savings_tag( wc_price( $saved ), Labels::get( 'you_save' ) );

		/**
		 * Filter the cart-line "you save" savings tag markup.
		 *
		 * @since 1.46.0
		 * @param string $tag       Savings-tag HTML.
		 * @param array  $cart_item Cart item.
		 * @param float  $saved     Amount saved on the line.
		 */
		$tag = (string) apply_filters( 'shopos_core/bundle_deals/savings_html', $tag, $cart_item, $saved );

		return $subtotal_html . $tag;
	}
}
