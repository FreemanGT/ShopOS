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

	public function test_build_search_text_includes_variation_skus(): void {
		// Variable products keep SKUs per-variation; they must reach the blob so a
		// variation-SKU search matches the parent (the search_text LIKE fallback).
		$text = Query_Engine::build_search_text(
			'Tee',
			'PARENT-SKU',
			array(),
			array(),
			'',
			'',
			array( 'VAR-S', 'VAR-M' )
		);

		$this->assertStringContainsString( 'PARENT-SKU', $text );
		$this->assertStringContainsString( 'VAR-S', $text );
		$this->assertStringContainsString( 'VAR-M', $text );
	}

	public function test_search_sql_has_score_match_and_like_fallback(): void {
		$sql = Query_Engine::search_sql( 'wp_freeman_search_index' );

		$this->assertStringContainsString( 'MATCH(search_text) AGAINST (%s IN NATURAL LANGUAGE MODE)', $sql );
		$this->assertStringContainsString( '4 * MATCH(title) AGAINST (%s IN NATURAL LANGUAGE MODE)', $sql );
		$this->assertStringContainsString( 'WHEN sku = %s THEN 1000', $sql );
		$this->assertStringContainsString( 'WHEN sku LIKE %s THEN 50', $sql );
		// Infix boosts so an end/middle SKU term still ranks near the top: the sku
		// column (simple / parent SKUs) + the blob (variation SKUs live only there).
		// Both boosts are a %d placeholder sized by term shape in search_args().
		$this->assertStringContainsString( 'WHEN sku LIKE %s THEN %d', $sql );
		$this->assertStringContainsString( 'WHEN search_text LIKE %s THEN %d', $sql );
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
		// Marker esc so escaping is visible in the output. 'hoodie' is a plain word
		// (no digit) → the gentle WORD_INFIX_BOOST.
		$esc  = static function ( $v ) { return '[' . $v . ']'; };
		$args = Query_Engine::search_args( 'hoodie', 8, $esc );

		$this->assertCount( 13, $args );
		// Bare term in the three MATCH slots + the two exact-SKU slots.
		$this->assertSame( 'hoodie', $args[0] );
		$this->assertSame( 'hoodie', $args[1] );
		$this->assertSame( 'hoodie', $args[2] );
		$this->assertSame( 'hoodie', $args[8] );
		$this->assertSame( 'hoodie', $args[9] );
		// SKU prefix slots: escaped + trailing %.
		$this->assertSame( '[hoodie]%', $args[3] );
		$this->assertSame( '[hoodie]%', $args[10] );
		// Infix pattern slots (sku + search_text score boosts) and the WHERE
		// substring: escaped, leading + trailing %.
		$this->assertSame( '%[hoodie]%', $args[4] );
		$this->assertSame( '%[hoodie]%', $args[6] );
		$this->assertSame( '%[hoodie]%', $args[11] );
		// Each infix pattern is followed by its %d boost. A plain word gets the
		// gentle boost so customer word-search relevance is undisturbed.
		$this->assertSame( Query_Engine::WORD_INFIX_BOOST, $args[5] );
		$this->assertSame( Query_Engine::WORD_INFIX_BOOST, $args[7] );
		// LIMIT is an int.
		$this->assertSame( 8, $args[12] );
	}

	public function test_search_args_boost_is_large_for_a_sku_shaped_term(): void {
		// A SKU tail like '700-001' is what staff type; it must clear FULLTEXT's
		// token score (the '700' / '001' word-parts), so both infix boosts jump to
		// the strong SKU value.
		$esc  = static function ( $v ) { return $v; };
		$args = Query_Engine::search_args( '700-001', 8, $esc );

		$this->assertSame( Query_Engine::SKU_INFIX_BOOST, $args[5] );
		$this->assertSame( Query_Engine::SKU_INFIX_BOOST, $args[7] );
		$this->assertGreaterThan( Query_Engine::WORD_INFIX_BOOST, Query_Engine::SKU_INFIX_BOOST );
	}

	/**
	 * @dataProvider sku_like_terms
	 */
	public function test_is_sku_like( string $term, bool $expected ): void {
		$this->assertSame( $expected, Query_Engine::is_sku_like( $term ) );
	}

	public static function sku_like_terms(): array {
		return array(
			'sku tail with hyphen'   => array( '700-001', true ),
			'full sku'               => array( '1368700-001', true ),
			'alnum model'            => array( 'FQ8374', true ),
			'short digit run'        => array( '001', false ),   // < 4 chars.
			'english word'           => array( 'hoodie', false ), // no digit.
			'hebrew word'            => array( 'כחול', false ),    // no digit.
			'blank'                  => array( '', false ),
		);
	}
}
