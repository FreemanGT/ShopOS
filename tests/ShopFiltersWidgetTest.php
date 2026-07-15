<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ShopFilters\Widget;
use ShopOS\Core\Core\Elementor\Widget_Base;
use PHPUnit\Framework\TestCase;

/**
 * The Shop Filters Elementor widget — a thin shell that delegates to the
 * module's already-shipped `[shopos_shop_filters]` shortcode via a throwaway
 * Shortcode instance. This locks the frozen widget id, the inherited ShopOS
 * panel category, and the single info-note control (there are no per-instance
 * settings — the panel is driven by page context + global module settings, so
 * the render path itself is exercised live in wp-env).
 *
 * @covers \ShopOS\Core\Modules\ShopFilters\Widget
 */
final class ShopFiltersWidgetTest extends TestCase {

	public function test_widget_id_is_frozen(): void {
		$this->assertSame( 'shopos_shop_filters', ( new Widget() )->get_name() );
	}

	public function test_extends_the_shared_widget_base(): void {
		$this->assertInstanceOf( Widget_Base::class, new Widget() );
	}

	public function test_surfaces_under_the_shopos_category(): void {
		$categories = ( new Widget() )->get_categories();
		$this->assertContains( 'shopos', $categories );
		$this->assertSame( 'shopos', $categories[0], 'ShopOS category should be first.' );
	}

	public function test_registers_a_single_info_note_and_no_functional_controls(): void {
		$w = new Widget();
		// register_controls() is protected; call via a bound closure (the
		// ProductPageWidgetsTest / RestockNotifyWidgetTest idiom).
		( function () { $this->register_controls(); } )->call( $w );
		$this->assertSame( array( 'section_info' ), $w->fr_test_sections );
		$this->assertSame( array( 'info' ), array_keys( $w->fr_test_controls ) );
		$this->assertSame( 'raw_html', $w->fr_test_controls['info']['type'] );
	}
}
