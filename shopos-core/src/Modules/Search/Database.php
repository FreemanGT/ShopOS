<?php
/**
 * Search index table installer.
 *
 * Owns the module's single custom table: one denormalised full-text row per
 * product (title + sku + category / tag names + descriptions, collapsed into a
 * MATCH-able `search_text` blob). Distinct from the Shop Filters index, which
 * stores only taxonomy / term / stock facts — this stores searchable text.
 *
 * Installed automatically by ShopOS\Core\Core\Migrations::run() on the version
 * bump (it discovers any module's sibling Database::install()). Dropped on
 * uninstall via Module::on_uninstall(). Inert on a version downgrade.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Modules\Search;

defined( 'ABSPATH' ) || exit;

/**
 * Index table installer.
 */
final class Database {

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'shopos_search_index';
	}

	/**
	 * dbDelta-compatible CREATE TABLE statement. Split out from install() so the
	 * SQL shape is unit-testable without a live $wpdb / dbDelta.
	 *
	 * One row per product (PRIMARY KEY product_id), so reindex is a keyed upsert.
	 * Two FULLTEXT indexes — `ft_search` over the broad blob, `ft_title` for a
	 * title-relevance boost — plus a plain `sku` key so exact / prefix SKU
	 * lookups never depend on FULLTEXT's min-token-size floor. InnoDB is explicit
	 * because the FULLTEXT support we rely on is InnoDB's (MySQL 5.6+).
	 *
	 * @param string $table   Table name.
	 * @param string $charset Charset/collation clause (from $wpdb->get_charset_collate()).
	 * @return string
	 */
	public static function schema_sql( $table, $charset = '' ) {
		return "CREATE TABLE {$table} (
			product_id bigint(20) unsigned NOT NULL,
			sku varchar(100) NOT NULL DEFAULT '',
			title text NOT NULL,
			search_text mediumtext NOT NULL,
			in_stock tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (product_id),
			KEY sku (sku),
			KEY in_stock (in_stock),
			FULLTEXT KEY ft_search (search_text),
			FULLTEXT KEY ft_title (title)
		) {$charset} ENGINE=InnoDB;";
	}

	/**
	 * Create / upgrade the table. Idempotent (dbDelta).
	 *
	 * ponytail: plain dbDelta, like the Shop Filters table. dbDelta can be fussy
	 * about FULLTEXT KEY lines and may try to re-add them each run; if that shows
	 * up in practice, guard a raw `ALTER TABLE ... ADD FULLTEXT` behind a
	 * `SHOW INDEX` existence check. Not pre-built — upgrade only if observed.
	 */
	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::schema_sql( self::table_name(), $wpdb->get_charset_collate() ) );
	}

	/**
	 * Drop the table. Called on uninstall — the data is a pure derived cache
	 * with no user value, so we drop rather than keep.
	 */
	public static function drop() {
		global $wpdb;
		$table = self::table_name();
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
	}
}
