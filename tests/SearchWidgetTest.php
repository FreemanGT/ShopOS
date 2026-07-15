<?php
declare(strict_types=1);

use ShopOS\Core\Modules\Search\Widget;
use ShopOS\Core\Core\Elementor\Widget_Base;
use PHPUnit\Framework\TestCase;

/**
 * The ShopOS Search Elementor widget — a thin shell over the module's
 * `render_form()`. These lock the frozen widget id, the inherited ShopOS panel
 * category, and the pure settings→atts mapping (blank controls must fall
 * through to the Labels default, mirroring the shortcode).
 *
 * @covers \ShopOS\Core\Modules\Search\Widget
 */
final class SearchWidgetTest extends TestCase {

	public function test_widget_id_is_frozen(): void {
		$this->assertSame( 'shopos_search', ( new Widget() )->get_name() );
	}

	public function test_extends_the_shared_widget_base(): void {
		$this->assertInstanceOf( Widget_Base::class, new Widget() );
	}

	public function test_surfaces_under_the_shopos_category(): void {
		$this->assertContains( 'shopos', ( new Widget() )->get_categories() );
	}

	public function test_atts_from_settings_is_empty_when_controls_are_blank(): void {
		// Blank / missing / whitespace-only → no att, so render_form() uses the
		// Labels default (the shortcode_atts fallback).
		$this->assertSame( array(), Widget::atts_from_settings( array() ) );
		$this->assertSame( array(), Widget::atts_from_settings( array( 'placeholder' => '', 'button' => '   ' ) ) );
	}

	public function test_atts_from_settings_passes_non_empty_overrides(): void {
		$this->assertSame(
			array( 'placeholder' => 'Find gear', 'button' => 'Go' ),
			Widget::atts_from_settings( array( 'placeholder' => 'Find gear', 'button' => 'Go' ) )
		);
	}

	public function test_atts_from_settings_passes_only_the_set_override(): void {
		$this->assertSame(
			array( 'button' => 'Go' ),
			Widget::atts_from_settings( array( 'placeholder' => '', 'button' => 'Go' ) )
		);
	}
}
