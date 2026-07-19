<?php
declare(strict_types=1);

use ShopOS\Core\Modules\BundleDeals\Labels;
use ShopOS\Core\Modules\BundleDeals\Module;
use PHPUnit\Framework\TestCase;

/**
 * Bundle Deals module metadata + boot wiring. The module-enable toggle is the
 * kill switch (default OFF); when booted it wires the cart engine, the
 * storefront block, the add-bundle endpoint and the admin builder.
 *
 * @covers \ShopOS\Core\Modules\BundleDeals\Module
 */
final class BundleDealsModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_metadata(): void {
		$module = new Module();

		$this->assertSame( 'bundle_deals', $module->id() );
		$this->assertNotEmpty( $module->label() );
		$this->assertNotEmpty( $module->description() );
		$this->assertSame( array( 'woocommerce' => true ), $module->dependencies() );
	}

	public function test_default_disabled(): void {
		// No shopos_core_modules option → module defaults off (its toggle is the
		// only kill switch for the cart-pricing surface).
		$this->assertFalse( ( new Module() )->is_enabled() );
	}

	public function test_settings_schema_carries_one_text_field_per_label(): void {
		$schema = ( new Module() )->settings_schema();

		foreach ( array_keys( Labels::defaults() ) as $key ) {
			$this->assertArrayHasKey( 'label_' . $key, $schema );
			$this->assertSame( 'text', $schema[ 'label_' . $key ]['type'] );
			$this->assertSame( '', $schema[ 'label_' . $key ]['default'] );
		}
	}

	public function test_boot_wires_cart_frontend_ajax_admin(): void {
		( new Module() )->boot();
		$hooks = $GLOBALS['fr_hooks'];

		// Cart pricing engine.
		$this->assertArrayHasKey( 'woocommerce_before_calculate_totals', $hooks );
		$this->assertArrayHasKey( 'woocommerce_cart_item_price', $hooks );
		$this->assertArrayHasKey( 'woocommerce_cart_item_subtotal', $hooks );

		// Storefront block.
		$this->assertArrayHasKey( 'wp_enqueue_scripts', $hooks );
		$this->assertArrayHasKey( 'woocommerce_single_product_summary', $hooks );

		// Add-bundle endpoint (public).
		$this->assertArrayHasKey( 'wp_ajax_shopos_core_bundle_deals_add', $hooks );
		$this->assertArrayHasKey( 'wp_ajax_nopriv_shopos_core_bundle_deals_add', $hooks );

		// Admin builder.
		$this->assertArrayHasKey( 'shopos_core/module_page/bundle_deals', $hooks );
		$this->assertArrayHasKey( 'admin_post_shopos_core_bundle_deals_save', $hooks );

		// Elementor widget registration.
		$this->assertArrayHasKey( 'elementor/widgets/register', $hooks );
	}

	public function test_before_calculate_totals_priority_is_20(): void {
		( new Module() )->boot();
		$found = false;
		foreach ( $GLOBALS['fr_hooks']['woocommerce_before_calculate_totals'] as $h ) {
			if ( 20 === (int) $h['priority'] ) {
				$found = true;
			}
		}
		$this->assertTrue( $found, 'the apply pass runs at priority 20' );
	}

	public function test_assets_exist_on_disk(): void {
		$base = SHOPOS_CORE_PATH . 'src/Modules/BundleDeals/';

		$this->assertFileExists( $base . 'assets/css/bundle-deals.css' );
		$this->assertFileExists( $base . 'assets/js/bundle-deals.js' );
		$this->assertFileExists( $base . 'assets/css/bundle-admin.css' );
		$this->assertFileExists( $base . 'assets/js/bundle-admin.js' );
		$this->assertFileExists( $base . 'templates/admin-builder.php' );
	}
}
