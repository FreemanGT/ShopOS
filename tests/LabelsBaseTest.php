<?php
declare(strict_types=1);

use ShopOS\Core\Core\Labels_Base;
use PHPUnit\Framework\TestCase;

/**
 * Concrete fixture for the abstract resolver — the exact shape a module's
 * Labels becomes on adoption (const OPTION_PREFIX + defaults()).
 */
final class _LabelsBaseFixture extends Labels_Base {

	const OPTION_PREFIX = 'shopos_core_test_label_';

	public static function defaults() {
		return array(
			'greeting' => array( 'label' => 'Greeting field', 'default' => 'Hello' ),
			'farewell' => array( 'label' => 'Farewell field', 'default' => 'Goodbye' ),
			'blankable' => array( 'label' => 'Blankable field', 'default' => '' ),
		);
	}
}

/**
 * The shared option-backed label resolver base: the byte-identical get()
 * extracted from QuickView / ShopFilters / Search / ProductPage. Verifies the
 * late-static-binding resolution (subclass OPTION_PREFIX + defaults drive it).
 *
 * @covers \ShopOS\Core\Core\Labels_Base
 */
final class LabelsBaseTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
	}

	public function test_get_returns_default_when_option_unset(): void {
		$this->assertSame( 'Hello', _LabelsBaseFixture::get( 'greeting' ) );
		$this->assertSame( 'Goodbye', _LabelsBaseFixture::get( 'farewell' ) );
	}

	public function test_get_returns_saved_override_via_subclass_prefix(): void {
		update_option( 'shopos_core_test_label_greeting', 'Shalom' );
		$this->assertSame( 'Shalom', _LabelsBaseFixture::get( 'greeting' ) );
		// Sibling key untouched.
		$this->assertSame( 'Goodbye', _LabelsBaseFixture::get( 'farewell' ) );
	}

	public function test_get_falls_back_to_default_when_saved_blank(): void {
		// The Hub can persist a whitespace-only string; that must not win.
		update_option( 'shopos_core_test_label_greeting', '   ' );
		$this->assertSame( 'Hello', _LabelsBaseFixture::get( 'greeting' ) );
	}

	public function test_get_unknown_key_returns_empty_string(): void {
		$this->assertSame( '', _LabelsBaseFixture::get( 'not_a_real_key' ) );
	}

	public function test_get_empty_default_key_returns_empty_when_unset(): void {
		// An intentionally-empty default (ProductPage trust lines) stays empty.
		$this->assertSame( '', _LabelsBaseFixture::get( 'blankable' ) );
	}
}
