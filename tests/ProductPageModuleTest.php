<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductPage\Labels;
use ShopOS\Core\Modules\ProductPage\Module;
use PHPUnit\Framework\TestCase;

/**
 * Product Page module metadata, settings schema, and the boot contract.
 * All three surfaces (coupon notice / stock urgency / designed layout) are
 * always-on since 1.23.0 (their feature flags graduated); boot() wires them
 * unconditionally and the module-enable toggle is the kill-switch.
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Module
 */
final class ProductPageModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']       = array();
		$GLOBALS['fr_hooks']      = array();
		$GLOBALS['fr_shortcodes'] = array();
	}

	public function test_metadata(): void {
		$module = new Module();

		$this->assertSame( 'product_page', $module->id() );
		$this->assertNotEmpty( $module->label() );
		$this->assertNotEmpty( $module->description() );
		$this->assertSame( array( 'woocommerce' => true ), $module->dependencies() );
	}

	public function test_settings_schema_carries_coupon_and_urgency_fields(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertSame( 'text', $schema['coupon_code']['type'] );
		$this->assertSame( '', $schema['coupon_code']['default'] );
		$this->assertSame( 'number', $schema['coupon_percent']['type'] );
		$this->assertSame( 0, $schema['coupon_percent']['default'] );
		$this->assertSame( 'number', $schema['urgency_max']['type'] );
		$this->assertSame( 5, $schema['urgency_max']['default'] );
	}

	public function test_settings_schema_carries_one_text_field_per_label(): void {
		$schema = ( new Module() )->settings_schema();

		foreach ( array_keys( Labels::defaults() ) as $key ) {
			$this->assertArrayHasKey( 'label_' . $key, $schema, "schema must carry label_$key" );
			$this->assertSame( 'text', $schema[ 'label_' . $key ]['type'] );
			$this->assertSame( '', $schema[ 'label_' . $key ]['default'], 'blank default = English fallback via Labels::get()' );
		}
	}

	public function test_boot_wires_all_surfaces(): void {
		( new Module() )->boot();

		// Coupon notice + stock urgency render hooks and shortcodes.
		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'woocommerce_single_product_summary', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'shopos_discounted_price', $GLOBALS['fr_shortcodes'] );
		$this->assertArrayHasKey( 'discounted_price', $GLOBALS['fr_shortcodes'], 'legacy alias must stay registered' );
		$this->assertArrayHasKey( 'shopos_stock_urgency', $GLOBALS['fr_shortcodes'] );
		$this->assertArrayHasKey( 'stock_urgency', $GLOBALS['fr_shortcodes'], 'legacy alias must stay registered' );

		// Designed-layout template takeover.
		$this->assertArrayHasKey( 'template_include', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'body_class', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'after_setup_theme', $GLOBALS['fr_hooks'], 'gallery theme-supports must attach after theme setup' );
	}

	public function test_assets_exist_on_disk(): void {
		$base = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/';

		$this->assertFileExists( $base . 'assets/css/coupon-notice.css' );
		$this->assertFileExists( $base . 'assets/js/coupon-notice.js' );
		$this->assertFileExists( $base . 'assets/css/stock-urgency.css' );
		$this->assertFileExists( $base . 'assets/js/stock-urgency.js' );
		$this->assertFileExists( $base . 'assets/css/product-page.css' );
		$this->assertFileExists( $base . 'assets/js/product-page.js' );
		$this->assertFileExists( $base . 'templates/single-product.php' );
	}
}
