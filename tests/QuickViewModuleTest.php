<?php
declare(strict_types=1);

use Freeman\Core\Modules\QuickView\Labels;
use Freeman\Core\Modules\QuickView\Module;
use PHPUnit\Framework\TestCase;

/**
 * Quick View module metadata + the boot contract. The storefront + AJAX
 * surfaces are always-on since 1.23.0 (the frontend flag graduated); the
 * module-enable toggle is the kill-switch.
 *
 * @covers \Freeman\Core\Modules\QuickView\Module
 */
final class QuickViewModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_metadata(): void {
		$module = new Module();

		$this->assertSame( 'quick_view', $module->id() );
		$this->assertNotEmpty( $module->label() );
		$this->assertNotEmpty( $module->description() );
		$this->assertSame( array( 'woocommerce' => true ), $module->dependencies() );
	}

	public function test_settings_schema_carries_one_text_field_per_label(): void {
		$schema = ( new Module() )->settings_schema();

		foreach ( array_keys( Labels::defaults() ) as $key ) {
			$this->assertArrayHasKey( 'label_' . $key, $schema, "schema must carry label_$key" );
			$this->assertSame( 'text', $schema[ 'label_' . $key ]['type'] );
			$this->assertSame( '', $schema[ 'label_' . $key ]['default'], 'blank default = English fallback via Labels::get()' );
		}
	}

	public function test_boot_registers_frontend_and_ajax(): void {
		( new Module() )->boot();

		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'woocommerce_after_shop_loop_item', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_footer', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_freeman_core_quick_view_product', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_nopriv_freeman_core_quick_view_product', $GLOBALS['fr_hooks'] );
	}

	public function test_assets_exist_on_disk(): void {
		$base = FREEMAN_CORE_PATH . 'src/Modules/QuickView/';

		$this->assertFileExists( $base . 'assets/css/quick-view.css' );
		$this->assertFileExists( $base . 'assets/js/quick-view.js' );
		$this->assertFileExists( $base . 'templates/drawer-content.php' );
	}

	/**
	 * Stub product exposing only the two gallery accessors gallery_image_ids()
	 * reads (the param is untyped, so no full WC_Product is needed).
	 */
	private function product( int $featured, array $gallery ) {
		return new class( $featured, $gallery ) {
			private $featured;
			private $gallery;
			public function __construct( $featured, $gallery ) {
				$this->featured = $featured;
				$this->gallery  = $gallery;
			}
			public function get_image_id() {
				return $this->featured;
			}
			public function get_gallery_image_ids() {
				return $this->gallery;
			}
		};
	}

	public function test_gallery_image_ids_featured_only(): void {
		$this->assertSame( array( 10 ), ( new Module() )->gallery_image_ids( $this->product( 10, array() ) ) );
	}

	public function test_gallery_image_ids_featured_first_then_gallery(): void {
		$this->assertSame( array( 10, 20, 30 ), ( new Module() )->gallery_image_ids( $this->product( 10, array( 20, 30 ) ) ) );
	}

	public function test_gallery_image_ids_dedupes_and_drops_zeros(): void {
		$this->assertSame( array( 10, 30 ), ( new Module() )->gallery_image_ids( $this->product( 10, array( 10, 0, 30 ) ) ) );
	}

	public function test_gallery_image_ids_without_a_featured_image(): void {
		$this->assertSame( array( 20, 30 ), ( new Module() )->gallery_image_ids( $this->product( 0, array( 20, 30 ) ) ) );
	}
}
