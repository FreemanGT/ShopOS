<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Results_Query;
use PHPUnit\Framework\TestCase;

/**
 * The results-page gating + id planning (pure) and the Shop Filters facet-feed
 * filter. The pre_get_posts / posts_search adapters touch WP_Query and are
 * integration / live QA.
 *
 * @covers \Freeman\Core\Modules\Search\Results_Query
 */
final class SearchResultsQueryTest extends TestCase {

	public function test_should_handle_true_for_frontend_product_search_with_data(): void {
		$this->assertTrue( Results_Query::should_handle( false, true, true, 'hoodie', true ) );
	}

	/**
	 * @dataProvider non_handling_cases
	 */
	public function test_should_handle_false_cases( array $args ): void {
		$this->assertFalse( Results_Query::should_handle( ...$args ) );
	}

	public static function non_handling_cases(): array {
		// [is_admin, is_main, is_product, term, has_data]
		return array(
			'admin'                  => array( array( true, true, true, 'x', true ) ),
			'not main query'         => array( array( false, false, true, 'x', true ) ),
			'not a product query'    => array( array( false, true, false, 'x', true ) ),
			'no term in request'     => array( array( false, true, true, '   ', true ) ),
			'empty index'            => array( array( false, true, true, 'x', false ) ),
		);
	}

	public function test_plan_ids_forces_zero_on_no_match(): void {
		// Engine authoritative once the index has data: no match = zero results.
		$this->assertSame( array( 0 ), Results_Query::plan_ids( array() ) );
	}

	public function test_plan_ids_preserves_engine_order(): void {
		$this->assertSame( array( 9, 4, 7 ), Results_Query::plan_ids( array( 9, 4, 7 ) ) );
	}

	public function test_grid_max_pages_passes_through_for_uncontrolled_widgets(): void {
		// Curated sources (and malformed settings) keep the main query's count.
		$rq = new Results_Query();

		$this->assertSame( 7, $rq->grid_max_pages( 7, array( 'source' => 'featured', 'display_mode' => 'grid' ) ) );
		$this->assertSame( 7, $rq->grid_max_pages( 7, null ) );
	}

	public function test_constrain_slider_query_ignores_non_current_query_widgets(): void {
		// A genuinely-curated "featured" ProductSlider on a search page must be left
		// alone — and a passthrough with no settings must not blow up.
		$rq   = new Results_Query();
		$args = array( 'limit' => 12 );

		$this->assertSame( $args, $rq->constrain_slider_query( $args, array( 'source' => 'featured' ) ) );
		$this->assertSame( $args, $rq->constrain_slider_query( $args, array() ) );
	}

	/**
	 * @dataProvider slider_constraint_cases
	 */
	public function test_should_constrain_slider( string $source, bool $is_grid, bool $expected ): void {
		$this->assertSame( $expected, Results_Query::should_constrain_slider( $source, $is_grid ) );
	}

	public static function slider_constraint_cases(): array {
		// [source, is_grid, expected]
		return array(
			'current query slider'  => array( 'current_query', false, true ),
			'current query grid'    => array( 'current_query', true, true ),
			'all products grid'     => array( 'all', true, true ),    // the Elementor archive results grid.
			'all products slider'   => array( 'all', false, false ),  // decorative carousel — leave alone.
			'featured grid'         => array( 'featured', true, false ),
			'category grid'         => array( 'category', true, false ),
			'no source'             => array( '', false, false ),
		);
	}

	public function test_neutralize_search_passes_through_when_inactive(): void {
		// A fresh instance hasn't taken over any query → native search SQL is kept.
		// (The active-path neutralisation, has_data() and supply_engine_ids() touch
		// WP_Query / $wpdb and are integration / live QA — Search_Repository is final
		// and the read path isn't stubbable, matching the Shop Filters boundary.)
		$rq = new Results_Query();

		$this->assertSame( ' AND x', $rq->neutralize_search( ' AND x', null ) );
	}

	public function test_pre_supply_engine_ids_respects_prior_listener(): void {
		// A non-null $pre means another listener already supplied ids — return it
		// untouched without consulting the (final, unstubbable) repo. The
		// has_data()/engine branches are integration / live QA, like supply_engine_ids().
		$rq = new Results_Query();

		$this->assertSame( array( 5, 6 ), $rq->pre_supply_engine_ids( array( 5, 6 ), 'hoodie' ) );
	}
}
