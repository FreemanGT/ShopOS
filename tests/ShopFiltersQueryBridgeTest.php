<?php
declare(strict_types=1);

use ShopOS\Core\Modules\ShopFilters\Query;
use PHPUnit\Framework\TestCase;

/**
 * The query bridge: pure tax_query construction (AND across facets, OR within)
 * and the flag-gated hook wiring.
 *
 * @covers \ShopOS\Core\Modules\ShopFilters\Query
 */
final class ShopFiltersQueryBridgeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks'] = array();
	}

	public function test_single_facet_builds_one_in_clause_without_relation(): void {
		$tq = Query::tax_query_for( array( 'pa_color' => array( 'red', 'blue' ) ) );

		$this->assertCount( 1, $tq );
		$this->assertArrayNotHasKey( 'relation', $tq );
		$this->assertSame( 'pa_color', $tq[0]['taxonomy'] );
		$this->assertSame( 'slug', $tq[0]['field'] );
		$this->assertSame( array( 'red', 'blue' ), $tq[0]['terms'] );
		$this->assertSame( 'IN', $tq[0]['operator'] );
	}

	public function test_multiple_facets_are_anded(): void {
		$tq = Query::tax_query_for(
			array(
				'pa_color' => array( 'red' ),
				'pa_size'  => array( 'm', 'l' ),
			)
		);

		$this->assertSame( 'AND', $tq['relation'] );
		$this->assertSame( 'pa_color', $tq[0]['taxonomy'] );
		$this->assertSame( 'pa_size', $tq[1]['taxonomy'] );
		$this->assertSame( array( 'm', 'l' ), $tq[1]['terms'] );
	}

	public function test_empty_or_blank_selection_yields_empty_tax_query(): void {
		$this->assertSame( array(), Query::tax_query_for( array() ) );
		$this->assertSame( array(), Query::tax_query_for( array( 'pa_color' => array( '', '' ) ) ) );
		$this->assertSame( array(), Query::tax_query_for( array( '' => array( 'red' ) ) ) );
	}

	public function test_slugs_deduped_within_facet(): void {
		$tq = Query::tax_query_for( array( 'pa_color' => array( 'red', 'red', 'blue' ) ) );

		$this->assertSame( array( 'red', 'blue' ), $tq[0]['terms'] );
	}

	public function test_register_wires_the_wc_filter_and_search_hook(): void {
		( new Query() )->register();

		$this->assertNotFalse( has_filter( 'woocommerce_product_query_tax_query' ) );
		$this->assertNotFalse( has_action( 'pre_get_posts' ) );
		$this->assertNotFalse( has_filter( 'shopos_core/product_slider/query_args' ) );
	}

	/**
	 * @dataProvider listing_cases
	 */
	public function test_is_product_listing_query_matrix( bool $wc, bool $singular, $post_type, bool $archive, bool $expected ): void {
		$this->assertSame( $expected, Query::is_product_listing_query( $wc, $singular, $post_type, $archive ) );
	}

	public static function listing_cases(): array {
		// [wc_conditionals, is_singular, post_type, query_says_product_archive, expected]
		return array(
			'wc conditionals'                => array( true, false, '', false, true ),
			'query archive predicates'       => array( false, false, '', true, true ),
			'product post_type string'       => array( false, false, 'product', false, true ), // the Elementor archive main query.
			'product in post_type array'     => array( false, false, array( 'product', 'page' ), false, true ),
			'singular vetoes everything'     => array( true, true, 'product', true, false ), // a filter_* param must not 404 a product page.
			'plain page'                     => array( false, false, 'page', false, false ),
			'no signals'                     => array( false, false, '', false, false ),
		);
	}

	/**
	 * @dataProvider slider_constraint_cases
	 */
	public function test_should_constrain_slider( string $source, bool $is_grid, bool $expected ): void {
		$this->assertSame( $expected, Query::should_constrain_slider( $source, $is_grid ) );
	}

	public static function slider_constraint_cases(): array {
		// [source, is_grid, expected] — deliberately the Search module's matrix.
		return array(
			'current query slider' => array( 'current_query', false, true ),
			'current query grid'   => array( 'current_query', true, true ),
			'all products grid'    => array( 'all', true, true ),
			'all products slider'  => array( 'all', false, false ),
			'featured grid'        => array( 'featured', true, false ),
			'no source'            => array( '', false, false ),
		);
	}

	public function test_compose_include_sets_filtered_ids_when_no_existing(): void {
		$this->assertSame( array( 5, 9 ), Query::compose_include( null, array( 5, 9 ) ) );
		$this->assertSame( array( 5, 9 ), Query::compose_include( array(), array( 5, 9 ) ) );
	}

	public function test_compose_include_empty_filtered_forces_zero(): void {
		$this->assertSame( array( 0 ), Query::compose_include( null, array() ) );
	}

	public function test_compose_include_intersects_preserving_existing_order(): void {
		// The existing include is the Search engine's relevance ranking — the
		// intersect must keep ITS order, not the filtered set's.
		$this->assertSame(
			array( 9, 4 ),
			Query::compose_include( array( 9, 4, 7 ), array( 4, 5, 9 ) )
		);
	}

	public function test_compose_include_empty_intersection_forces_zero(): void {
		$this->assertSame( array( 0 ), Query::compose_include( array( 1, 2 ), array( 3, 4 ) ) );
	}

	public function test_compose_include_respects_search_no_match_sentinel(): void {
		// Search found nothing ([0]); the filters must not resurrect products.
		$this->assertSame( array( 0 ), Query::compose_include( array( 0 ), array( 3, 4 ) ) );
	}

	public function test_constrain_slider_query_ignores_curated_widgets(): void {
		$q    = new Query();
		$args = array( 'limit' => 12 );

		$this->assertSame( $args, $q->constrain_slider_query( $args, array( 'source' => 'featured', 'display_mode' => 'grid' ) ) );
		$this->assertSame( $args, $q->constrain_slider_query( $args, array() ) );
	}

	public function test_intersect_id_sets_ands_across_facets(): void {
		// AND across facets: only ids present in every set survive.
		$ids = Query::intersect_id_sets(
			array(
				array( 1, 2, 3, 4 ),
				array( 2, 3, 5 ),
				array( 3, 2 ),
			)
		);
		sort( $ids );

		$this->assertSame( array( 2, 3 ), $ids );
	}

	public function test_intersect_id_sets_empty_facet_short_circuits(): void {
		// A facet that resolved to no in-stock products yields no matches.
		$this->assertSame(
			array(),
			Query::intersect_id_sets( array( array( 1, 2, 3 ), array() ) )
		);
	}

	public function test_intersect_id_sets_single_facet_dedupes(): void {
		$this->assertSame(
			array( 1, 2 ),
			Query::intersect_id_sets( array( array( 1, 2, 2, 1 ) ) )
		);
	}

	public function test_intersect_id_sets_no_facets_is_empty(): void {
		$this->assertSame( array(), Query::intersect_id_sets( array() ) );
	}

	public function test_filter_posts_to_ids_keeps_allowed_in_order(): void {
		$posts = array(
			(object) array( 'ID' => 10 ),
			(object) array( 'ID' => 11 ),
			(object) array( 'ID' => 12 ),
		);

		$kept = Query::filter_posts_to_ids( $posts, array( 12, 10 ) );

		$this->assertCount( 2, $kept );
		$this->assertSame( 10, $kept[0]->ID ); // original order preserved.
		$this->assertSame( 12, $kept[1]->ID );
	}

	public function test_filter_posts_to_ids_empty_allowlist_drops_all(): void {
		$posts = array( (object) array( 'ID' => 10 ) );

		$this->assertSame( array(), Query::filter_posts_to_ids( $posts, array() ) );
	}

	/**
	 * A $wpdb stand-in counting the reads the in-stock resolution + has_rows probe
	 * make, so the memo test can assert how many actually ran.
	 */
	private function counting_wpdb(): object {
		return new class() {
			public $prefix        = 'wp_';
			public $get_col_calls = 0;
			public $get_var_calls = 0;
			public function prepare( $sql, ...$args ) {
				return $sql;
			}
			public function get_col( $sql ) {
				$this->get_col_calls++;
				return array( '11' );
			}
			public function get_var( $sql ) {
				$this->get_var_calls++;
				return '1';
			}
		};
	}

	/**
	 * A7: the same selection resolves to in-stock ids up to three times per
	 * request (main-query post__in, slider constraint, search enforcement); the
	 * per-request memo collapses identical resolutions to one index read, and the
	 * has_rows() probe (SELECT 1 LIMIT 1, memoized in index_has_data) runs once.
	 */
	public function test_instock_resolution_is_memoized_per_request(): void {
		$orig_wpdb = $GLOBALS['wpdb'] ?? null;
		$orig_get  = $_GET;
		$orig_by   = $GLOBALS['fr_term_by'] ?? null;

		$GLOBALS['wpdb']        = $this->counting_wpdb();
		$_GET                   = array( 'filter_pa_color' => 'red' );
		$GLOBALS['fr_term_by']  = array( 'pa_color:slug:red' => (object) array( 'term_id' => 11 ) );

		$q        = new Query();
		$settings = array( 'source' => 'current_query', 'display_mode' => 'slider' );

		$first  = $q->constrain_slider_query( array( 'include' => array( 11, 12 ) ), $settings );
		$second = $q->constrain_slider_query( array( 'include' => array( 11, 12 ) ), $settings );

		// Correctness: the widget's include is intersected down to the in-stock id.
		$this->assertSame( array( 11 ), $first['include'] );
		$this->assertSame( $first, $second );

		// Efficiency: the second identical resolution reuses the memo, and the
		// has_rows() existence probe runs at most once.
		$this->assertSame( 1, $GLOBALS['wpdb']->get_col_calls, 'in-stock resolution must memoize' );
		$this->assertLessThanOrEqual( 1, $GLOBALS['wpdb']->get_var_calls, 'has_rows() must not re-probe' );

		$GLOBALS['wpdb']       = $orig_wpdb;
		$_GET                  = $orig_get;
		$GLOBALS['fr_term_by'] = $orig_by;
	}
}
