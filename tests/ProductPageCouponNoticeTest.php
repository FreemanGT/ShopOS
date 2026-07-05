<?php
declare(strict_types=1);

use Freeman\Core\Modules\ProductPage\Coupon_Notice;
use Freeman\Core\Modules\ProductPage\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Coupon notice pure seams: the render-qualification gate, the percent
 * math, the notice markup (string placement + escaping + the variation
 * price-map attribute), and the Labels defaults/override contract. The
 * live-coupon validation and the variation objects read are integration
 * (need WC) — live-QA.
 *
 * @covers \Freeman\Core\Modules\ProductPage\Coupon_Notice
 * @covers \Freeman\Core\Modules\ProductPage\Labels
 */
final class ProductPageCouponNoticeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	/**
	 * @dataProvider provider_should_render
	 */
	public function test_should_render( string $code, float $percent, bool $has_price, bool $expected ): void {
		$this->assertSame( $expected, Coupon_Notice::should_render( $code, $percent, $has_price ) );
	}

	public static function provider_should_render(): array {
		return array(
			'all valid'        => array( 'arba50', 50.0, true, true ),
			'no code'          => array( '', 50.0, true, false ),
			'zero percent'     => array( 'arba50', 0.0, true, false ),
			'negative percent' => array( 'arba50', -10.0, true, false ),
			'hundred percent'  => array( 'arba50', 100.0, true, false ),
			'over hundred'     => array( 'arba50', 150.0, true, false ),
			'no price'         => array( 'arba50', 50.0, false, false ),
		);
	}

	public function test_discounted_price_halves_at_fifty_percent(): void {
		$this->assertSame( 50.0, Coupon_Notice::discounted_price( 100.0, 50.0 ) );
	}

	public function test_discounted_price_quarter_off(): void {
		$this->assertSame( 75.0, Coupon_Notice::discounted_price( 100.0, 25.0 ) );
	}

	/**
	 * @dataProvider provider_discounted_price_invalid
	 */
	public function test_discounted_price_rejects_invalid_inputs( $price, $percent ): void {
		$this->assertNull( Coupon_Notice::discounted_price( $price, $percent ) );
	}

	public static function provider_discounted_price_invalid(): array {
		return array(
			'zero price'        => array( 0.0, 50.0 ),
			'negative price'    => array( -5.0, 50.0 ),
			'non-numeric price' => array( 'abc', 50.0 ),
			'zero percent'      => array( 100.0, 0.0 ),
			'full percent'      => array( 100.0, 100.0 ),
			'over percent'      => array( 100.0, 150.0 ),
		);
	}

	public function test_notice_html_places_strings_and_price(): void {
		$html = Coupon_Notice::notice_html( 'arba50', '<span class="amount">₪50</span>', 'Enter coupon code', 'and pay:', '' );

		$this->assertStringContainsString( 'fm-coupon-notice', $html );
		$this->assertStringContainsString( 'Enter coupon code', $html );
		$this->assertStringContainsString( '<strong class="fm-coupon-notice__code">arba50</strong>', $html );
		$this->assertStringContainsString( 'and pay:', $html );
		$this->assertStringContainsString( '<span class="amount">₪50</span>', $html, 'wc_price() HTML must pass through unescaped' );
		$this->assertStringNotContainsString( 'data-fm-coupon-prices', $html, 'no map attribute without a variation map' );
	}

	public function test_notice_html_escapes_code_and_labels(): void {
		$html = Coupon_Notice::notice_html( '<script>x</script>', '₪1', 'a<b', 'c>d', '' );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( 'a&lt;b', $html );
	}

	public function test_notice_html_carries_variation_price_map(): void {
		$json = (string) wp_json_encode( array( 101 => '₪25' ) );
		$html = Coupon_Notice::notice_html( 'arba50', '₪50', 'in', 'out', $json );

		$this->assertStringContainsString( 'data-fm-coupon-prices=', $html );
		$this->assertStringContainsString( 'data-fm-coupon-price>', $html, 'the price element must be JS-addressable' );
	}

	public function test_labels_fall_back_to_english_defaults(): void {
		$this->assertSame( 'Enter coupon code', Labels::get( 'coupon_intro' ) );
	}

	public function test_labels_prefer_saved_override_and_ignore_blank(): void {
		$GLOBALS['fr_opts'][ Labels::OPTION_PREFIX . 'coupon_intro' ] = 'בהקלדת קוד קופון';
		$GLOBALS['fr_opts'][ Labels::OPTION_PREFIX . 'coupon_outro' ] = '   ';

		$this->assertSame( 'בהקלדת קוד קופון', Labels::get( 'coupon_intro' ) );
		$this->assertSame( 'and the product will cost you:', Labels::get( 'coupon_outro' ), 'whitespace-only override falls back' );
	}
}
