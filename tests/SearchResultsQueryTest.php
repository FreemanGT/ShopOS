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
		$this->assertTrue( Results_Query::should_handle( false, true, true, true, 'hoodie', true ) );
	}

	/**
	 * @dataProvider non_handling_cases
	 */
	public function test_should_handle_false_cases( array $args ): void {
		$this->assertFalse( Results_Query::should_handle( ...$args ) );
	}

	public static function non_handling_cases(): array {
		// [is_admin, is_main, is_search, is_product, term, has_data]
		return array(
			'admin'              => array( array( true, true, true, true, 'x', true ) ),
			'not main query'     => array( array( false, false, true, true, 'x', true ) ),
			'not a search'       => array( array( false, true, false, true, 'x', true ) ),
			'not product search' => array( array( false, true, true, false, 'x', true ) ),
			'blank term'         => array( array( false, true, true, true, '   ', true ) ),
			'empty index'        => array( array( false, true, true, true, 'x', false ) ),
		);
	}

	public function test_plan_ids_forces_zero_on_no_match(): void {
		// Engine authoritative once the index has data: no match = zero results.
		$this->assertSame( array( 0 ), Results_Query::plan_ids( array() ) );
	}

	public function test_plan_ids_preserves_engine_order(): void {
		$this->assertSame( array( 9, 4, 7 ), Results_Query::plan_ids( array( 9, 4, 7 ) ) );
	}

	public function test_order_posts_by_ids_filters_and_reorders(): void {
		// Native query fetched many; engine matched 3, in its own rank order.
		$posts = array(
			(object) array( 'ID' => 10 ),
			(object) array( 'ID' => 20 ),
			(object) array( 'ID' => 30 ),
			(object) array( 'ID' => 40 ),
		);
		$out = Results_Query::order_posts_by_ids( $posts, array( 30, 10 ) );

		$this->assertSame( array( 30, 10 ), array_map( static fn ( $p ) => $p->ID, $out ) );
	}

	public function test_order_posts_by_ids_empty_ids_drops_everything(): void {
		// Engine no-match → empty grid (engine authoritative once indexed).
		$posts = array( (object) array( 'ID' => 10 ) );

		$this->assertSame( array(), Results_Query::order_posts_by_ids( $posts, array() ) );
	}

	public function test_neutralize_search_passes_through_when_inactive(): void {
		// A fresh instance hasn't taken over any query → native search SQL is kept.
		// (The active-path neutralisation, has_data() and supply_engine_ids() touch
		// WP_Query / $wpdb and are integration / live QA — Search_Repository is final
		// and the read path isn't stubbable, matching the Shop Filters boundary.)
		$rq = new Results_Query();

		$this->assertSame( ' AND x', $rq->neutralize_search( ' AND x', null ) );
	}
}
