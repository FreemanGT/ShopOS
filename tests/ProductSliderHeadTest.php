<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Stubs — match the shapes used in the other ProductSlider tests, guarded so
// every file stays runnable in isolation (PHPUnit loads them into one process).
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

use ShopOS\Core\Modules\ProductSlider\Widget;

/**
 * #2 (1.14.3): the `.cs-head` header block was always emitted, so its shared
 * `border-bottom` showed as a stray line even with no eyebrow / headline /
 * arrows (the common grid-mode case). It must only render when it has content.
 *
 * @covers \ShopOS\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderHeadTest extends TestCase {

	private function render( array $overrides ): string {
		$settings = array_merge(
			array(
				'display_mode'  => 'grid',
				'shape'         => 'soft',
				'snap'          => 'none',
				'show_arrows'   => 'no',
				'show_progress' => 'no',
				'direction'     => 'ltr',
				'per_view'      => 4,
				'per_view_mobile' => 4.0,
				'gap'           => 20,
				'card_height'   => 320,
				'eyebrow'       => '',
				'headline'      => '',
				'headline_mute' => '',
				'source'        => 'all',
				'limit'         => 10,
				'orderby'       => 'date',
				'order'         => 'DESC',
			),
			$overrides
		);

		$widget                   = new Widget();
		$widget->fr_test_settings = $settings;
		ob_start();
		$ref = new \ReflectionMethod( Widget::class, 'render' );
		$ref->setAccessible( true );
		$ref->invoke( $widget );
		return (string) ob_get_clean();
	}

	public function test_head_omitted_when_no_eyebrow_headline_or_arrows(): void {
		$this->assertStringNotContainsString( 'class="cs-head"', $this->render( array() ) );
	}

	public function test_head_rendered_when_headline_set(): void {
		$out = $this->render( array( 'headline' => 'ShopOSFeatured' ) );
		$this->assertStringContainsString( 'class="cs-head"', $out );
		$this->assertStringContainsString( 'ShopOSFeatured', $out );
	}
}
