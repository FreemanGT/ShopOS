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
	}

	/**
	 * Remove a product's row (deleted or no longer search-visible).
	 *
	 * @param int $product_id Product id.
	 */
	public function delete_product( $product_id ) {
		global $wpdb;
		$wpdb->delete( Database::table_name(), array( 'product_id' => (int) $product_id ), array( '%d' ) );
	}

	/**
	 * Products currently in the index (one row each, so COUNT(*) is the product
	 * count). Drives the admin status line.
	 *
	 * @return int
	 */
	public function count_indexed_products() {
		global $wpdb;
		$table = Database::table_name();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
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

		// MySQL has no LIMIT -1; map "unlimited" (the results-page call) to an
		// effectively-infinite cap so the prepared LIMIT clause stays valid.
		$limit = ( (int) $limit > 0 ) ? (int) $limit : 4294967295;

		$sql  = Query_Engine::search_sql( Database::table_name(), $in_stock_only );
		$args = Query_Engine::search_args( $term, $limit, array( $wpdb, 'esc_like' ) );
		$ids  = $wpdb->get_col( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array_map( 'intval', (array) $ids );
	}
}
