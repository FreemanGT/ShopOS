<?php
declare(strict_types=1);

use ShopOS\Core\Modules\Search\Module;
use PHPUnit\Framework\TestCase;

/**
 * Module identity, default-disabled state, and the always-on boot contract:
 * graduated in 1.21.0 (the three `search`/* flags removed), so booting the
 * module wires every surface — indexer, dropdown, results page.
 *
 * @covers \ShopOS\Core\Modules\Search\Module
 */
final class SearchModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		$GLOBALS['fr_cron']  = array();
	}

	public function test_metadata_and_option_names(): void {
		$module = new Module();

		$this->assertSame( 'search', $module->id() );
		$this->assertNotEmpty( $module->label() );
		$this->assertNotEmpty( $module->description() );
		$this->assertSame( array( 'woocommerce' => true ), $module->dependencies() );
		$this->assertSame( 'shopos_core_search_dirty_queue', $module->option_name( 'dirty_queue' ) );
	}

	public function test_disabled_by_default(): void {
		$this->assertFalse( ( new Module() )->is_enabled() );
	}

	public function test_boot_wires_all_surfaces(): void {
		( new Module() )->boot();

		// Indexer lifecycle + scheduling + cron recurrence.
		$this->assertArrayHasKey( 'woocommerce_update_product', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'woocommerce_variation_set_stock', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'init', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'cron_schedules', $GLOBALS['fr_hooks'] );
		// Live dropdown + public endpoint.
		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_shopos_core_search_query', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_nopriv_shopos_core_search_query', $GLOBALS['fr_hooks'] );
		// Engine-driven results page + Shop Filters facet feed (pre-filter
		// short-circuits the native search WP_Query; post-filter kept for back-compat).
		$this->assertArrayHasKey( 'pre_get_posts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'posts_search', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'shopos_core/shop_filters/pre_search_product_ids', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'shopos_core/shop_filters/search_product_ids', $GLOBALS['fr_hooks'] );
	}

	public function test_settings_schema_has_dropdown_fields(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertSame( 2, $schema['min_chars']['default'] );
		$this->assertSame( 200, $schema['debounce_ms']['default'] );
		// The field selector is no longer a setting (the shortcode field is matched
		// by the hardcoded default).
		$this->assertArrayNotHasKey( 'field_selector', $schema );
	}
}
