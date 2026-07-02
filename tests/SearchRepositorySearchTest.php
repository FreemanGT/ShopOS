<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Search_Repository;
use PHPUnit\Framework\TestCase;

/**
 * The search() read method's pure guards: the blank-term short-circuit, the
 * effective-limit clamp (the pre-1.21.19 "unlimited" -1 mapped to 4294967295,
 * which let one broad Hebrew search hydrate the whole catalogue downstream),
 * and the per-request memo that collapses the results page's repeated reads
 * (main query + slider constraint + facet feed) to a single ranked query. The
 * live ranked query (esc_like + $wpdb->prepare + get_col) is integration /
 * live QA; the SQL algebra it composes is covered by SearchQueryEngineTest.
 *
 * @covers \Freeman\Core\Modules\Search\Search_Repository
 */
final class SearchRepositorySearchTest extends TestCase {

	/**
	 * @var object|null Original $wpdb stub to restore.
	 */
	private $original_wpdb;

	protected function setUp(): void {
		parent::setUp();
		Search_Repository::reset_runtime_cache();
		$this->original_wpdb = $GLOBALS['wpdb'] ?? null;
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		Search_Repository::reset_runtime_cache();
		parent::tearDown();
	}

	/**
	 * A $wpdb stand-in that counts reads so the memo tests can assert how many
	 * queries actually ran.
	 */
	private function counting_wpdb(): object {
		return new class() {
			public $prefix        = 'wp_';
			public $get_col_calls = 0;
			public $get_var_calls = 0;
			public function esc_like( $text ) {
				return addcslashes( (string) $text, '_%\\' );
			}
			public function prepare( $sql, ...$args ) {
				return $sql;
			}
			public function get_col( $sql ) {
				$this->get_col_calls++;
				return array( '7', '3' );
			}
			public function get_var( $sql ) {
				$this->get_var_calls++;
				return '5';
			}
		};
	}

	public function test_blank_term_short_circuits_to_empty(): void {
		// A blank / whitespace term returns [] before touching $wpdb.
		$repo = new Search_Repository();

		$this->assertSame( array(), $repo->search( '' ) );
		$this->assertSame( array(), $repo->search( '   ' ) );
	}

	/**
	 * @dataProvider limit_cases
	 */
	public function test_effective_limit_clamps( int $requested, int $expected ): void {
		$this->assertSame( $expected, Search_Repository::effective_limit( $requested ) );
	}

	public static function limit_cases(): array {
		return array(
			'unlimited sentinel -1'   => array( -1, Search_Repository::MAX_RESULTS ),
			'zero'                    => array( 0, Search_Repository::MAX_RESULTS ),
			'small stays'             => array( 10, 10 ),
			'dropdown max stays'      => array( 20, 20 ),
			'exactly the cap stays'   => array( Search_Repository::MAX_RESULTS, Search_Repository::MAX_RESULTS ),
			'oversized clamps'        => array( 999999, Search_Repository::MAX_RESULTS ),
		);
	}

	public function test_search_memoizes_identical_reads(): void {
		$GLOBALS['wpdb'] = $this->counting_wpdb();
		$repo            = new Search_Repository();

		$first  = $repo->search( 'shirt', -1, true );
		$second = $repo->search( 'shirt', -1, true );

		$this->assertSame( array( 7, 3 ), $first );
		$this->assertSame( $first, $second );
		$this->assertSame( 1, $GLOBALS['wpdb']->get_col_calls, 'identical reads must collapse to one query' );

		// A different stock flag is a different read.
		$repo->search( 'shirt', -1, false );
		$this->assertSame( 2, $GLOBALS['wpdb']->get_col_calls );
	}

	public function test_reset_runtime_cache_forces_requery(): void {
		$GLOBALS['wpdb'] = $this->counting_wpdb();
		$repo            = new Search_Repository();

		$repo->search( 'shirt', 10, true );
		Search_Repository::reset_runtime_cache();
		$repo->search( 'shirt', 10, true );

		$this->assertSame( 2, $GLOBALS['wpdb']->get_col_calls );
	}

	public function test_count_and_has_data_memoize(): void {
		$GLOBALS['wpdb'] = $this->counting_wpdb();
		$repo            = new Search_Repository();

		$this->assertSame( 5, $repo->count_indexed_products() );
		$this->assertTrue( $repo->has_data() );
		$this->assertTrue( $repo->has_data() );
		$this->assertSame( 1, $GLOBALS['wpdb']->get_var_calls, 'has_data() must not re-COUNT per call' );
	}
}
