<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Stubs — guarded so this file runs in isolation and alongside the other
// ProductSlider tests (PHPUnit loads every test file into one process).
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

// Minimal WP_Query stub — `posts` + `max_num_pages` plus the two
// product-archive predicates the widget reads off the query object.
if ( ! class_exists( 'WP_Query' ) ) {
	eval( 'class WP_Query {
		public $posts = array();
		public $max_num_pages = 1;
		private $pta = false;
		private $tax = false;
		private $search = false;
		public function __construct( $posts = array(), $pta = false, $tax = false, $max = 1, $search = false ) {
			$this->posts = $posts; $this->pta = (bool) $pta; $this->tax = (bool) $tax; $this->max_num_pages = (int) $max; $this->search = (bool) $search;
		}
		public function is_post_type_archive( $type = "" ) { return $this->pta; }
		public function is_tax( $tax = "" ) { return $this->tax; }
		public function is_search() { return $this->search; }
	}' );
}

use ShopOS\Core\Modules\ProductSlider\Widget;

/**
 * #1 grid-cap fix (1.14.2): the `current_query` grid path must read the
 * canonical main query ($wp_the_query), not the global $wp_query, because an
 * Elementor archive template swaps $wp_query for its own loop while the widget
 * renders — nulling the is_shop()/is_tax() tags and the posts we need. These
 * cover the three extracted seams; archive detection against a real Elementor
 * render is live-QA on the store.
 *
 * @covers \ShopOS\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderArchiveQueryTest extends TestCase {

	private function invoke( string $method, array $args ) {
		$widget = new Widget();
		$ref    = new \ReflectionMethod( Widget::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $widget, $args );
	}

	protected function setUp(): void {
		parent::setUp();
		unset(
			$GLOBALS['fr_page_type'],
			$GLOBALS['fr_wc_visible'],
			$GLOBALS['fr_wc_products'],
			$GLOBALS['wp_the_query'],
			$GLOBALS['wp_query']
		);
	}

	public function test_main_query_prefers_wp_the_query(): void {
		$canonical               = new \WP_Query( array( 1 ) );
		$swapped                 = new \WP_Query( array( 9 ) );
		$GLOBALS['wp_the_query'] = $canonical;
		$GLOBALS['wp_query']     = $swapped;
		$this->assertSame( $canonical, $this->invoke( 'main_query', array() ) );
	}

	public function test_main_query_falls_back_to_wp_query_when_no_the_query(): void {
		$swapped             = new \WP_Query( array( 9 ) );
		$GLOBALS['wp_query'] = $swapped;
		$this->assertSame( $swapped, $this->invoke( 'main_query', array() ) );
	}

	public function test_is_product_archive_true_via_query_post_type_archive(): void {
		$q = new \WP_Query( array(), true, false );
		$this->assertTrue( $this->invoke( 'is_product_archive', array( $q ) ) );
	}

	public function test_is_product_archive_true_via_query_taxonomy(): void {
		$q = new \WP_Query( array(), false, true );
		$this->assertTrue( $this->invoke( 'is_product_archive', array( $q ) ) );
	}

	public function test_is_product_archive_true_via_conditional_tag(): void {
		$GLOBALS['fr_page_type'] = 'shop'; // is_shop() → true even though the query says no.
		$q                       = new \WP_Query( array(), false, false );
		$this->assertTrue( $this->invoke( 'is_product_archive', array( $q ) ) );
	}

	public function test_is_product_archive_false_when_not_archive(): void {
		$q = new \WP_Query( array(), false, false );
		$this->assertFalse( $this->invoke( 'is_product_archive', array( $q ) ) );
	}

	public function test_collect_grid_returns_all_visible_uncapped(): void {
		$GLOBALS['fr_wc_products'] = array(
			1 => new \WC_Product( 1 ),
			2 => new \WC_Product( 2 ),
			3 => new \WC_Product( 3 ),
		);
		$q   = new \WP_Query( array( 1, 2, 3 ) );
		$out = $this->invoke( 'collect_archive_products', array( $q, true, 2 ) ); // grid ignores the cap.
		$this->assertCount( 3, $out );
	}

	public function test_collect_slider_caps_at_limit(): void {
		$GLOBALS['fr_wc_products'] = array(
			1 => new \WC_Product( 1 ),
			2 => new \WC_Product( 2 ),
			3 => new \WC_Product( 3 ),
			4 => new \WC_Product( 4 ),
		);
		$q   = new \WP_Query( array( 1, 2, 3, 4 ) );
		$out = $this->invoke( 'collect_archive_products', array( $q, false, 2 ) );
		$this->assertCount( 2, $out );
	}

	public function test_collect_drops_non_product_entries(): void {
		// wc_get_product yields a product for 1 and 3 but a non-product for 2;
		// collect must drop the invalid entry. (The harness's shared WC_Product
		// stub is hard-wired visible, so is_visible() filtering itself stays
		// live-QA — that branch is unchanged from the prior implementation.)
		$GLOBALS['fr_wc_products'] = array(
			1 => new \WC_Product( 1 ),
			2 => false,
			3 => new \WC_Product( 3 ),
		);
		$q   = new \WP_Query( array( 1, 2, 3 ) );
		$out = $this->invoke( 'collect_archive_products', array( $q, true, 99 ) );
		$this->assertCount( 2, $out );
	}

	/**
	 * The current_query grid reads the archive query for a genuine archive, but a
	 * product *search* rendered through an archive template must fall through to
	 * the wc_get_products() path (where the search-results filter constrains it) —
	 * else the unconstrained main query renders the whole catalog (the arba4 bug).
	 *
	 * @dataProvider archive_routing_cases
	 */
	public function test_should_use_archive( bool $main_is_search, bool $is_product_archive, bool $expected ): void {
		$this->assertSame( $expected, Widget::should_use_archive( $main_is_search, $is_product_archive ) );
	}

	public static function archive_routing_cases(): array {
		// [main_is_search, is_product_archive, expected]
		return array(
			'genuine archive'        => array( false, true, true ),
			'search on archive tmpl' => array( true, true, false ),  // route through wc_get_products() so search constrains it.
			'search, not archive'    => array( true, false, false ),
			'neither'                => array( false, false, false ),
		);
	}
}
