<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Query_Engine;
use PHPUnit\Framework\TestCase;

/**
 * The pure searchable-text blob builder. The MATCH ... AGAINST read query is
 * Wave 2 and lives in Search_Repository (integration / live QA).
 *
 * @covers \Freeman\Core\Modules\Search\Query_Engine
 */
final class SearchQueryEngineTest extends TestCase {

	public function test_build_search_text_joins_all_fields(): void {
		$text = Query_Engine::build_search_text(
			'Blue Hoodie',
			'SKU-123',
			array( 'Hoodies', 'Tops' ),
			array( 'winter', 'sale' ),
			'Warm and cosy',
			'A long description.'
		);

		foreach ( array( 'Blue Hoodie', 'SKU-123', 'Hoodies', 'Tops', 'winter', 'sale', 'Warm and cosy', 'A long description.' ) as $needle ) {
			$this->assertStringContainsString( $needle, $text );
		}
	}

	public function test_build_search_text_strips_html_and_collapses_whitespace(): void {
		$text = Query_Engine::build_search_text(
			'Shirt',
			'',
			array(),
			array(),
			"<p>Soft   cotton</p>\n\n<strong>tee</strong>",
			''
		);

		$this->assertStringNotContainsString( '<', $text );
		$this->assertStringNotContainsString( '  ', $text, 'runs of whitespace must collapse to one space' );
		$this->assertStringContainsString( 'Soft cotton', $text );
		$this->assertStringContainsString( 'tee', $text );
	}

	public function test_build_search_text_trims_and_handles_empty(): void {
		$this->assertSame( '', Query_Engine::build_search_text( '', '', array(), array(), '', '' ) );
		$this->assertSame( 'Just A Title', Query_Engine::build_search_text( '  Just A Title  ', '', array(), array(), '', '' ) );
	}

	public function test_search_sql_has_score_match_and_like_fallback(): void {
		$sql = Query_Engine::search_sql( 'wp_freeman_search_index' );

		$this->assertStringContainsString( 'MATCH(search_text) AGAINST (%s IN NATURAL LANGUAGE MODE)', $sql );
		$this->assertStringContainsString( '4 * MATCH(title) AGAINST (%s IN NATURAL LANGUAGE MODE)', $sql );
		$this->assertStringContainsString( 'WHEN sku = %s THEN 1000', $sql );
		$this->assertStringContainsString( 'WHEN sku LIKE %s THEN 50', $sql );
		// The LIKE substring fallback that rescues short / non-Latin tokens.
		$this->assertStringContainsString( 'OR search_text LIKE %s', $sql );
		$this->assertStringContainsString( 'FROM wp_freeman_search_index', $sql );
		$this->assertStringContainsString( 'ORDER BY score DESC', $sql );
		$this->assertStringContainsString( 'LIMIT %d', $sql );
	}

	public function test_search_sql_in_stock_clause_is_optional(): void {
		$this->assertStringNotContainsString( 'in_stock = 1', Query_Engine::search_sql( 'wp_x', false ) );
		$this->assertStringContainsString( 'AND in_stock = 1', Query_Engine::search_sql( 'wp_x', true ) );
	}

	public function test_search_args_order_and_escaping(): void {
		// Marker esc so escaping is visible in the output.
		$esc  = static function ( $v ) { return '[' . $v . ']'; };
		$args = Query_Engine::search_args( 'hoodie', 8, $esc );

		$this->assertCount( 9, $args );
		// Bare term in the three MATCH slots + the two exact-SKU slots.
		$this->assertSame( 'hoodie', $args[0] );
		$this->assertSame( 'hoodie', $args[1] );
		$this->assertSame( 'hoodie', $args[2] );
		$this->assertSame( 'hoodie', $args[4] );
		$this->assertSame( 'hoodie', $args[5] );
		// SKU prefix slots: escaped + trailing %.
		$this->assertSame( '[hoodie]%', $args[3] );
		$this->assertSame( '[hoodie]%', $args[6] );
		// search_text substring: leading + trailing %.
		$this->assertSame( '%[hoodie]%', $args[7] );
		// LIMIT is an int.
		$this->assertSame( 8, $args[8] );
	}
}
