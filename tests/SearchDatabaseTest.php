<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Database;
use PHPUnit\Framework\TestCase;

/**
 * The search index-table SQL shape. Tests the pure schema_sql() builder; the
 * live install()/drop() ($wpdb + dbDelta) are integration / live QA.
 *
 * @covers \Freeman\Core\Modules\Search\Database
 */
final class SearchDatabaseTest extends TestCase {

	public function test_schema_sql_contains_columns_and_keys(): void {
		$sql = Database::schema_sql( 'wp_freeman_search_index', 'DEFAULT CHARSET=utf8mb4' );

		$this->assertStringContainsString( 'CREATE TABLE wp_freeman_search_index', $sql );
		$this->assertStringContainsString( 'product_id bigint(20) unsigned NOT NULL', $sql );
		$this->assertStringContainsString( "sku varchar(100) NOT NULL DEFAULT ''", $sql );
		$this->assertStringContainsString( 'title text NOT NULL', $sql );
		$this->assertStringContainsString( 'search_text mediumtext NOT NULL', $sql );
		$this->assertStringContainsString( 'in_stock tinyint(1) NOT NULL DEFAULT 1', $sql );
		$this->assertStringContainsString( 'PRIMARY KEY (product_id)', $sql );
		$this->assertStringContainsString( 'DEFAULT CHARSET=utf8mb4', $sql );
	}

	public function test_schema_sql_has_fulltext_indexes_and_innodb(): void {
		// The FULLTEXT indexes are the whole point — guard them, plus the explicit
		// InnoDB engine (the FULLTEXT support we rely on is InnoDB's).
		$sql = Database::schema_sql( 'wp_x', '' );

		$this->assertStringContainsString( 'FULLTEXT KEY ft_search (search_text)', $sql );
		$this->assertStringContainsString( 'FULLTEXT KEY ft_title (title)', $sql );
		$this->assertStringContainsString( 'KEY sku (sku)', $sql );
		$this->assertStringContainsString( 'ENGINE=InnoDB', $sql );
	}
}
