<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Reuse the rich WC_Product stub from snapshots so this file works regardless
// of which other test loaded WC_Product first. The snapshot stub hardcodes
// get_id() to 42 and is_visible() to true — fine for these tests, which
// assert sort-order via the captured sequence of wc_get_product() lookups
// (see $GLOBALS['fr_wc_get_product_calls'], populated by the bootstrap stub),
// not via the returned objects' get_id().
require_once __DIR__ . '/snapshots/__fixtures__/wc_product_stub.php';

if ( ! class_exists( '\\Elementor\\Widget_Base' ) ) {
	eval( 'namespace Elementor; class Widget_Base { public $fr_test_settings = array(); public function __construct( $data = array(), $args = null ) {} public function get_settings_for_display() { return $this->fr_test_settings; } }' );
}
if ( ! class_exists( '\\Elementor\\Controls_Manager' ) ) {
	eval( 'namespace Elementor; class Controls_Manager { const TAB_CONTENT = "content"; const TAB_STYLE = "style"; const TEXT = "text"; const NUMBER = "number"; const SELECT = "select"; const SWITCHER = "switcher"; const COLOR = "color"; const SLIDER = "slider"; const SELECT2 = "select2"; const CHOOSE = "choose"; const HEADING = "heading"; }' );
}
if ( ! class_exists( '\\Elementor\\Group_Control_Typography' ) ) {
	eval( 'namespace Elementor; class Group_Control_Typography { public static function get_type() { return "typography"; } }' );
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
	function is_singular( $type = '' ) { return false; }
}
if ( ! function_exists( 'is_tax' ) ) {
	function is_tax( $tax ) { return false; }
}

use ShopOS\Core\Modules\ProductSlider\Widget;

/**
 * Covers the popularity / rating / price orderby in
 * Widget::fetch_products_by_meta_orderby() — the two-pass query that bypasses
 * WC's INNER JOIN on the sort meta so products with no sales / reviews / price
 * still appear and the sort actually applies.
 *
 * As of 1.21.28 (audit C1) the per-product sort value is read from
 * `wc_product_meta_lookup` (total_sales / average_rating / min_price) in a
 * single indexed query instead of an `update_meta_cache()` whole-catalog
 * postmeta prime. These tests drive a $wpdb double (per the SearchRepository
 * pattern): the double answers the lookup SELECT from a per-test [ id => value ]
 * map, omitting ids not in the map to exercise the no-lookup-row default-0 path.
 *
 * Sort order is asserted via the sequence of wc_get_product() lookups captured
 * by the bootstrap stub. The returned objects' get_id() is meaningless (snapshot
 * stub hardcodes 42), but the IDs PASSED to wc_get_product reflect the in-PHP
 * sort exactly.
 *
 * @covers \ShopOS\Core\Modules\ProductSlider\Widget
 */
final class ProductSliderPopularityOrderbyTest extends TestCase {

	/** @var object|null Original $wpdb to restore. */
	private $original_wpdb;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks']                  = array();
		$GLOBALS['fr_wc_get_products_args']   = null;
		$GLOBALS['fr_wc_get_products_return'] = array();
		$GLOBALS['fr_wc_products']            = array();
		$GLOBALS['fr_wc_get_product_return']  = new \WC_Product();
		$GLOBALS['fr_wc_get_product_calls']   = array();
		$this->original_wpdb                  = $GLOBALS['wpdb'] ?? null;
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	/**
	 * A $wpdb stand-in that answers the wc_product_meta_lookup SELECT from the
	 * given [ id => value ] map. Ids absent from the map return no row — the
	 * production code then defaults them to 0.0. Captures the last SQL (so tests
	 * can assert the selected column) and counts get_results() calls (so "one
	 * indexed query" is verifiable).
	 */
	private function install_wpdb( array $values ): object {
		$db = new class() {
			public $prefix           = 'wp_';
			public $values           = array();
			public $last_sql         = '';
			public $get_results_runs = 0;
			private $captured_ids    = array();
			public function prepare( $sql, $args = array() ) {
				$this->last_sql     = $sql;
				$this->captured_ids = is_array( $args ) ? $args : array_slice( func_get_args(), 1 );
				return $sql;
			}
			public function get_results( $sql ) {
				$this->get_results_runs++;
				$rows = array();
				foreach ( $this->captured_ids as $id ) {
					$id = (int) $id;
					if ( array_key_exists( $id, $this->values ) ) {
						$rows[] = (object) array( 'product_id' => $id, 'sort_value' => $this->values[ $id ] );
					}
				}
				return $rows;
			}
		};
		$db->values      = $values;
		$GLOBALS['wpdb'] = $db;
		return $db;
	}

	/** Invoke the private fetch_products() with the given settings. */
	private function fetch( array $s ): array {
		$widget = new Widget();
		$ref    = new \ReflectionClass( $widget );
		$m      = $ref->getMethod( 'fetch_products' );
		$m->setAccessible( true );
		return $m->invoke( $widget, $s );
	}

