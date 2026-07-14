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
		unset( $GLOBALS['fr_page_type'], $GLOBALS['fr_locate_template'] );
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

		$this->assertStringContainsString( 'fm-pdp__trust', $html );
		$this->assertStringContainsString( 'משלוח מהיר עד 3 ימי עסקים', $html );
		$this->assertSame( 1, substr_count( $html, 'fm-pdp__trust-item' ), 'the blank returns item is skipped' );
	}

	public function test_trust_html_renders_both_items_and_escapes(): void {
		$GLOBALS['fr_opts']['shopos_core_product_page_label_trust_shipping'] = 'Fast shipping';
		$GLOBALS['fr_opts']['shopos_core_product_page_label_trust_returns']  = 'Returns <b>30</b> days';

		$html = Template_Loader::trust_html();

		$this->assertSame( 2, substr_count( $html, 'fm-pdp__trust-item' ) );
		$this->assertStringContainsString( 'Returns &lt;b&gt;30&lt;/b&gt; days', $html );
	}

	public function test_body_class_scopes_only_product_pages(): void {
		$this->assertSame( array( 'a' ), $this->loader()->body_class( array( 'a' ) ) );

		$GLOBALS['fr_page_type'] = 'product';
		$this->assertContains( 'fm-pdp-active', $this->loader()->body_class( array( 'a' ) ) );
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

		$this->assertStringStartsWith( '<details class="fm-pdp__addl-info">', $html );
		$this->assertStringNotContainsString( '<details class="fm-pdp__addl-info" open', $html, 'collapsed by default — a tab, not just open' );
		$this->assertStringContainsString( '<summary class="fm-pdp__addl-info-summary">', $html );
		$this->assertStringContainsString( '<span class="fm-pdp__addl-info-title">Additional information</span>', $html );
		$this->assertStringNotContainsString( '<h2', $html, 'no heading element — an Elementor kit h2 rule outranked the 9.2 title' );
		$this->assertStringContainsString( 'fm-pdp__addl-info-chevron', $html );
		$this->assertStringContainsString( '<div class="fm-pdp__addl-info-body">' . $table . '</div>', $html, 'WC table passes through unescaped' );
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
		$this->assertStringContainsString( '.fm-pdp .shopos-buy-box{--shopos-primary:#0A7C66', $css );
		$this->assertStringContainsString( '--shopos-primary-hover:#0A7C66', $css );
		$this->assertStringContainsString( '--shopos-primary-active:#0A7C66', $css );
		// …plus the explicit sticky-bar override, whose red is a hardcoded
		// literal in VS rather than the var.
		$this->assertStringContainsString( '.shopos-sticky-bar__buy{background:#0A7C66 !important}', $css );
	}
}
