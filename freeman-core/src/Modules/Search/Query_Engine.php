<?php
/**
 * Search query engine — pure, $wpdb-free seams.
 *
 * Write side (Wave 1): assembling a product's denormalised searchable-text
 * blob. Read side (Wave 2): the MATCH ... AGAINST score / where SQL builders the
 * dropdown calls via Search_Repository::search(). Both are pure ($wpdb-free) so
 * the SQL shape and the placeholder-arg ordering are unit-testable; the live
 * prepare/run is integration / live QA.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Query engine.
 */
final class Query_Engine {

	/**
	 * Build the denormalised searchable-text blob stored in `search_text`. Pure:
	 * the Indexer extracts the raw fields off the WC_Product / terms and hands
	 * them here, so the blob assembly is unit-testable without WordPress.
	 *
	 * Tags are stripped (descriptions arrive as HTML) and whitespace collapsed so
	 * FULLTEXT tokenises cleanly. No stemming / synonyms — that is AWS's moat and
	 * deliberately out of scope.
	 *
	 * @param string   $title             Product title.
	 * @param string   $sku               SKU.
	 * @param string[] $category_names    Category term names.
	 * @param string[] $tag_names         Tag term names.
	 * @param string   $short_description Short description (may contain HTML).
	 * @param string   $content           Long description (may contain HTML).
	 * @param string[] $variation_skus    Variation SKUs (variable products keep
	 *                                     SKUs per-variation, not on the parent).
	 * @return string
	 */
	public static function build_search_text( $title, $sku, array $category_names, array $tag_names, $short_description, $content, array $variation_skus = array() ) {
		$parts = array_merge(
			array( (string) $title, (string) $sku ),
			array_map( 'strval', $category_names ),
			array_map( 'strval', $tag_names ),
			array( (string) $short_description, (string) $content ),
			array_map( 'strval', $variation_skus )
		);

		$text = strip_tags( implode( ' ', $parts ) );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( (string) $text );
	}

	/* -----------------------------------------------------------------
	 * Read path (Wave 2 — the ranked search query)
	 * ----------------------------------------------------------------- */

	/**
	 * The ranked search SQL, with $wpdb->prepare placeholders. Relevance score =
	 * the broad FULLTEXT match + a 4× title-match boost + a large constant for an
	 * exact SKU (always top) + a smaller one for a SKU prefix. The WHERE OR-group
	 * pairs FULLTEXT with a SKU exact/prefix and a `search_text LIKE` substring —
	 * the fallback that rescues short / non-Latin tokens FULLTEXT's min-token-size
	 * drops. NATURAL LANGUAGE MODE: no operator syntax reaches the placeholder.
	 *
	 * Placeholder order (must match search_args()): term, term, term, sku-prefix,
	 * term, term, sku-prefix, text-substring, limit.
	 *
	 * @param string $table         Table name.
	 * @param bool   $in_stock_only Restrict to in-stock rows.
	 * @return string
	 */
	public static function search_sql( $table, $in_stock_only = false ) {
		$stock = $in_stock_only ? ' AND in_stock = 1' : '';
		return "SELECT product_id, (
				MATCH(search_text) AGAINST (%s IN NATURAL LANGUAGE MODE)
				+ 4 * MATCH(title) AGAINST (%s IN NATURAL LANGUAGE MODE)
				+ CASE WHEN sku = %s THEN 1000 ELSE 0 END
				+ CASE WHEN sku LIKE %s THEN 50 ELSE 0 END
			) AS score
			FROM {$table}
			WHERE (
				MATCH(search_text) AGAINST (%s IN NATURAL LANGUAGE MODE)
				OR sku = %s
				OR sku LIKE %s
				OR search_text LIKE %s
			){$stock}
			ORDER BY score DESC
			LIMIT %d";
	}

	/**
	 * The ordered args for search_sql()'s placeholders. esc_like is injected (the
	 * repository passes array($wpdb,'esc_like')) so this stays pure / testable.
	 *
	 * @param string   $term    Raw search term.
	 * @param int      $limit   Max rows.
	 * @param callable $esc_like LIKE-escaper, e.g. array($wpdb, 'esc_like').
	 * @return array
	 */
	public static function search_args( $term, $limit, callable $esc_like ) {
		$term   = (string) $term;
		$prefix = call_user_func( $esc_like, $term ) . '%';
		$like   = '%' . call_user_func( $esc_like, $term ) . '%';

		return array(
			$term,        // search_text MATCH (score).
			$term,        // title MATCH (score).
			$term,        // sku exact (score).
			$prefix,      // sku prefix (score).
			$term,        // search_text MATCH (where).
			$term,        // sku exact (where).
			$prefix,      // sku prefix (where).
			$like,        // search_text substring (where).
			(int) $limit, // LIMIT.
		);
	}
}
