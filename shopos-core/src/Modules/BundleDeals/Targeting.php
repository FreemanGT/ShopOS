<?php
/**
 * Bundle Deals — "does this product qualify for this bundle" resolver.
 *
 * A bundle's `scope` names the products it applies to by product id,
 * `product_cat` id and `product_tag` id, minus an `exclude_categories` list
 * (the VariationSwatches archive-scope + excluded-category model). This class
 * splits the decision into a pure set-logic seam (`matches_terms()`, fully
 * unit-tested with an injected term map) and a thin WC wrapper (`matches()`)
 * that resolves a product's real category/tag membership via `get_the_terms`.
 *
 * An empty scope matches NOTHING — a bundle with no target does nothing, which
 * is the safe default for a half-built draft.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\BundleDeals;

defined( 'ABSPATH' ) || exit;

/**
 * Bundle targeting resolver.
 */
final class Targeting {

	/**
	 * Whether a product matches a bundle's scope, resolving the product's live
	 * taxonomy membership. Thin wrapper over the pure seam.
	 *
	 * @param int   $product_id Product id (variation callers pass the parent).
	 * @param array $bundle     Sanitised bundle.
	 * @return bool
	 */
	public static function matches( $product_id, array $bundle ) {
		$scope = isset( $bundle['scope'] ) && is_array( $bundle['scope'] ) ? $bundle['scope'] : array();

		return self::matches_terms(
			(int) $product_id,
			self::term_ids( (int) $product_id, 'product_cat' ),
			self::term_ids( (int) $product_id, 'product_tag' ),
			$scope
		);
	}

	/**
	 * Pure scope test. A product matches when it is named directly, OR sits in
	 * a targeted category/tag — AND is not in an excluded category. Empty scope
	 * (no products, categories or tags) matches nothing.
	 *
	 * @param int   $product_id Product id.
	 * @param int[] $cat_ids    The product's product_cat term ids.
	 * @param int[] $tag_ids    The product's product_tag term ids.
	 * @param array $scope      { products, categories, tags, exclude_categories }.
	 * @return bool
	 */
	public static function matches_terms( $product_id, array $cat_ids, array $tag_ids, array $scope ) {
		$products = self::ids( $scope['products'] ?? array() );
		$cats     = self::ids( $scope['categories'] ?? array() );
		$tags     = self::ids( $scope['tags'] ?? array() );
		$exclude  = self::ids( $scope['exclude_categories'] ?? array() );

		if ( empty( $products ) && empty( $cats ) && empty( $tags ) ) {
			return false;
		}

		if ( array_intersect( $cat_ids, $exclude ) ) {
			return false;
		}

		if ( in_array( (int) $product_id, $products, true ) ) {
			return true;
		}
		if ( array_intersect( $cat_ids, $cats ) ) {
			return true;
		}
		if ( array_intersect( $tag_ids, $tags ) ) {
			return true;
		}

		return false;
	}

	/**
	 * A product's term ids for a taxonomy. Integration — needs WP/WC.
	 *
	 * @param int    $product_id Product id.
	 * @param string $taxonomy   Taxonomy.
	 * @return int[]
	 */
	private static function term_ids( $product_id, $taxonomy ) {
		if ( ! function_exists( 'get_the_terms' ) ) {
			return array();
		}
		$terms = get_the_terms( $product_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}

		return array_map(
			static function ( $t ) {
				return (int) ( is_object( $t ) ? $t->term_id : $t );
			},
			$terms
		);
	}

	/**
	 * Coerce a raw id list to unique ints. Pure.
	 *
	 * @param mixed $ids Raw.
	 * @return int[]
	 */
	private static function ids( $ids ) {
		if ( ! is_array( $ids ) ) {
			return array();
		}
		return array_values( array_unique( array_map( 'intval', $ids ) ) );
	}
}
