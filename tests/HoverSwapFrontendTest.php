<?php
declare(strict_types=1);

use ShopOS\Core\Modules\HoverSwap\Frontend;
use ShopOS\Core\Modules\HoverSwap\Module;
use PHPUnit\Framework\TestCase;

// A gallery-bearing product that is `instanceof WC_Product` regardless of
// which base WC_Product stub another test file defined first (e.g. QuickView's
// lacks get_gallery_image_ids). Subclassing keeps us collision-proof in the
// full suite.
if ( ! class_exists( '\\WC_Product' ) ) {
	eval( 'class WC_Product {}' );
}
if ( ! class_exists( 'FR_HoverSwap_Test_Product' ) ) {
	class FR_HoverSwap_Test_Product extends \WC_Product {
		private $gallery;
		public function __construct( array $ids = array() ) {
			$this->gallery = $ids;
		}
		public function get_gallery_image_ids() {
			return $this->gallery;
		}
	}
}

// Minimal wp_get_attachment_image: echoes the requested id + class so the
// overlay markup can be asserted without a real WP_Post / image pipeline.
if ( ! function_exists( 'wp_get_attachment_image' ) ) {
	function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attr = array() ) {
		$class = is_array( $attr ) && isset( $attr['class'] ) ? $attr['class'] : '';
		return '<img data-id="' . (int) $id . '" class="' . $class . '" />';
	}
}

/**
 * Hover Image Swap storefront seams: which image is swapped to, the overlay
 * markup, the no-gallery no-op, and the show filter.
 *
 * @covers \ShopOS\Core\Modules\HoverSwap\Frontend
 */
final class HoverSwapFrontendTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset( $GLOBALS['product'] );
	}

	private function frontend(): Frontend {
		return new Frontend( new Module() );
	}

	public function test_second_image_id_is_first_gallery_image(): void {
		$product = new FR_HoverSwap_Test_Product( array( 55, 56, 57 ) );

		$this->assertSame( 55, $this->frontend()->second_image_id( $product ) );
	}

	public function test_second_image_id_zero_when_no_gallery(): void {
		$product = new FR_HoverSwap_Test_Product( array() );

		$this->assertSame( 0, $this->frontend()->second_image_id( $product ) );
	}

	public function test_overlay_html_carries_the_gallery_image_and_class(): void {
		$product = new FR_HoverSwap_Test_Product( array( 88 ) );

		$html = $this->frontend()->overlay_html( $product );

		$this->assertStringContainsString( 'data-id="88"', $html );
		$this->assertStringContainsString( 'fc-hover-swap__img', $html );
	}

	public function test_overlay_html_empty_when_no_gallery_image(): void {
		$product = new FR_HoverSwap_Test_Product( array() );

		$this->assertSame( '', $this->frontend()->overlay_html( $product ), 'no gallery image = no overlay (no-op)' );
	}

	public function test_show_filter_can_suppress_the_overlay(): void {
		add_filter( 'shopos_core/hover_swap/show', '__return_false' );

		$GLOBALS['product'] = new FR_HoverSwap_Test_Product( array( 88 ) );

		ob_start();
		$this->frontend()->render_overlay();
		$out = (string) ob_get_clean();

		$this->assertSame( '', $out, 'show=false must render nothing' );
	}

	public function test_render_overlay_echoes_markup_for_a_loop_product(): void {
		$GLOBALS['product'] = new FR_HoverSwap_Test_Product( array( 88 ) );

		ob_start();
		$this->frontend()->render_overlay();
		$out = (string) ob_get_clean();

		$this->assertStringContainsString( 'data-id="88"', $out );
		$this->assertStringContainsString( 'fc-hover-swap__img', $out );
	}
}
