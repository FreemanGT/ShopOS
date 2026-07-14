<?php
declare(strict_types=1);

use ShopOS\Core\Modules\HoverSwap\Module;
use PHPUnit\Framework\TestCase;

/**
 * Card Image Effects module metadata + the dropdown-driven boot contract:
 * activation is the module-enable switch plus the `card_image_mode` setting
 * (no feature flags). Mode `none` (the default) registers nothing.
 *
 * @covers \ShopOS\Core\Modules\HoverSwap\Module
 */
final class HoverSwapModuleTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_metadata(): void {
		$module = new Module();

		$this->assertSame( 'hover_swap', $module->id() );
		$this->assertNotEmpty( $module->label() );
		$this->assertNotEmpty( $module->description() );
		$this->assertSame( array( 'woocommerce' => true ), $module->dependencies() );
	}

	public function test_settings_schema_has_card_image_mode_select(): void {
		$schema = ( new Module() )->settings_schema();

		$this->assertArrayHasKey( 'card_image_mode', $schema );
		$this->assertSame( 'select', $schema['card_image_mode']['type'] );
		$this->assertSame( 'none', $schema['card_image_mode']['default'], 'default ships dark — nothing until a mode is chosen' );
		// Settings_Hub renders select options from the `choices` key.
		$this->assertArrayHasKey( 'choices', $schema['card_image_mode'], 'select must use the choices key Settings_Hub renders' );
		foreach ( array( 'none', 'hover_swap', 'gallery_slider' ) as $mode ) {
			$this->assertArrayHasKey( $mode, $schema['card_image_mode']['choices'] );
		}
		// Arrows are the only slider sub-toggle; default on.
		$this->assertSame( 1, $schema['slider_arrows']['default'] );
		$this->assertArrayNotHasKey( 'slider_dots', $schema, 'dots removed' );
		$this->assertArrayNotHasKey( 'slider_autoplay', $schema, 'autoplay removed' );
	}

	public function test_boot_default_mode_none_registers_nothing(): void {
		// No card_image_mode saved → default 'none' → dark.
		( new Module() )->boot();

		$this->assertSame( array(), $GLOBALS['fr_hooks'], 'mode none must register zero hooks' );
	}

	public function test_boot_mode_hover_swap_registers_overlay(): void {
		$GLOBALS['fr_opts']['shopos_core_hover_swap_card_image_mode'] = 'hover_swap';

		( new Module() )->boot();

		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'woocommerce_before_shop_loop_item_title', $GLOBALS['fr_hooks'] );
		$this->assertSame( 11, $GLOBALS['fr_hooks']['woocommerce_before_shop_loop_item_title'][0]['priority'], 'hover overlay injects at priority 11' );
	}

	public function test_boot_mode_gallery_slider_registers_slider(): void {
		$GLOBALS['fr_opts']['shopos_core_hover_swap_card_image_mode'] = 'gallery_slider';

		( new Module() )->boot();

		$this->assertArrayHasKey( 'wp_enqueue_scripts', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'woocommerce_before_shop_loop_item_title', $GLOBALS['fr_hooks'] );
		$this->assertSame( 10, $GLOBALS['fr_hooks']['woocommerce_before_shop_loop_item_title'][0]['priority'], 'slider takes the thumbnail slot at priority 10' );
	}

	public function test_assets_exist_on_disk(): void {
		$base = SHOPOS_CORE_PATH . 'src/Modules/HoverSwap/';

		$this->assertFileExists( $base . 'assets/css/hover-swap.css' );
		$this->assertFileExists( $base . 'assets/css/card-slider.css' );
		$this->assertFileExists( $base . 'assets/js/card-slider.js' );
	}
}
