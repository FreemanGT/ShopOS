<?php
/**
 * Thin $wpdb wrapper over the search index table.
 *
 * Write path (Wave 1): upsert / delete / count / clear, used by the Indexer and
 * the admin reindex tool. Read path (Wave 2): the ranked MATCH ... AGAINST
 * search(). $wpdb-touching, so exercised by integration / live QA; the pure SQL
 * algebra it composes lives in Query_Engine and is unit-tested.
 *
 * @package FreemanCore
 */

namespace Freeman\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Search index repository.
 */
final class Search_Repository {

	/**
	 * Hard ceiling for one search() read. Bounds the results-page / facet-feed
	 * "unlimited" (-1) calls: Hebrew terms match via the search_text LIKE
	 * fallback (below the FULLTEXT token size), so a broad term can match a
	 * large share of the catalogue — uncapped, those ids get hydrated into
	 * WC_Product objects downstream and exhaust the request's memory.
	 */
	const MAX_RESULTS = 500;

	/**
	 * Per-request memo for search() results (term|limit|stock => ids). One
	 * results-page request runs the same term through the main-query
	 * constraint, the ProductSlider constraint and the Shop Filters facet
	 * feed; the memo collapses those to a single ranked query.
	 *
	 * @var array<string,int[]>
	 */
	private static $search_memo = array();

	/**
	 * Per-request memo for the index row count (has_data() gates run on hot
	 * paths and must not re-COUNT per WP_Query).
	 *
	 * @var int|null
	 */
	private static $count_memo = null;

	/**
	 * Drop the per-request memos. Called from every index write below so a
	 * write-then-read request never serves stale results; also used in tests.
	 */
	public static function reset_runtime_cache() {
		self::$search_memo = array();
		self::$count_memo  = null;
	}

	/**
	 * The LIMIT actually sent to MySQL. MySQL has no LIMIT -1, and the
	 * "unlimited" results-page call must not be (the pre-1.21.19 4294967295
	 * mapping is what let one broad search hydrate the whole catalogue), so
	 * non-positive AND oversized limits both clamp to MAX_RESULTS.
	 *
	 * @param int $limit Requested limit (<=0 means "no limit").
	 * @return int
	 */
	public static function effective_limit( $limit ) {
		$limit = (int) $limit;
		/** Filter the hard cap on search results returned in one query. @since 1.21.40 */
		$cap = (int) apply_filters( 'freeman_core/search/max_results', self::MAX_RESULTS );
		return ( $limit > 0 ) ? min( $limit, $cap ) : $cap;
	}

	/**
	 * Insert or replace a product's single index row. REPLACE keyed on the
	 * product_id primary key, so a reindex is one statement with no stale rows.
	 *
	 * @param int    $product_id  Product id.
	 * @param string $sku         SKU.
	 * @param string $title       Product title.
	 * @param string $search_text Denormalised searchable blob.
	 * @param int    $in_stock    1/0 overall stock.
	 */
	public function upsert( $product_id, $sku, $title, $search_text, $in_stock ) {
		global $wpdb;
		$wpdb->replace(
			Database::table_name(),
			array(
				'product_id'  => (int) $product_id,
				'sku'         => (string) $sku,
				'title'       => (string) $title,
				'search_text' => (string) $search_text,
				'in_stock'    => empty( $in_stock ) ? 0 : 1,
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);
		self::reset_runtime_cache();
	}

	/**
	 * Remove a product's row (deleted or no longer search-visible).
	 *
	 * @param int $product_id Product id.
	 */
	public function delete_product( $product_id ) {
		global $wpdb;
		$wpdb->delete( Database::table_name(), array( 'product_id' => (int) $product_id ), array( '%d' ) );
		self::reset_runtime_cache();
	}

	/**
	 * Products currently in the index (one row each, so COUNT(*) is the product
	 * count). Drives the admin status line.
	 *
	 * @return int
	 */
	public function count_indexed_products() {
		if ( null !== self::$count_memo ) {
			return self::$count_memo;
		}
		global $wpdb;
		$table            = Database::table_name();
		self::$count_memo = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return self::$count_memo;
	}

	/**
	 * Whether the index has any rows. The Wave 3 results page uses this to decide
	 * between driving the search grid from the engine and falling back to native
	 * WP search (so a site that enabled the results flag before ever reindexing
	 * isn't left with a broken search).
	 *
	 * @return bool
	 */
	public function has_data() {
		return $this->count_indexed_products() > 0;
	}

	/**
	 * Empty the whole index (used before a full rebuild).
	 */
	public function clear_all() {
		global $wpdb;
		$table = Database::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		self::reset_runtime_cache();
	}

	/* -----------------------------------------------------------------
	 * Read path (Wave 2 — the dropdown query)
	 * ----------------------------------------------------------------- */

	/**
	 * Ranked product ids for a search term, best match first. Returns an empty
	 * array for a blank term or no matches.
	 *
	 * ponytail: empty array on no-match is right for the dropdown (it just shows
	 * "no results"). The null-on-empty-index distinction — so the Wave 3 results
	 * page can fall back to native WP search when the index isn't built yet —
	 * lands in Wave 3 where that fallback consumer exists.
	 *
	 * @param string $term          Search term.
	 * @param int    $limit         Max results.
	 * @param bool   $in_stock_only Restrict to in-stock rows.
	 * @return int[]
	 */
	public function search( $term, $limit = 10, $in_stock_only = false ) {
		global $wpdb;
		$term = trim( (string) $term );
		if ( '' === $term ) {
			return array();
		}

		$limit = self::effective_limit( $limit );

		$key = $term . '|' . $limit . '|' . ( $in_stock_only ? '1' : '0' );
		if ( isset( self::$search_memo[ $key ] ) ) {
			return self::$search_memo[ $key ];
		}

		$sql  = Query_Engine::search_sql( Database::table_name(), $in_stock_only );
		$args = Query_Engine::search_args( $term, $limit, array( $wpdb, 'esc_like' ) );
		$ids  = $wpdb->get_col( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		self::$search_memo[ $key ] = array_map( 'intval', (array) $ids );
		return self::$search_memo[ $key ];
	}
}
