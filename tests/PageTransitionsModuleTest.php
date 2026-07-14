<?php
declare(strict_types=1);

use Freeman\Core\Modules\PageTransitions\Module;
use PHPUnit\Framework\TestCase;

/**
 * Module identity, default-disabled state, boot wiring, and the localized
 * payload (label falls back to the English default when the setting is
 * blank). The overlay/trigger behaviour and the cross-document fade are
 * JS/CSS — live-QA.
 *
 * @covers \Freeman\Core\Modules\PageTransitions\Module
 */
final class PageTransitionsModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_id_and_option_names(): void {
		$module = new Module();

		$this->assertSame( 'page_transitions', $module->id() );
		$this->assertSame( 'freeman_core_page_transitions_loading_label', $module->option_name( 'loading_label' ) );
	}

	public function test_disabled_by_default(): void {
		// Absent from freeman_core_modules → a newly-added module is off.
		$this->assertFalse( ( new Module() )->is_enabled() );
	}

	public function test_settings_schema_has_loading_label(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertArrayHasKey( 'loading_label', $schema );
		$this->assertSame( 'text', $schema['loading_label']['type'] );
		$this->assertSame( '', $schema['loading_label']['default'] );
	}

	public function test_boot_head_enqueues_the_assets(): void {
		( new Module() )->boot();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts' ) );
	}

	public function test_localized_payload_falls_back_to_english_default(): void {
		$payload = ( new Module() )->localized_payload();

		$this->assertSame( 'Loading…', $payload['label'] );
	}

	public function test_localized_payload_falls_back_to_hebrew_on_hebrew_locale(): void {
		$GLOBALS['fr_locale'] = 'he_IL';

		$payload = ( new Module() )->localized_payload();

		unset( $GLOBALS['fr_locale'] );
		$this->assertSame( 'טוען תוצאות…', $payload['label'] );
	}

	public function test_localized_payload_uses_saved_label(): void {
		$GLOBALS['fr_opts']['freeman_core_page_transitions_loading_label'] = 'טוען תוצאות…';

		$payload = ( new Module() )->localized_payload();

		$this->assertSame( 'טוען תוצאות…', $payload['label'] );
	}
}