	public function test_popularity_orderby_calls_wc_get_products_with_ids_return_and_date_orderby(): void {
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$this->install_wpdb( array( 100 => 5, 200 => 50, 300 => 20 ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'popularity',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		// fetch_products_by_meta_orderby() rewrites these args before calling
		// wc_get_products() — confirms we bypassed WC's INNER JOIN path.
		$captured = $GLOBALS['fr_wc_get_products_args'];
		$this->assertSame( 'ids', $captured['return'] );
		$this->assertSame( 'date', $captured['orderby'] );
		$this->assertSame( -1, $captured['limit'] );
	}

	public function test_popularity_orderby_sorts_by_total_sales_descending(): void {
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$db = $this->install_wpdb( array( 100 => 5, 200 => 50, 300 => 20 ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'popularity',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		// Lookup order = sort order (desc by total_sales): 200 → 300 → 100.
		$this->assertSame( array( 200, 300, 100 ), array_slice( $GLOBALS['fr_wc_get_product_calls'], 0, 3 ) );
		$this->assertStringContainsString( 'total_sales', $db->last_sql );
		$this->assertSame( 1, $db->get_results_runs, 'sort values must come from a single indexed query' );
	}

	public function test_popularity_orderby_includes_products_without_total_sales_row(): void {
		// Only product 200 has a lookup value. Products missing from the lookup
		// (no row) must still reach the ranking at 0, not be dropped.
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$this->install_wpdb( array( 200 => 10 ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'popularity',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		$lookups = $GLOBALS['fr_wc_get_product_calls'];
		$this->assertCount( 3, $lookups, 'all eligible products must be considered, not just those with a lookup row' );
		$this->assertSame( 200, $lookups[0], 'product with sales must lead' );
		// 100 and 300 are tied at 0 — order between them is implementation-defined,
		// but both must appear in the lookup set after 200.
		$this->assertEqualsCanonicalizing( array( 100, 300 ), array_slice( $lookups, 1 ) );
	}

	public function test_rating_orderby_sorts_by_average_rating_descending(): void {
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$db = $this->install_wpdb( array( 100 => 3.5, 200 => 4.8, 300 => 4.2 ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'rating',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		$this->assertSame( array( 200, 300, 100 ), array_slice( $GLOBALS['fr_wc_get_product_calls'], 0, 3 ) );
		$this->assertStringContainsString( 'average_rating', $db->last_sql );
	}

	public function test_price_orderby_sorts_by_min_price(): void {
		// Prices 29.99 / 9.99 / 19.99 — DESC expects 100, 300, 200.
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$db = $this->install_wpdb( array( 100 => '29.99', 200 => '9.99', 300 => '19.99' ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'price',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		// Confirms `price` now uses the meta-orderby bypass (return=ids path)
		// and the canonical min_price lookup column (not _price postmeta).
		$this->assertSame( 'ids', $GLOBALS['fr_wc_get_products_args']['return'] );
		$this->assertStringContainsString( 'min_price', $db->last_sql );
		$this->assertSame( array( 100, 300, 200 ), array_slice( $GLOBALS['fr_wc_get_product_calls'], 0, 3 ) );
	}

	public function test_price_orderby_includes_products_without_price_row(): void {
		// Product 200 priced; 100 and 300 have no lookup price ("price on
		// request"). They must still appear, sorting as 0.
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$this->install_wpdb( array( 200 => '49.00' ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'price',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		$lookups = $GLOBALS['fr_wc_get_product_calls'];
		$this->assertCount( 3, $lookups );
		$this->assertSame( 200, $lookups[0], 'priced product leads on DESC' );
	}

	public function test_price_orderby_treats_null_lookup_value_as_zero(): void {
		// A lookup row exists but min_price is NULL (unpriced) — must default to
		// 0 and still rank, not corrupt the sort.
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200 );
		$this->install_wpdb( array( 100 => '15.00', 200 => null ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'price',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		$lookups = $GLOBALS['fr_wc_get_product_calls'];
		$this->assertCount( 2, $lookups );
		$this->assertSame( 100, $lookups[0], 'priced product leads; NULL sorts as 0' );
	}

	public function test_popularity_orderby_asc_reverses_sort(): void {
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300 );
		$this->install_wpdb( array( 100 => 5, 200 => 50, 300 => 20 ) );

		$this->fetch( array(
			'limit'   => 12,
			'orderby' => 'popularity',
			'order'   => 'ASC',
			'source'  => 'all',
		) );

		$this->assertSame( array( 100, 300, 200 ), array_slice( $GLOBALS['fr_wc_get_product_calls'], 0, 3 ) );
	}

	public function test_popularity_orderby_respects_limit(): void {
		$GLOBALS['fr_wc_get_products_return'] = array( 100, 200, 300, 400, 500 );
		$this->install_wpdb( array( 100 => 1, 200 => 5, 300 => 3, 400 => 4, 500 => 2 ) );

		$out = $this->fetch( array(
			'limit'   => 2,
			'orderby' => 'popularity',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		// Returned product count clamped to limit; the FIRST two lookups must be
		// the top-2 by total_sales (200 then 400). Implementation overshoots to
		// $limit*2 to absorb visibility drops, so up to 4 lookups can occur,
		// but the LEAD pair is the assertion.
		$this->assertCount( 2, $out );
		$this->assertSame( array( 200, 400 ), array_slice( $GLOBALS['fr_wc_get_product_calls'], 0, 2 ) );
	}

	public function test_non_meta_orderby_does_not_use_bypass_path(): void {
		// orderby=date must keep the original wc_get_products(return=objects) path.
		$GLOBALS['fr_wc_get_products_return'] = array( new \WC_Product(), new \WC_Product() );

		$out = $this->fetch( array(
			'limit'   => 12,
			'orderby' => 'date',
			'order'   => 'DESC',
			'source'  => 'all',
		) );

		$captured = $GLOBALS['fr_wc_get_products_args'];
		$this->assertSame( 'objects', $captured['return'], 'date orderby must not switch to ids-only return' );
		$this->assertSame( 'date', $captured['orderby'] );
		$this->assertCount( 2, $out );
	}
}
