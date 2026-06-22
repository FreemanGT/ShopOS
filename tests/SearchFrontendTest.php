<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Ajax;
use Freeman\Core\Modules\Search\Frontend;
use Freeman\Core\Modules\Search\Module;
use PHPUnit\Framework\TestCase;

/**
 * The localized JS payload the dropdown script reads — keys, the configurable
 * selector default, and the numeric knobs from settings_schema defaults.
 *
 * @covers \Freeman\Core\Modules\Search\Frontend
 */
final class SearchFrontendTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts'] = array();
	}

	public function test_localized_payload_shape(): void {
		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertArrayHasKey( 'ajaxUrl', $payload );
		$this->assertSame( Ajax::ACTION, $payload['action'] );
		$this->assertArrayHasKey( 'nonce', $payload );
		$this->assertArrayHasKey( 'labels', $payload );
		// Defaults flow from settings_schema.
		$this->assertSame( 'input[type="search"], input[name="s"]', $payload['selector'] );
		$this->assertSame( 2, $payload['minChars'] );
		$this->assertSame( 200, $payload['debounce'] );
	}

	public function test_payload_honours_setting_overrides(): void {
		$GLOBALS['fr_opts']['freeman_core_search_field_selector'] = '#my-search';
		$GLOBALS['fr_opts']['freeman_core_search_min_chars']      = 3;

		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertSame( '#my-search', $payload['selector'] );
		$this->assertSame( 3, $payload['minChars'] );
	}
}
