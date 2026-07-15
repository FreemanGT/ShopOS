<?php
declare(strict_types=1);

use ShopOS\Core\Core\Settings_Hub;
use PHPUnit\Framework\TestCase;

/**
 * The additive field-API extension (1.28.0): range / media / typography-select
 * / multiselect control types. Verifies each new render + sanitize branch AND
 * that the pre-existing types render byte-identically unaffected.
 *
 * @covers \ShopOS\Core\Core\Settings_Hub
 */
final class SettingsHubFieldApiTest extends TestCase {

	private Settings_Hub $hub;
	private $module;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		$this->hub    = new Settings_Hub( new \ShopOS\Core\Core\Module_Registry() );
		$this->module = new \ShopOS\Core\Modules\VariationSwatches\Module();
	}

	private function render( string $key, array $def ): string {
		$method = ( new ReflectionClass( $this->hub ) )->getMethod( 'render_field' );
		$method->setAccessible( true );
		ob_start();
		$method->invoke( $this->hub, $this->module, $key, $def );
		return (string) ob_get_clean();
	}

	private function sanitize( array $def, $value ) {
		$method = ( new ReflectionClass( $this->hub ) )->getMethod( 'sanitizer_for' );
		$method->setAccessible( true );
		$callable = $method->invoke( $this->hub, $def );
		return $callable( $value );
	}

	/* ---------------- range ---------------- */

	public function test_range_render_emits_slider_with_bounds(): void {
		$html = $this->render(
			'demo_range',
			array( 'type' => 'range', 'min' => 2, 'max' => 20, 'step' => 2, 'unit' => 'px', 'default' => 8 )
		);
		$this->assertStringContainsString( 'type="range"', $html );
		$this->assertStringContainsString( 'min="2"', $html );
		$this->assertStringContainsString( 'max="20"', $html );
		$this->assertStringContainsString( 'step="2"', $html );
		$this->assertStringContainsString( '<output', $html );
	}

	public function test_range_sanitizer_coerces_and_clamps(): void {
		$def = array( 'type' => 'range', 'min' => 0, 'max' => 100 );
		$this->assertSame( 50, $this->sanitize( $def, '50' ) );   // numeric string -> int
		$this->assertSame( 100, $this->sanitize( $def, 250 ) );   // clamp high
		$this->assertSame( 0, $this->sanitize( $def, -30 ) );     // clamp low
		$this->assertSame( 0, $this->sanitize( $def, 'abc' ) );   // non-numeric
	}

	/* ---------------- media ---------------- */

	public function test_media_render_emits_hidden_id_and_picker_controls(): void {
		$GLOBALS['fr_attachment_image_url'] = 'https://example.test/x.jpg';
		$html = $this->render( 'demo_media', array( 'type' => 'media', 'default' => 5 ) );
		$this->assertStringContainsString( 'type="hidden"', $html );
		$this->assertStringContainsString( 'value="5"', $html );
		$this->assertStringContainsString( 'shopos-image-field-id', $html );
		$this->assertStringContainsString( 'shopos-image-field-pick', $html );
		$this->assertStringContainsString( 'shopos-image-field-clear', $html );
		$this->assertStringContainsString( 'https://example.test/x.jpg', $html );
	}

	public function test_media_sanitizer_is_absint(): void {
		$def = array( 'type' => 'media' );
		$this->assertSame( 42, $this->sanitize( $def, '42' ) );
		$this->assertSame( 7, $this->sanitize( $def, -7 ) );
		$this->assertSame( 0, $this->sanitize( $def, 'nope' ) );
	}

	/* ---------------- typography-select ---------------- */

	public function test_typography_select_render_previews_fonts_and_marks_selected(): void {
		$def = array(
			'type'    => 'typography-select',
			'choices' => array( 'Georgia, serif' => 'Georgia', 'Arial, sans-serif' => 'Arial' ),
			'default' => 'Georgia, serif',
		);
		update_option( $this->module->option_name( 'demo_type' ), 'Arial, sans-serif' );
		$html = $this->render( 'demo_type', $def );
		$this->assertStringContainsString( '<select', $html );
		$this->assertStringContainsString( 'font-family:Arial, sans-serif', $html );
		$this->assertStringContainsString( 'selected="selected"', $html );
	}

	public function test_typography_select_sanitizer_whitelists_choices(): void {
		$def = array(
			'type'    => 'typography-select',
			'choices' => array( 'Georgia, serif' => 'Georgia' ),
			'default' => 'Georgia, serif',
		);
		$this->assertSame( 'Georgia, serif', $this->sanitize( $def, 'Georgia, serif' ) );
		// An unknown value (e.g. an injection attempt) falls back to the default.
		$this->assertSame( 'Georgia, serif', $this->sanitize( $def, 'evil</style>, x' ) );
	}

	/* ---------------- multiselect ---------------- */

	public function test_multiselect_render_is_multiple_and_marks_selected(): void {
		$def = array(
			'type'    => 'multiselect',
			'choices' => array( 'a' => 'Alpha', 'b' => 'Beta', 'c' => 'Gamma' ),
			'default' => array(),
		);
		update_option( $this->module->option_name( 'demo_multi' ), array( 'a', 'c' ) );
		$html = $this->render( 'demo_multi', $def );
		$this->assertStringContainsString( 'multiple', $html );
		$this->assertStringContainsString( '[]"', $html ); // name attribute ends with []
		$this->assertSame( 2, substr_count( $html, 'selected="selected"' ) );
	}

	public function test_multiselect_sanitizer_whitelists_and_returns_array(): void {
		$def = array(
			'type'    => 'multiselect',
			'choices' => array( 'a' => 'Alpha', 'b' => 'Beta' ),
		);
		$this->assertSame( array( 'a', 'b' ), $this->sanitize( $def, array( 'a', 'z', 'b' ) ) );
		$this->assertSame( array(), $this->sanitize( $def, 'not-an-array' ) );
	}

	/* ---------------- additivity guard ---------------- */

	public function test_preexisting_types_render_unchanged(): void {
		$this->assertStringContainsString(
			'type="text"',
			$this->render( 'demo_text', array( 'type' => 'text' ) )
		);
		$this->assertStringContainsString(
			'shopos-color-field',
			$this->render( 'demo_color', array( 'type' => 'color' ) )
		);
		$this->assertStringContainsString(
			'type="number"',
			$this->render( 'demo_number', array( 'type' => 'number' ) )
		);
	}
}
