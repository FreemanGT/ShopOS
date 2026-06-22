<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Module;
use PHPUnit\Framework\TestCase;

/**
 * Module identity, default-disabled state, and the flag-gated boot contract:
 * with `freeman_core_search_indexer_enabled` off, boot() registers nothing.
 *
 * @covers \Freeman\Core\Modules\Search\Module
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
		$this->assertSame( 'freeman_core_search_dirty_queue', $module->option_name( 'dirty_queue' ) );
	}

	public function test_disabled_by_default(): void {
		$this->assertFalse( ( new Module() )->is_enabled() );
	}

	public function test_boot_registers_nothing_when_flag_is_off(): void {
		( new Module() )->boot();

		$this->assertSame( array(), $GLOBALS['fr_hooks'], 'flag-off boot must register zero hooks' );
	}

	public function test_boot_wires_indexer_when_flag_is_on(): void {
		$GLOBALS['fr_opts']['freeman_core_search_indexer_enabled'] = '1';

		( new Module() )->boot();

		$this->assertNotFalse( has_action( 'woocommerce_update_product' ) );
		$this->assertNotFalse( has_action( 'woocommerce_variation_set_stock' ) );
		// Scheduling is deferred to init (Action Scheduler isn't ready at plugins_loaded).
		$this->assertNotFalse( has_action( 'init' ) );
		// The cron-schedule recurrence filter is registered.
		$this->assertNotFalse( has_filter( 'cron_schedules' ) );
		// Indexer flag alone wires no public dropdown endpoint.
		$this->assertArrayNotHasKey( 'wp_ajax_freeman_core_search_query', $GLOBALS['fr_hooks'] );
	}

	public function test_boot_wires_dropdown_when_flag_is_on(): void {
		$GLOBALS['fr_opts']['freeman_core_search_dropdown_enabled'] = '1';

		( new Module() )->boot();

		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_freeman_core_search_query', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_nopriv_freeman_core_search_query', $GLOBALS['fr_hooks'] );
		// Dropdown flag alone wires no indexer lifecycle hooks.
		$this->assertArrayNotHasKey( 'woocommerce_update_product', $GLOBALS['fr_hooks'] );
	}

	public function test_boot_wires_results_query_when_flag_is_on(): void {
		$GLOBALS['fr_opts']['freeman_core_search_results_enabled'] = '1';

		( new Module() )->boot();

		$this->assertArrayHasKey( 'pre_get_posts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'posts_search', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'freeman_core/shop_filters/search_product_ids', $GLOBALS['fr_hooks'] );
		// Results flag alone wires no indexer hooks and no public dropdown endpoint.
		$this->assertArrayNotHasKey( 'woocommerce_update_product', $GLOBALS['fr_hooks'] );
		$this->assertArrayNotHasKey( 'wp_ajax_freeman_core_search_query', $GLOBALS['fr_hooks'] );
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
