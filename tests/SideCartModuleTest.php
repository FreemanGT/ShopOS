<?php
declare(strict_types=1);

use ShopOS\Core\Modules\SideCart\Frontend;
use ShopOS\Core\Modules\SideCart\Module;
use PHPUnit\Framework\TestCase;

/**
 * Side Cart module identity, default-off state, settings schema, boot wiring,
 * the checkbox-reader coercion, and the storefront seams (drawer shell markup,
 * body-container wrapper, label overrides, localized payload). The cart-driven
 * body render + coupon/remove ops are integration — live-QA.
 *
 * @covers \ShopOS\Core\Modules\SideCart\Module
 * @covers \ShopOS\Core\Modules\SideCart\Frontend
 */
final class SideCartModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	private function frontend(): Frontend {
		return new Frontend( new Module() );
	}

	public function test_id_and_option_names(): void {
		$module = new Module();

		$this->assertSame( 'side_cart', $module->id() );
		$this->assertSame( 'shopos_core_side_cart_free_shipping_meter', $module->option_name( 'free_shipping_meter' ) );
	}

	public function test_disabled_by_default(): void {
		// Absent from shopos_core_modules → a newly-added module is off.
		$this->assertFalse( ( new Module() )->is_enabled() );
	}

	public function test_settings_schema_has_toggles_and_labels(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertSame( 'checkbox', $schema['free_shipping_meter']['type'] );
		$this->assertSame( 'yes', $schema['free_shipping_meter']['default'] );
		$this->assertSame( 'checkbox', $schema['show_recommendations']['type'] );

		$this->assertArrayHasKey( 'label_heading', $schema );
		$this->assertSame( 'text', $schema['label_heading']['type'] );
		$this->assertSame( '', $schema['label_heading']['default'] );
		$this->assertArrayHasKey( 'label_checkout', $schema );
	}

	public function test_bool_option_coerces_saved_and_default_shapes(): void {
		$module = new Module();

		// Unset → schema-ish default true.
		$this->assertTrue( $module->bool_option( 'free_shipping_meter', true ) );

		$GLOBALS['fr_opts']['shopos_core_side_cart_free_shipping_meter'] = '0';
		$this->assertFalse( $module->bool_option( 'free_shipping_meter', true ) );

		$GLOBALS['fr_opts']['shopos_core_side_cart_free_shipping_meter'] = '1';
		$this->assertTrue( $module->bool_option( 'free_shipping_meter', true ) );

		$GLOBALS['fr_opts']['shopos_core_side_cart_free_shipping_meter'] = 'no';
		$this->assertFalse( $module->bool_option( 'free_shipping_meter', true ) );
	}

	public function test_boot_wires_storefront_and_ajax(): void {
		( new Module() )->boot();

		$this->assertNotFalse( has_action( 'wp_enqueue_scripts' ) );
		$this->assertNotFalse( has_action( 'wp_footer' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_shopos_core_side_cart' ) );
		$this->assertNotFalse( has_action( 'wp_ajax_nopriv_shopos_core_side_cart' ) );
	}

	public function test_drawer_shell_shape(): void {
		$html = $this->frontend()->drawer_shell_html( '' );

		$this->assertStringContainsString( 'id="shopos-side-cart"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( 'role="dialog"', $html );
		$this->assertStringContainsString( 'class="shopos-side-cart__body"', $html );
		$this->assertStringContainsString( 'data-shopos-sc-body', $html );
		$this->assertStringContainsString( 'data-shopos-sc-close', $html );
	}

	public function test_body_container_wraps_the_rendered_body(): void {
		$html = $this->frontend()->body_container_html( '<p>hi</p>' );

		$this->assertStringStartsWith( '<div class="shopos-side-cart__body" data-shopos-sc-body', $html );
		$this->assertStringContainsString( '<p>hi</p>', $html );
	}

	public function test_heading_label_is_overridable_blank_falls_back(): void {
		$GLOBALS['fr_opts']['shopos_core_side_cart_label_heading'] = 'העגלה שלך';
		$this->assertStringContainsString( 'aria-label="העגלה שלך"', $this->frontend()->drawer_shell_html() );

		$GLOBALS['fr_opts']['shopos_core_side_cart_label_heading'] = '   ';
		$this->assertStringContainsString( 'aria-label="Your cart"', $this->frontend()->drawer_shell_html() );
	}

	public function test_localized_payload_shape(): void {
		$payload = $this->frontend()->localized_payload();

		$this->assertSame( 'https://example.test/wp-admin/admin-ajax.php', $payload['ajaxUrl'] );
		$this->assertSame( 'shopos_core_side_cart', $payload['action'] );
		$this->assertNotEmpty( $payload['nonce'] );
		$this->assertSame( '.shopos-side-cart__body', $payload['bodySelector'] );
		$this->assertNotEmpty( $payload['cartLinkSelectors'] );
		$this->assertSame( 'Updating…', $payload['labels']['loading'] );
	}
}
