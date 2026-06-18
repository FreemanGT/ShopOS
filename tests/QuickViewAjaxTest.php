<?php
declare(strict_types=1);

use Freeman\Core\Modules\QuickView\Ajax;
use Freeman\Core\Modules\QuickView\Module;
use PHPUnit\Framework\TestCase;

if ( ! class_exists( '\\WC_Product' ) ) {
	eval( 'class WC_Product { private $id; public function __construct( $id ) { $this->id = $id; } public function get_id() { return $this->id; } public function is_visible() { return ! isset( $GLOBALS["fr_wc_visible"][ $this->id ] ) || (bool) $GLOBALS["fr_wc_visible"][ $this->id ]; } }' );
}
if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post = null ) {
		$id = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		return $GLOBALS['fr_post_status'][ $id ] ?? false;
	}
}

/**
 * Quick View AJAX endpoint: registration wiring + the is_viewable() guard
 * that keeps the public endpoint from serving drafts, hidden products, or
 * arbitrary post ids. The JSON-echoing handler itself is live-QA.
 *
 * @covers \Freeman\Core\Modules\QuickView\Ajax
 */
final class QuickViewAjaxTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks']       = array();
		$GLOBALS['fr_post_status'] = array();
	}

	private function ajax(): Ajax {
		return new Ajax( new Module() );
	}

	/**
	 * Visibility-controllable product. An anonymous subclass (rather than
	 * the shared eval stub) because two WC_Product stub shapes exist across
	 * the suite (the per-test eval and the snapshot fixture) and whichever
	 * loads first wins — this stays order-independent.
	 */
	private function product( bool $visible ): \WC_Product {
		return new class( $visible ) extends \WC_Product {
			private $fr_visible;
			public function __construct( $visible ) {
				$this->fr_visible = (bool) $visible;
			}
			public function is_visible() {
				return $this->fr_visible;
			}
		};
	}

	public function test_register_wires_both_public_actions(): void {
		$this->ajax()->register();

		$this->assertArrayHasKey( 'wp_ajax_freeman_core_quick_view_product', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'wp_ajax_nopriv_freeman_core_quick_view_product', $GLOBALS['fr_hooks'] );
	}

	public function test_is_viewable_rejects_a_non_product(): void {
		$this->assertFalse( $this->ajax()->is_viewable( false, 42 ) );
		$this->assertFalse( $this->ajax()->is_viewable( null, 42 ) );
		$this->assertFalse( $this->ajax()->is_viewable( new \stdClass(), 42 ) );
	}

	public function test_is_viewable_rejects_unpublished_status(): void {
		$GLOBALS['fr_post_status'][42] = 'draft';

		$this->assertFalse( $this->ajax()->is_viewable( $this->product( true ), 42 ) );
	}

	public function test_is_viewable_rejects_catalog_hidden_products(): void {
		$GLOBALS['fr_post_status'][42] = 'publish';

		$this->assertFalse( $this->ajax()->is_viewable( $this->product( false ), 42 ) );
	}

	public function test_is_viewable_accepts_a_published_visible_product(): void {
		$GLOBALS['fr_post_status'][42] = 'publish';

		$this->assertTrue( $this->ajax()->is_viewable( $this->product( true ), 42 ) );
	}
}
