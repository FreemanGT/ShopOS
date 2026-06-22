<?php
/**
 * Thin $wpdb wrapper over the search index table.
 *
 * Wave 1 is the write path only (upsert / delete / count / clear) used by the
 * Indexer and the admin reindex tool. The ranked MATCH ... AGAINST read query
 * arrives in Wave 2. $wpdb-touching by nature, so exercised by integration /
 * live QA rather than unit tests.
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
	 * Empty the whole index (used before a full rebuild).
	 */
	public function clear_all() {
		global $wpdb;
		$table = Database::table_name();
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}
}
