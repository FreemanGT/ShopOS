<?php
declare(strict_types=1);

use Freeman\Core\Modules\Search\Search_Repository;
use PHPUnit\Framework\TestCase;

/**
 * The search() read method's pure guard. The live ranked query (esc_like +
 * $wpdb->prepare + get_col) is integration / live QA; the SQL algebra it
 * composes is covered by SearchQueryEngineTest.
 *
 * @covers \Freeman\Core\Modules\Search\Search_Repository
 */
final class SearchRepositorySearchTest extends TestCase {

	public function test_blank_term_short_circuits_to_empty(): void {
		// A blank / whitespace term returns [] before touching $wpdb.
		$repo = new Search_Repository();

		$this->assertSame( array(), $repo->search( '' ) );
		$this->assertSame( array(), $repo->search( '   ' ) );
	}
}
