<?php
declare(strict_types=1);

use ShopOS\Core\Modules\RestockNotify\Widget;
use ShopOS\Core\Core\Elementor\Widget_Base;
use PHPUnit\Framework\TestCase;

/**
 * The RestockNotify back-in-stock Elementor widget — a thin shell that
 * delegates to the module's already-shipped `[restock_notify]` shortcode via
 * do_shortcode(). This locks the frozen widget id, the inherited ShopOS panel
 * category, the optional product-id control, and the pure settings→shortcode
 * mapping (the render path itself is exercised live in wp-env).
 *
 * @covers \ShopOS\Core\Modules\RestockNotify\Widget
 */
final class RestockNotifyWidgetTest extends TestCase {

	public function test_widget_id_is_frozen(): void {
		$this->assertSame( 'shopos_restock_notify', ( new Widget() )->get_name() );
	}

	public function test_extends_the_shared_widget_base(): void {
		$this->assertInstanceOf( Widget_Base::class, new Widget() );
	}

	public function test_surfaces_under_the_shopos_category(): void {
		$this->assertContains( 'shopos', ( new Widget() )->get_categories() );
	}

	public function test_registers_a_product_id_control_and_info_note(): void {
		$w = new Widget();
		// register_controls() is protected; call via a bound closure (the
		// ProductPageWidgetsTest idiom).
		( function () { $this->register_controls(); } )->call( $w );
		$this->assertSame( array( 'section_content' ), $w->fr_test_sections );
		$this->assertArrayHasKey( 'product_id', $w->fr_test_controls );
		$this->assertSame( 'number', $w->fr_test_controls['product_id']['type'] );
		$this->assertArrayHasKey( 'info', $w->fr_test_controls );
		$this->assertSame( 'raw_html', $w->fr_test_controls['info']['type'] );
	}

	public function test_blank_product_id_yields_the_bare_shortcode(): void {
		$this->assertSame( '[restock_notify]', Widget::shortcode_from_settings( array() ) );
		$this->assertSame( '[restock_notify]', Widget::shortcode_from_settings( array( 'product_id' => '' ) ) );
		$this->assertSame( '[restock_notify]', Widget::shortcode_from_settings( array( 'product_id' => 0 ) ) );
	}

	public function test_positive_product_id_is_passed_through(): void {
		$this->assertSame(
			'[restock_notify product_id="42"]',
			Widget::shortcode_from_settings( array( 'product_id' => 42 ) )
		);
		// String numerics from saved settings are coerced via absint().
		$this->assertSame(
			'[restock_notify product_id="42"]',
			Widget::shortcode_from_settings( array( 'product_id' => '42' ) )
		);
	}
}
