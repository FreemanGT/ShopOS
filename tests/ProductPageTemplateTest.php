<?php
declare(strict_types=1);

use Freeman\Core\Modules\ProductPage\Module;
use Freeman\Core\Modules\ProductPage\Template_Loader;
use PHPUnit\Framework\TestCase;

/**
 * Template takeover seams: the template resolution ladder (theme override →
 * module copy), the non-product pass-through, the WC-defaults detach on
 * takeover, and the body-class scope hook. The actual render (Elementor
 * precedence, gallery, sticky bar) is integration — live-QA.
 *
 * @covers \Freeman\Core\Modules\ProductPage\Template_Loader
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
		$this->assertFileExists( FREEMAN_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php' );
	}

	public function test_takeover_passes_through_off_product_pages(): void {
		$this->assertSame( '/theme/page.php', $this->loader()->maybe_takeover( '/theme/page.php' ) );
	}

	public function test_takeover_swaps_the_template_on_product_pages(): void {
		$GLOBALS['fr_page_type'] = 'product';

		$expected = FREEMAN_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
		$this->assertSame( $expected, $this->loader()->maybe_takeover( '/theme/single.php' ) );
	}

	public function test_takeover_prefers_a_theme_override(): void {
		$GLOBALS['fr_page_type'] = 'product';
		// The override must be a readable file — reuse a real fixture path.
		$override                        = FREEMAN_CORE_PATH . 'src/Modules/ProductPage/templates/single-product.php';
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
		$GLOBALS['fr_opts']['freeman_core_product_page_label_trust_shipping'] = 'משלוח מהיר עד 3 ימי עסקים';

		$html = Template_Loader::trust_html();

		$this->assertStringContainsString( 'fm-pdp__trust', $html );
		$this->assertStringContainsString( 'משלוח מהיר עד 3 ימי עסקים', $html );
		$this->assertSame( 1, substr_count( $html, 'fm-pdp__trust-item' ), 'the blank returns item is skipped' );
	}

	public function test_trust_html_renders_both_items_and_escapes(): void {
		$GLOBALS['fr_opts']['freeman_core_product_page_label_trust_shipping'] = 'Fast shipping';
		$GLOBALS['fr_opts']['freeman_core_product_page_label_trust_returns']  = 'Returns <b>30</b> days';

		$html = Template_Loader::trust_html();

		$this->assertSame( 2, substr_count( $html, 'fm-pdp__trust-item' ) );
		$this->assertStringContainsString( 'Returns &lt;b&gt;30&lt;/b&gt; days', $html );
	}

	public function test_body_class_scopes_only_product_pages(): void {
		$this->assertSame( array( 'a' ), $this->loader()->body_class( array( 'a' ) ) );

		$GLOBALS['fr_page_type'] = 'product';
		$this->assertContains( 'fm-pdp-active', $this->loader()->body_class( array( 'a' ) ) );
	}
}
