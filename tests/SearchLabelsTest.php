<?php
declare(strict_types=1);

use ShopOS\Core\Modules\Search\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Search storefront label resolver — the defaults map plus the saved-override /
 * blank-fallback resolution (QuickView / ShopFilters Labels precedent).
 *
 * @covers \ShopOS\Core\Modules\Search\Labels
 */
final class SearchLabelsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
	}

	public function test_defaults_map_exposes_every_storefront_string(): void {
		$defaults = Labels::defaults();

		$this->assertSame(
			array( 'placeholder', 'button', 'no_results', 'see_all', 'searching', 'toggle', 'close' ),
			array_keys( $defaults )
		);
		$this->assertSame( 'Search products…', $defaults['placeholder']['default'] );
		$this->assertSame( 'No products found', $defaults['no_results']['default'] );
		// Every entry carries an admin field label + a non-empty default.
		foreach ( $defaults as $key => $def ) {
			$this->assertNotSame( '', trim( $def['label'] ), "{$key} needs an admin label" );
			$this->assertNotSame( '', trim( $def['default'] ), "{$key} needs a default" );
		}
	}

	public function test_get_returns_saved_override(): void {
		update_option( 'shopos_core_search_label_no_results', 'לא נמצאו מוצרים' );
		$this->assertSame( 'לא נמצאו מוצרים', Labels::get( 'no_results' ) );
	}

	public function test_get_falls_back_to_default_when_blank_or_unset(): void {
		// Unset → default.
		$this->assertSame( 'Searching…', Labels::get( 'searching' ) );

		// Saved-but-blank → still the default (the Hub can persist an empty string).
		update_option( 'shopos_core_search_label_searching', '   ' );
		$this->assertSame( 'Searching…', Labels::get( 'searching' ) );
	}

	public function test_get_unknown_key_returns_empty_string(): void {
		$this->assertSame( '', Labels::get( 'not_a_real_key' ) );
	}
}
