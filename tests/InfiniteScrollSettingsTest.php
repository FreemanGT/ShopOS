<?php
declare(strict_types=1);

use ShopOS\Core\Modules\InfiniteScroll\Module;
use PHPUnit\Framework\TestCase;

/**
 * Wave 3.1a — settings registration + payload propagation to localized
 * JS payload. Hook firing + render-path tests live in 3.1b.
 *
 * @covers \ShopOS\Core\Modules\InfiniteScroll\Module
 */
final class InfiniteScrollSettingsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_trigger_mode_setting_registers_with_default_auto(): void {
		$schema = ( new Module() )->settings_schema();
		$this->assertSame( 'auto', $schema['trigger_mode']['default'] );
		$this->assertSame( 'select', $schema['trigger_mode']['type'] );
		$this->assertArrayHasKey( 'choices', $schema['trigger_mode'] );
		$this->assertSame(
			array( 'auto', 'button', 'hybrid' ),
			array_keys( $schema['trigger_mode']['choices'] )
		);
	}

	public function test_history_mode_setting_registers_with_default_disabled(): void {
		// Default flipped pushState → disabled in 1.24.8: the 1.23.0 flag
		// graduation had silently resurrected pushState-by-default, undoing
		// the 1.21.14 clean-URLs fix and breaking back-navigation scroll
		// restore (Back landed on a /page/N/ URL the server renders alone).
		$schema = ( new Module() )->settings_schema();
		$this->assertSame( 'disabled', $schema['history_mode']['default'] );
		$this->assertSame( 'select', $schema['history_mode']['type'] );
		$this->assertArrayHasKey( 'choices', $schema['history_mode'] );
		$this->assertSame(
			array( 'pushState', 'replaceState', 'disabled' ),
			array_keys( $schema['history_mode']['choices'] )
		);
	}

	public function test_existing_settings_preserved_with_original_defaults(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertArrayHasKey( 'skeleton_count', $schema );
		$this->assertSame( 6, $schema['skeleton_count']['default'] );

		$this->assertArrayHasKey( 'max_pages', $schema );
		$this->assertSame( 50, $schema['max_pages']['default'] );

		$this->assertArrayHasKey( 'end_message', $schema );
		$this->assertSame(
			__( 'You have reached the end.', 'shopos-core' ),
			$schema['end_message']['default']
		);
	}

	public function test_get_option_falls_back_to_schema_defaults_for_new_settings(): void {
		$module = new Module();
		$this->assertSame( 'auto', $module->get_option( 'trigger_mode' ) );
		$this->assertSame( 'disabled', $module->get_option( 'history_mode' ) );
		$this->assertSame( 2, $module->get_option( 'hybrid_threshold' ) );
	}

	public function test_hybrid_threshold_setting_registers_with_default_2(): void {
		$schema = ( new Module() )->settings_schema();
		$this->assertSame( 2, $schema['hybrid_threshold']['default'] );
	}

	public function test_localized_payload_always_signals_trigger_modes_enabled(): void {
		// Always-on since 1.23.0 (the trigger_modes flag graduated); the key
		// is kept true for shipped JS that still reads it.
		$payload = ( new Module() )->localized_payload();
		$this->assertTrue( $payload['triggerModesEnabled'] );
	}
}
