<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Elementor + WC stubs — match the shapes used in ProductSliderHooksTest so
// this file can run in isolation.
if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
	eval( 'namespace Elementor; class Widget_Base { public $fr_test_settings = array(); public function __construct( $data = array(), $args = null ) {} public function get_settings_for_display() { return $this->fr_test_settings; } }' );
}
if ( ! class_exists( '\\Elementor\\Controls_Manager' ) ) {
	eval( 'namespace Elementor; class Controls_Manager { const TAB_CONTENT = "content"; const TAB_STYLE = "style"; const TEXT = "text"; const NUMBER = "number"; const SELECT = "select"; const SWITCHER = "switcher"; const COLOR = "color"; const SLIDER = "slider"; const SELECT2 = "select2"; const CHOOSE = "choose"; const HEADING = "heading"; }' );
}
if ( ! class_exists( '\\Elementor\\Group_Control_Typography' ) ) {
	eval( 'namespace Elementor; class Group_Control_Typography { public static function get_type() { return "typography"; } }' );
}
if ( ! class_exists( '\\WC_Product' ) ) {
	eval( 'class WC_Product { private $id; public function __construct( $id ) { $this->id = $id; } public function get_id() { return $this->id; } public function is_visible() { return ! isset( $GLOBALS["fr_wc_visible"][ $this->id ] ) || (bool) $GLOBALS["fr_wc_visible"][ $this->id ]; } }' );
}

if ( ! function_exists( 'wc_get_products' ) ) {
	function wc_get_products( $args = array() ) {
		$GLOBALS['fr_wc_get_products_args'] = $args;
		return $GLOBALS['fr_wc_get_products_return'] ?? array();
	}
}
if ( ! function_exists( 'is_post_type_archive' ) ) {
	function is_post_type_archive( $type ) { return false; }
}
if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $type ) { return false; }
}
if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $tax ) { return false; }
}
if ( ! function_exists( 'wc_get_template_part' ) ) {
	function wc_get_template_part( $slug, $name = '' ) { /* no-op */ }
}
if ( ! function_exists( 'setup_postdata' ) ) {
	function setup_postdata( $post ) {}
}
if ( ! function_exists( 'wp_reset_postdata' ) ) {
	function wp_reset_postdata() {}
}
if ( ! function_exists( 'get_post' ) ) {
	function get_post( $id ) { return (object) array( 'ID' => is_object( $id ) ? $id->ID : $id ); }
}

use Freeman\Core\Modules\ProductSlider\Widget;

/**
 * Grid parity audit G1 (1.12.31): grid mode feeds `--cs-per-mobile` into
 * `grid-template-columns: repeat(var(--cs-per-mobile), …)`, and `repeat()`
 * requires an integer track count — a fractional "peek" value (slider-only
 * concept) is invalid CSS there and collapses the mobile grid to a single
 * column. The render path must round the value to a whole column count in
 * grid mode while preserving the float in slider mode.
 *
 * @covers \Freeman\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderGridMobileColumnsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']                   = array();
		$GLOBALS['fr_hooks']                  = array();
		$GLOBALS['fr_wc_get_products_return'] = array( new \WC_Product( 42 ) );
	}

	public function test_grid_mode_rounds_fractional_mobile_columns_down(): void {
		$html = $this->render_widget( $this->settings_for( 'grid', 1.4 ) );

		$this->assertStringContainsString( '--cs-per-mobile: 1;', $html );
	}

	public function test_grid_mode_rounds_fractional_mobile_columns_up(): void {
		$html = $this->render_widget( $this->settings_for( 'grid', 2.5 ) );

		$this->assertStringContainsString( '--cs-per-mobile: 3;', $html );
	}

	public function test_slider_mode_preserves_fractional_peek_value(): void {
		$html = $this->render_widget( $this->settings_for( 'slider', 1.4 ) );

		$this->assertStringContainsString( '--cs-per-mobile: 1.4;', $html );
	}

	private function render_widget( array $settings ): string {
		$widget                   = new Widget();
		$widget->fr_test_settings = $settings;

		ob_start();
		$ref = new \ReflectionClass( $widget );
		$m   = $ref->getMethod( 'render' );
		$m->setAccessible( true );
		$m->invoke( $widget );
		return (string) ob_get_clean();
	}

	private function settings_for( string $display_mode, float $per_view_mobile ): array {
		return array(
			'display_mode'    => $display_mode,
			'shape'           => 'soft',
			'snap'            => 'none',
			'show_arrows'     => 'no',
			'show_progress'   => 'no',
			'show_cart'       => 'yes',
			'show_sale_badge' => 'yes',
			'mouse_drag'      => 'yes',
			'direction'       => 'ltr',
			'per_view'        => 4,
			'per_view_tablet' => 3,
			'per_view_mobile' => $per_view_mobile,
			'gap'             => 20,
			'card_height'     => 320,
			'eyebrow'         => '',
			'headline'        => '',
			'headline_mute'   => '',
			'source'          => 'all',
			'limit'           => 10,
			'orderby'         => 'date',
			'order'           => 'DESC',
		);
	}
}
