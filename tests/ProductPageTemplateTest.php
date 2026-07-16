<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ProductPage\Module;
use ShopOS\Core\Modules\ProductPage\Template_Loader;
use PHPUnit\Framework\TestCase;

/**
 * Template takeover seams: the template resolution ladder (theme override →
 * module copy), the non-product pass-through, the WC-defaults detach +
 * gallery-image-size filter on takeover, the additional-information
 * <details> markup, and the body-class scope hook. The actual render
 * (Elementor precedence, gallery, sticky bar) is integration — live-QA.
 *
 * @covers \ShopOS\Core\Modules\ProductPage\Template_Loader
 */
final class ProductPageTemplateTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset( $GLOBALS['fr_page_type'], $GLOBALS['fr_locate_template'], $GLOBALS['fr_stylesheet_dir'] );
	}

	private function loader(): Template_Loader {
		return new Template_Loader( new Module() );
	}

	public function test_template_file_exists_on_disk(): void {
		$this->assertFileExists( SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php' );
	}

	public function test_takeover_passes_through_off_product_pages(): void {
		$this->assertSame( '/theme/page.php', $this->loader()->maybe_takeover( '/theme/page.php' ) );
	}

	public function test_takeover_swaps_the_template_on_product_pages(): void {
		$GLOBALS['fr_page_type'] = 'product';

		$expected = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
		$this->assertSame( $expected, $this->loader()->maybe_takeover( '/theme/single.php' ) );
	}

	public function test_takeover_prefers_a_theme_override(): void {
		$GLOBALS['fr_page_type'] = 'product';
		// The override must be a readable file — reuse a real fixture path.
		$override                        = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
		$GLOBALS['fr_locate_template'] = $override;

		$this->assertSame( $override, $this->loader()->maybe_takeover( '/theme/single.php' ) );
	}

	public function test_takeover_detaches_wc_defaults_from_after_summary(): void {
		$GLOBALS['fr_page_type'] = 'product';
		add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		add_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
		add_action( 'woocommerce_after_single_product_summary', 'third_party_callback', 25 );

		$this->loader()->maybe_takeover( '/theme/single.php' );

		$remaining = array_map(
			static function ( $h ) {
				return $h['cb'];
			},
			$GLOBALS['fr_hooks']['woocommerce_after_single_product_summary']
		);
		$this->assertSame( array( 'third_party_callback' ), $remaining, 'only WC defaults detach; third parties stay' );
	}

	public function test_trust_html_is_empty_until_a_label_is_written(): void {
		$this->assertSame( '', Template_Loader::trust_html(), 'both trust labels default empty = no line' );
	}

	public function test_trust_html_renders_only_filled_items(): void {
		$GLOBALS['fr_opts']['shopos_core_product_page_label_trust_shipping'] = 'משלוח מהיר עד 3 ימי עסקים';

		$html = Template_Loader::trust_html();

		$this->assertStringContainsString( 'shopos-ui-pdp__trust', $html );
		$this->assertStringContainsString( 'משלוח מהיר עד 3 ימי עסקים', $html );
		$this->assertSame( 1, substr_count( $html, 'shopos-ui-pdp__trust-item' ), 'the blank returns item is skipped' );
	}

	public function test_trust_html_renders_both_items_and_escapes(): void {
		$GLOBALS['fr_opts']['shopos_core_product_page_label_trust_shipping'] = 'Fast shipping';
		$GLOBALS['fr_opts']['shopos_core_product_page_label_trust_returns']  = 'Returns <b>30</b> days';

		$html = Template_Loader::trust_html();

		$this->assertSame( 2, substr_count( $html, 'shopos-ui-pdp__trust-item' ) );
		$this->assertStringContainsString( 'Returns &lt;b&gt;30&lt;/b&gt; days', $html );
	}

	public function test_body_class_scopes_only_product_pages(): void {
		$this->assertSame( array( 'a' ), $this->loader()->body_class( array( 'a' ) ) );

		$GLOBALS['fr_page_type'] = 'product';
		$this->assertContains( 'shopos-ui-pdp-active', $this->loader()->body_class( array( 'a' ) ) );
	}

	public function test_gallery_supports_keeps_zoom_and_drops_slider_and_lightbox(): void {
		// The theme declares all three; the module keeps zoom, removes the
		// slider (flexslider fights the editorial grid) and the lightbox.
		$GLOBALS['fr_theme_supports'] = array(
			'wc-product-gallery-zoom',
			'wc-product-gallery-lightbox',
			'wc-product-gallery-slider',
		);

		$this->loader()->add_gallery_supports();

		$this->assertContains( 'wc-product-gallery-zoom', $GLOBALS['fr_theme_supports'] );
		$this->assertNotContains( 'wc-product-gallery-slider', $GLOBALS['fr_theme_supports'] );
		$this->assertNotContains( 'wc-product-gallery-lightbox', $GLOBALS['fr_theme_supports'] );
	}

	public function test_takeover_adds_the_gallery_image_size_filter(): void {
		$GLOBALS['fr_page_type'] = 'product';

		$loader = $this->loader();
		$loader->maybe_takeover( '/theme/single.php' );

		// With the slider support removed, WC drops non-main gallery images
		// to the ~100px gallery_thumbnail — the filter lifts them back.
		$this->assertArrayHasKey( 'woocommerce_gallery_image_size', $GLOBALS['fr_hooks'] );
		$this->assertSame( 'woocommerce_single', $loader->gallery_image_size() );
	}

	public function test_takeover_off_product_pages_adds_no_gallery_size_filter(): void {
		$this->loader()->maybe_takeover( '/theme/page.php' );

		$this->assertArrayNotHasKey( 'woocommerce_gallery_image_size', $GLOBALS['fr_hooks'] );
	}

	public function test_additional_information_html_is_a_collapsed_details(): void {
		$table = '<table class="woocommerce-product-attributes"><tr><th>צבע</th><td>שחור</td></tr></table>';

		$html = Template_Loader::additional_information_html( 'Additional information', $table );

		$this->assertStringStartsWith( '<details class="shopos-ui-pdp__addl-info">', $html );
		$this->assertStringNotContainsString( '<details class="shopos-ui-pdp__addl-info" open', $html, 'collapsed by default — a tab, not just open' );
		$this->assertStringContainsString( '<summary class="shopos-ui-pdp__addl-info-summary">', $html );
		$this->assertStringContainsString( '<span class="shopos-ui-pdp__addl-info-title">Additional information</span>', $html );
		$this->assertStringNotContainsString( '<h2', $html, 'no heading element — an Elementor kit h2 rule outranked the 9.2 title' );
		$this->assertStringContainsString( 'shopos-ui-pdp__addl-info-chevron', $html );
		$this->assertStringContainsString( '<div class="shopos-ui-pdp__addl-info-body">' . $table . '</div>', $html, 'WC table passes through unescaped' );
	}

	public function test_additional_information_html_escapes_the_heading(): void {
		$html = Template_Loader::additional_information_html( 'Specs <b>now</b>', '<table></table>' );

		$this->assertStringContainsString( 'Specs &lt;b&gt;now&lt;/b&gt;', $html );
	}

	public function test_button_color_css_is_empty_for_no_or_invalid_hex(): void {
		$this->assertSame( '', Template_Loader::button_color_css( '' ) );
		$this->assertSame( '', Template_Loader::button_color_css( '   ' ) );
		$this->assertSame( '', Template_Loader::button_color_css( 'red' ) );
		$this->assertSame( '', Template_Loader::button_color_css( '#12g' ), 'non-hex digit rejected' );
		$this->assertSame( '', Template_Loader::button_color_css( '123456' ), 'missing # rejected' );
	}

	public function test_button_color_css_drives_vs_primary_and_sticky_override(): void {
		$css = Template_Loader::button_color_css( '#0A7C66' );

		// Drives VS's own custom property (its action buttons read it)…
		$this->assertStringContainsString( '.shopos-ui-pdp .shopos-buy-box{--shopos-primary:#0A7C66', $css );
		$this->assertStringContainsString( '--shopos-primary-hover:#0A7C66', $css );
		$this->assertStringContainsString( '--shopos-primary-active:#0A7C66', $css );
		// …plus the explicit sticky-bar override, whose red is a hardcoded
		// literal in VS rather than the var.
		$this->assertStringContainsString( '.shopos-sticky-bar__buy{background:#0A7C66 !important}', $css );
	}

	// ---- §11.4 row 4: the flag-gated theme-copy rung in template_file() ----

	/** The real repo theme dir — its templates/woo/single-product.php IS the fixture. */
	private function theme_dir(): string {
		return realpath( __DIR__ . '/../shopos-theme' );
	}

	private function log_entries( string $level ): array {
		$log = $GLOBALS['fr_opts']['shopos_core_log'] ?? array();
		return array_values(
			array_filter(
				is_array( $log ) ? $log : array(),
				static function ( $entry ) use ( $level ) {
					return ( $entry['level'] ?? '' ) === $level;
				}
			)
		);
	}

	public function test_theme_copy_exists_on_disk(): void {
		$this->assertFileExists( $this->theme_dir() . '/templates/woo/single-product.php' );
	}

	public function test_flag_off_ignores_a_present_theme_copy(): void {
		$GLOBALS['fr_page_type']      = 'product';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();

		$expected = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
		$this->assertSame( $expected, $this->loader()->template_file(), 'file presence alone must never change the render (§11.3)' );
		$this->assertSame( array(), $this->log_entries( 'info' ), 'flag off logs nothing' );
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'flag off warns nothing' );
	}

	public function test_flag_on_resolves_the_theme_copy(): void {
		$GLOBALS['fr_page_type']      = 'product';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_pdp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/single-product.php',
			$this->loader()->template_file()
		);
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'fonts on ⇒ no Ruling-10 warning' );
	}

	public function test_flag_on_without_a_theme_copy_falls_back_to_the_module_copy_and_logs_once(): void {
		$GLOBALS['fr_page_type']      = 'product';
		$GLOBALS['fr_stylesheet_dir'] = __DIR__; // A real dir with no templates/woo/ — a theme predating the template.
		$GLOBALS['fr_opts']['shopos_core_theme_template_pdp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$loader   = $this->loader();
		$expected = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';

		// template_file() runs more than once per request (maybe_takeover +
		// body_class): both calls resolve, exactly one info row is written.
		$this->assertSame( $expected, $loader->template_file(), 'Blueprint-import-onto-old-theme falls back (§11.3)' );
		$this->assertSame( $expected, $loader->template_file() );
		$this->assertCount( 1, $this->log_entries( 'info' ), 'fallback logs once per request, not per call' );
	}

	public function test_public_override_beats_the_theme_copy_flag_on(): void {
		$GLOBALS['fr_page_type']      = 'product';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_pdp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';
		// The override must be a readable file — reuse a real fixture path.
		$override                      = SHOPOS_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
		$GLOBALS['fr_locate_template'] = $override;

		$this->assertSame( $override, $this->loader()->template_file(), 'the shopos/product_page/ public contract stays the top rung (Hard Rule #2)' );
	}

	public function test_flag_on_with_fonts_off_warns_once_and_still_resolves(): void {
		$GLOBALS['fr_page_type']      = 'product';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_pdp_enabled'] = '1';

		$loader = $this->loader();

		$this->assertSame( $this->theme_dir() . '/templates/woo/single-product.php', $loader->template_file() );
		$loader->template_file();

		$warnings = $this->log_entries( 'warning' );
		$this->assertCount( 1, $warnings, 'Ruling-10 warning fires once per request, not per call' );
		$this->assertStringContainsString( 'fonts_selfhost', $warnings[0]['message'] );
	}
}
