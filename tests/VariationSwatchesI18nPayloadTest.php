<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * The ShopOSVS.i18n localize payload carries every locale-aware string the
 * swatches JS reads — including the swatch-tooltip `oos` / `unavailable`
 * wording, which previously bypassed the Labels resolver as hard-coded Hebrew
 * literals in shopos-swatches.js. Exercises the pure
 * ShopOS_VS_Frontend::i18n_payload() seam.
 *
 * @covers \ShopOS_VS_Frontend::i18n_payload
 */
final class VariationSwatchesI18nPayloadTest extends TestCase {

	private const FRONTEND_FILE = __DIR__ . '/../shopos-core/src/Modules/VariationSwatches/legacy/includes/class-frontend.php';

	public static function setUpBeforeClass(): void {
		require_once self::FRONTEND_FILE;
	}

	protected function setUp(): void {
		parent::setUp();
		unset( $GLOBALS['fr_locale'] );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['fr_locale'] );
		parent::tearDown();
	}

	public function test_payload_exposes_swatch_tooltip_keys(): void {
		$payload = \ShopOS_VS_Frontend::i18n_payload();

		$this->assertArrayHasKey( 'oos', $payload );
		$this->assertArrayHasKey( 'unavailable', $payload );
	}

	public function test_tooltip_strings_resolve_hebrew_on_hebrew_site(): void {
		$GLOBALS['fr_locale'] = 'he_IL';

		$payload = \ShopOS_VS_Frontend::i18n_payload();

		$this->assertSame( 'אזל מהמלאי', $payload['oos'] );
		$this->assertSame( 'לא זמין', $payload['unavailable'] );
	}

	public function test_tooltip_strings_resolve_english_off_hebrew_site(): void {
		$GLOBALS['fr_locale'] = 'en_US';

		$payload = \ShopOS_VS_Frontend::i18n_payload();

		$this->assertSame( 'Out of stock', $payload['oos'] );
		$this->assertSame( 'Unavailable', $payload['unavailable'] );
	}
}
