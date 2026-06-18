<?php
declare(strict_types=1);

use Freeman\Core\Modules\HoverSwap\Gallery_Slider;
use Freeman\Core\Modules\HoverSwap\Module;
use PHPUnit\Framework\TestCase;

// A product with a primary image id + a settable gallery list, instanceof
// WC_Product regardless of which base stub another test file defined first.
if ( ! class_exists( '\\WC_Product' ) ) {
	eval( 'class WC_Product {}' );
}
if ( ! class_exists( 'FR_CardSlider_Test_Product' ) ) {
	class FR_CardSlider_Test_Product extends \WC_Product {
		private $primary;
		private $gallery;
		public function __construct( int $primary = 0, array $gallery = array() ) {
			$this->primary = $primary;
			$this->gallery = $gallery;
		}
		public function get_image_id() {
			return $this->primary;
		}
		public function get_gallery_image_ids() {
			return $this->gallery;
		}
	}
}

if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attr = array() ) {
		$class = is_array( $attr ) && isset( $attr['class'] ) ? $attr['class'] : '';
		return '<img data-id="' . (int) $id . '" class="' . $class . '" />';
	}
}

/**
 * Gallery-slider seams: slide-image selection (primary first, deduped) and the
 * slider markup — single-image fallback and the hover-arrows toggle.
 *
 * @covers \Freeman\Core\Modules\HoverSwap\Gallery_Slider
 */
final class HoverSwapGallerySliderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset( $GLOBALS['product'] );
	}

	private function slider(): Gallery_Slider {
		return new Gallery_Slider( new Module() );
	}

	public function test_slide_image_ids_primary_first_then_gallery(): void {
		$product = new FR_CardSlider_Test_Product( 10, array( 11, 12 ) );

		$this->assertSame( array( 10, 11, 12 ), $this->slider()->slide_image_ids( $product ) );
	}

	public function test_slide_image_ids_skips_missing_primary(): void {
		$product = new FR_CardSlider_Test_Product( 0, array( 11 ) );

		$this->assertSame( array( 11 ), $this->slider()->slide_image_ids( $product ) );
	}

	public function test_slide_image_ids_dedupes_primary_in_gallery(): void {
		$product = new FR_CardSlider_Test_Product( 10, array( 10, 11 ) );

		$this->assertSame( array( 10, 11 ), $this->slider()->slide_image_ids( $product ) );
	}

	public function test_single_image_has_no_slider_chrome(): void {
		$html = $this->slider()->slider_html( new FR_CardSlider_Test_Product( 10, array() ) );

		$this->assertStringContainsString( 'fc-card-slider--single', $html );
		$this->assertStringContainsString( 'data-id="10"', $html );
		$this->assertStringNotContainsString( 'data-fc-card-slider', $html );
		$this->assertStringNotContainsString( 'data-fc-slider-prev', $html );
	}

	public function test_multi_image_default_has_viewport_and_arrows(): void {
		$html = $this->slider()->slider_html( new FR_CardSlider_Test_Product( 10, array( 11 ) ) );

		$this->assertStringContainsString( 'data-fc-card-slider', $html );
		$this->assertStringContainsString( 'fc-card-slider__viewport', $html );
		$this->assertStringContainsString( 'data-fc-slider-prev', $html );
		$this->assertStringContainsString( 'data-fc-slider-next', $html );
	}

	public function test_arrows_can_be_turned_off(): void {
		$GLOBALS['fr_opts']['freeman_core_hover_swap_slider_arrows'] = 0;

		$html = $this->slider()->slider_html( new FR_CardSlider_Test_Product( 10, array( 11 ) ) );

		$this->assertStringNotContainsString( 'data-fc-slider-prev', $html );
		$this->assertStringContainsString( 'fc-card-slider__viewport', $html, 'slider (swipe) still renders' );
	}
}
