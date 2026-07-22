<?php
declare(strict_types=1);

use ShopOS\Core\Modules\QuickView\Frontend;
use ShopOS\Core\Modules\QuickView\Module;
use PHPUnit\Framework\TestCase;

if ( ! class_exists( '\\WC_Product' ) ) {
	eval( 'class WC_Product { private $id; public function __construct( $id ) { $this->id = $id; } public function get_id() { return $this->id; } public function is_visible() { return ! isset( $GLOBALS["fr_wc_visible"][ $this->id ] ) || (bool) $GLOBALS["fr_wc_visible"][ $this->id ]; } }' );
}

/**
 * Quick View storefront seams: trigger markup, drawer shell markup, the
 * footer-shell gating (only after a trigger rendered), the show_trigger
 * filter, label overrides, and the localized JS payload shape.
 *
 * @covers \ShopOS\Core\Modules\QuickView\Frontend
 */
final class QuickViewFrontendTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset( $GLOBALS['product'] );
	}

	private function frontend(): Frontend {
		return new Frontend( new Module() );
	}

	public function test_trigger_html_shape(): void {
		$html = $this->frontend()->trigger_html( 42 );

		$this->assertStringContainsString( 'class="shopos-qv-trigger shopos-ui-iconbtn shopos-ui-iconbtn--sm"', $html );
		$this->assertStringContainsString( 'data-shopos-qv="42"', $html );
		$this->assertStringContainsString( 'type="button"', $html );
		$this->assertStringContainsString( 'aria-label="Quick view"', $html );
	}

	public function test_trigger_label_is_overridable_blank_falls_back(): void {
		$GLOBALS['fr_opts']['shopos_core_quick_view_label_trigger'] = 'תצוגה מהירה';
		$this->assertStringContainsString( 'aria-label="תצוגה מהירה"', $this->frontend()->trigger_html( 7 ) );

		$GLOBALS['fr_opts']['shopos_core_quick_view_label_trigger'] = '   ';
		$this->assertStringContainsString( 'aria-label="Quick view"', $this->frontend()->trigger_html( 7 ) );
	}

	public function test_drawer_shell_shape(): void {
		$html = $this->frontend()->drawer_shell_html();

		$this->assertStringContainsString( 'id="shopos-quick-view"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
		$this->assertStringContainsString( 'role="dialog"', $html );
		$this->assertStringContainsString( 'data-shopos-qv-content', $html );
		$this->assertStringContainsString( 'data-shopos-qv-close', $html );
		// VariationSwatches' isInsideModal() heuristic matches
		// [class*="quick-view"] — the wrapper class must keep the substring.
		$this->assertStringContainsString( 'quick-view', $html );
	}

	public function test_footer_shell_only_renders_after_a_trigger(): void {
		$frontend = $this->frontend();

		ob_start();
		$frontend->render_drawer_shell();
		$this->assertSame( '', ob_get_clean(), 'no trigger rendered → no shell' );

		$GLOBALS['product'] = new \WC_Product( 42 );
		ob_start();
		$frontend->render_trigger();
		$trigger = ob_get_clean();
		$this->assertStringContainsString( 'data-shopos-qv="42"', $trigger );

		ob_start();
		$frontend->render_drawer_shell();
		$this->assertStringContainsString( 'id="shopos-quick-view"', ob_get_clean() );
	}

	public function test_render_trigger_skips_without_a_loop_product(): void {
		ob_start();
		$this->frontend()->render_trigger();
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_show_trigger_filter_suppresses_the_trigger(): void {
		$GLOBALS['product'] = new \WC_Product( 42 );
		add_filter( 'shopos_core/quick_view/show_trigger', '__return_false' );

		$frontend = $this->frontend();
		ob_start();
		$frontend->render_trigger();
		$this->assertSame( '', ob_get_clean(), 'filter false → no trigger' );

		ob_start();
		$frontend->render_drawer_shell();
		$this->assertSame( '', ob_get_clean(), 'suppressed trigger must not arm the footer shell' );
	}

	public function test_localized_payload_shape(): void {
		$payload = $this->frontend()->localized_payload();

		$this->assertSame( 'https://example.test/wp-admin/admin-ajax.php', $payload['ajaxUrl'] );
		$this->assertSame( 'shopos_core_quick_view_product', $payload['action'] );
		$this->assertNotEmpty( $payload['nonce'] );
		$this->assertSame( 'Loading…', $payload['labels']['loading'] );
		$this->assertNotEmpty( $payload['labels']['error'] );
	}
}
