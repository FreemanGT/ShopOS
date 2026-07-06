<?php
/**
 * Product Page — shared per-request variations read.
 *
 * Coupon_Notice and Stock_Urgency each need the available-variations set of
 * the current variable product (the light `'objects'` read, 1.21.34
 * precedent). Before Wave 9.3 each ran its own full
 * `get_available_variations()` enumeration, so every variable-product
 * pageview paid the sweep twice — on top of the buy-box payload build. This
 * memo collapses them to one read per product per request.
 *
 * Keyed by product id: the variation set is data-derived, so two object
 * instances of the same product within one request resolve identically.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\ProductPage;

defined( 'ABSPATH' ) || exit;

/**
 * Per-request variations memo.
 */
final class Variations {

	/**
	 * Product id => raw get_available_variations( 'objects' ) result.
	 *
	 * @var array<int,array>
	 */
	private static $memo = array();

	/**
	 * The product's available variations (light objects read), memoized for
	 * the request. Callers keep their own `WC_Product_Variation` instanceof
	 * filtering — the memo stores the raw array untouched.
	 *
	 * @param object $product Variable product (anything exposing get_id() +
	 *                        get_available_variations()).
	 * @return array
	 */
	public static function objects( $product ) {
		if ( ! method_exists( $product, 'get_available_variations' ) ) {
			return array();
		}

		$id = (int) $product->get_id();
		if ( ! array_key_exists( $id, self::$memo ) ) {
			self::$memo[ $id ] = (array) $product->get_available_variations( 'objects' );
		}

		return self::$memo[ $id ];
	}

	/**
	 * Drop the memo — test isolation seam.
	 */
	public static function reset() {
		self::$memo = array();
	}
}
