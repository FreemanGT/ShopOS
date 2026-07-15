<?php
declare(strict_types=1);

use ShopOS\Core\Core\Elementor\Widget_Base;
use PHPUnit\Framework\TestCase;

/**
 * The shared Elementor Widget_Base — the pure setting-coercion + environment
 * helpers extracted from the Category/Product slider widgets (Phase 1). These
 * must behave byte-identically to the per-widget copies they replaced.
 *
 * @covers \ShopOS\Core\Core\Elementor\Widget_Base
 */
final class WidgetBaseTest extends TestCase {

	/**
	 * A minimal concrete widget exposing the protected helpers for assertion.
	 * The abstract base declares no abstract methods, so the only additions are
	 * the public test accessors.
	 */
	private function widget(): Widget_Base {
		return new class() extends Widget_Base {
			public function get_name() {
				return 'shopos_test_widget';
			}
			public function get_title() {
				return 'Test';
			}
			public function pub_slider_int( $raw, $default ) {
				return $this->slider_int( $raw, $default );
			}
			public function pub_slider_float( $raw, $default ) {
				return $this->slider_float( $raw, $default );
			}
			public function pub_resolve_direction( $setting ) {
				return $this->resolve_direction( $setting );
			}
		};
	}

	public function test_get_categories_returns_the_shared_default(): void {
		$this->assertSame(
			array( 'shopos', 'woocommerce-elements', 'general' ),
			$this->widget()->get_categories()
		);
	}

	/**
	 * @dataProvider slider_int_cases
	 */
	public function test_slider_int( $raw, int $default, int $expected ): void {
		$this->assertSame( $expected, $this->widget()->pub_slider_int( $raw, $default ) );
	}

	public static function slider_int_cases(): array {
		return array(
			'elementor slider shape (int size)'    => array( array( 'size' => 5 ), 3, 5 ),
			'elementor slider shape (string size)' => array( array( 'size' => '5' ), 3, 5 ),
			'legacy scalar int'                    => array( 7, 3, 7 ),
			'legacy scalar string'                 => array( '7', 3, 7 ),
			'zero scalar is kept, not defaulted'   => array( 0, 3, 0 ),
			'empty size falls back to default'     => array( array( 'size' => '' ), 3, 3 ),
			'empty string falls back to default'   => array( '', 3, 3 ),
			'null falls back to default'           => array( null, 3, 3 ),
			'array without size key defaults'      => array( array( 'unit' => 'px' ), 3, 3 ),
		);
	}

	/**
	 * @dataProvider slider_float_cases
	 */
	public function test_slider_float( $raw, float $default, float $expected ): void {
		$this->assertSame( $expected, $this->widget()->pub_slider_float( $raw, $default ) );
	}

	public static function slider_float_cases(): array {
		return array(
			'fractional slider shape'            => array( array( 'size' => 1.4 ), 1.0, 1.4 ),
			'fractional string size'             => array( array( 'size' => '1.4' ), 1.0, 1.4 ),
			'legacy scalar float'                => array( 2.5, 1.0, 2.5 ),
			'empty size falls back to default'   => array( array( 'size' => '' ), 1.0, 1.0 ),
			'empty string falls back to default' => array( '', 1.0, 1.0 ),
			'null falls back to default'         => array( null, 1.0, 1.0 ),
		);
	}

	public function test_resolve_direction_explicit_wins_over_locale(): void {
		$w = $this->widget();
		$this->assertSame( 'rtl', $w->pub_resolve_direction( 'rtl' ) );
		$this->assertSame( 'ltr', $w->pub_resolve_direction( 'ltr' ) );
	}

	public function test_resolve_direction_auto_follows_site_locale(): void {
		$w = $this->widget();

		$GLOBALS['fr_locale'] = 'he_IL';
		$this->assertSame( 'rtl', $w->pub_resolve_direction( 'auto' ) );

		$GLOBALS['fr_locale'] = 'en_US';
		$this->assertSame( 'ltr', $w->pub_resolve_direction( 'auto' ) );

		// Any value that isn't the literal 'ltr'/'rtl' resolves via locale.
		$this->assertSame( 'ltr', $w->pub_resolve_direction( '' ) );
	}

	protected function tearDown(): void {
		unset( $GLOBALS['fr_locale'] );
		parent::tearDown();
	}
}
