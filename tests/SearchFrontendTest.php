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

	public function test_localized_payload_exposes_display_toggles(): void {
		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertArrayHasKey( 'show', $payload );
		// Schema defaults: image + price on, SKU off.
		$this->assertTrue( $payload['show']['image'] );
		$this->assertTrue( $payload['show']['price'] );
		$this->assertFalse( $payload['show']['sku'] );
	}

	public function test_display_toggles_read_saved_integer_values(): void {
		// Settings_Hub persists checkboxes as 1/0 (not the schema's 'yes'/'no'
		// string default), so the payload must read them as booleans — otherwise a
		// saved settings page hides image/price/SKU. Regression for 1.21.9.
		$GLOBALS['fr_opts']['freeman_core_search_show_image'] = 0;
		$GLOBALS['fr_opts']['freeman_core_search_show_price'] = 1;
		$GLOBALS['fr_opts']['freeman_core_search_show_sku']   = 1;

		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertFalse( $payload['show']['image'] );
		$this->assertTrue( $payload['show']['price'] );
		$this->assertTrue( $payload['show']['sku'] );
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

	public function test_payload_labels_reflect_saved_overrides(): void {
		$GLOBALS['fr_opts']['freeman_core_search_label_no_results'] = 'אין תוצאות';
		$GLOBALS['fr_opts']['freeman_core_search_label_see_all']    = 'הצג הכל';

		$payload = ( new Frontend( new Module() ) )->localized_payload();

		$this->assertSame( 'אין תוצאות', $payload['labels']['noResults'] );
		$this->assertSame( 'הצג הכל', $payload['labels']['seeAll'] );
	}

	public function test_shortcode_placeholder_default_comes_from_label_setting(): void {
		// No att passed → the placeholder default is the admin label setting.
		$GLOBALS['fr_opts']['freeman_core_search_label_placeholder'] = 'חפש מוצר';

		$html = ( new Frontend( new Module() ) )->render_form();

		$this->assertStringContainsString( 'placeholder="חפש מוצר"', $html );
	}
}
