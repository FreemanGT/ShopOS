<?php
declare(strict_types=1);

use ShopOS\Core\Modules\VariationSwatches\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Variation Swatches buy-box labels resolve by site locale: Hebrew wording on
 * a Hebrew site, English everywhere else, with admin-free deterministic
 * switching (no .mo dependency).
 *
 * @covers \ShopOS\Core\Modules\VariationSwatches\Labels
 */
final class VariationSwatchesLabelsTest extends TestCase {

	/** Every key the templates + localize payloads resolve. */
	private const KEYS = array(
		'add_to_cart', 'buy_now', 'out_of_stock', 'from_price', 'choose_option',
		'select_options', 'quantity', 'not_available', 'unavailable',
		'added_to_cart', 'error_generic', 'close', 'notices',
	);

	protected function setUp(): void {
		parent::setUp();
		unset( $GLOBALS['fr_locale'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['fr_locale'] );
		parent::tearDown();
	}

	public function test_hebrew_locale_returns_hebrew(): void {
		$GLOBALS['fr_locale'] = 'he_IL';

		$this->assertTrue( Labels::is_hebrew() );
		$this->assertSame( 'הוספה לעגלה', Labels::get( 'add_to_cart' ) );
		$this->assertSame( 'קנה עכשיו', Labels::get( 'buy_now' ) );
		$this->assertSame( 'אזל מהמלאי', Labels::get( 'out_of_stock' ) );
	}

	public function test_english_locale_returns_english(): void {
		$GLOBALS['fr_locale'] = 'en_US';

		$this->assertFalse( Labels::is_hebrew() );
		$this->assertSame( 'Add to cart', Labels::get( 'add_to_cart' ) );
		$this->assertSame( 'Buy now', Labels::get( 'buy_now' ) );
		$this->assertSame( 'Out of stock', Labels::get( 'out_of_stock' ) );
	}

	public function test_unset_locale_defaults_to_english(): void {
		// bootstrap get_locale() returns en_US when fr_locale is unset.
		$this->assertFalse( Labels::is_hebrew() );
		$this->assertSame( 'Add to cart', Labels::get( 'add_to_cart' ) );
	}

	public function test_unknown_key_returns_empty_string(): void {
		$GLOBALS['fr_locale'] = 'en_US';
		$this->assertSame( '', Labels::get( 'nope' ) );

		$GLOBALS['fr_locale'] = 'he_IL';
		$this->assertSame( '', Labels::get( 'nope' ) );
	}

	public function test_both_language_maps_cover_every_key(): void {
		foreach ( array( 'en_US', 'he_IL' ) as $locale ) {
			$GLOBALS['fr_locale'] = $locale;
			foreach ( self::KEYS as $key ) {
				$this->assertNotSame( '', Labels::get( $key ), "$locale missing $key" );
			}
		}
	}
}
