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

	public function test_payload_honours_numeric_setting_overrides(): void {
		$GLOBALS['fr_opts']['freeman_core_search_min_chars']   = 3;
		$GLOBALS['fr_opts']['freeman_core_search_debounce_ms'] = 350;

		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertSame( 3, $payload['minChars'] );
		$this->assertSame( 350, $payload['debounce'] );
	}

	public function test_shortcode_form_is_enhanceable_and_native(): void {
		$html = ( new Frontend( new Module() ) )->render_form();

		// input[name="s"] type=search → matched by the default dropdown selector.
		$this->assertStringContainsString( 'type="search"', $html );
		$this->assertStringContainsString( 'name="s"', $html );
		// GET form to home + product post type → native product search with JS off.
		$this->assertStringContainsString( 'method="get"', $html );
		$this->assertStringContainsString( 'name="post_type" value="product"', $html );
	}

	public function test_shortcode_honours_placeholder_attr(): void {
		$html = ( new Frontend( new Module() ) )->render_form( array( 'placeholder' => 'Find gear' ) );

		$this->assertStringContainsString( 'placeholder="Find gear"', $html );
	}
}
