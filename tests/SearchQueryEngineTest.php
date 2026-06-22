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
}
