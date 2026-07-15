<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductPage\Coupon_Notice_Widget;
use ShopOS\Core\Modules\ProductPage\Stock_Urgency_Widget;
use ShopOS\Core\Core\Elementor\Widget_Base;
use PHPUnit\Framework\TestCase;

/**
 * The two ProductPage conversion-block Elementor widgets — thin shells over the
 * module's already-shipped `Coupon_Notice::shortcode()` / `Stock_Urgency::shortcode()`.
 * These lock the frozen widget ids, the inherited ShopOS panel category, and the
 * info-note control (config lives in global settings, not per-instance).
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Coupon_Notice_Widget
 * @covers \ShopOS\Core\Modules\ProductPage\Stock_Urgency_Widget
 */
final class ProductPageWidgetsTest extends TestCase {

	public function test_coupon_widget_id_is_frozen(): void {
		$this->assertSame( 'shopos_discounted_price', ( new Coupon_Notice_Widget() )->get_name() );
	}

	public function test_stock_widget_id_is_frozen(): void {
		$this->assertSame( 'shopos_stock_urgency', ( new Stock_Urgency_Widget() )->get_name() );
	}

	public function test_both_extend_the_shared_widget_base(): void {
		$this->assertInstanceOf( Widget_Base::class, new Coupon_Notice_Widget() );
		$this->assertInstanceOf( Widget_Base::class, new Stock_Urgency_Widget() );
	}

	public function test_both_surface_under_the_shopos_category(): void {
		$this->assertContains( 'shopos', ( new Coupon_Notice_Widget() )->get_categories() );
		$this->assertContains( 'shopos', ( new Stock_Urgency_Widget() )->get_categories() );
	}

	public function test_widget_ids_are_distinct(): void {
		$this->assertNotSame(
			( new Coupon_Notice_Widget() )->get_name(),
			( new Stock_Urgency_Widget() )->get_name()
		);
	}

	public function test_coupon_widget_registers_a_single_info_section(): void {
		$w = new Coupon_Notice_Widget();
		// register_controls() is protected; call via a bound closure (the
		// CategorySliderDesignTokensTest idiom).
		( function () { $this->register_controls(); } )->call( $w );
		$this->assertSame( array( 'section_info' ), $w->fr_test_sections );
		$this->assertArrayHasKey( 'info', $w->fr_test_controls );
		$this->assertSame( 'raw_html', $w->fr_test_controls['info']['type'] );
	}

	public function test_stock_widget_registers_a_single_info_section(): void {
		$w = new Stock_Urgency_Widget();
		( function () { $this->register_controls(); } )->call( $w );
		$this->assertSame( array( 'section_info' ), $w->fr_test_sections );
		$this->assertArrayHasKey( 'info', $w->fr_test_controls );
		$this->assertSame( 'raw_html', $w->fr_test_controls['info']['type'] );
	}
}
