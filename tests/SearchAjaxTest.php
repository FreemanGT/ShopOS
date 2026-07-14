<?php
declare(strict_types=1);

use ShopOS\Core\Modules\Search\Ajax;
use PHPUnit\Framework\TestCase;

/**
 * The dropdown endpoint's pure min-chars gate. The client already debounces on
 * minChars; the server-side check keeps a crafted short request from running
 * the broad LIKE fallback scan. The handler itself (nonce, rate limit, JSON,
 * live WC products) is integration / live QA.
 *
 * @covers \ShopOS\Core\Modules\Search\Ajax
 */
final class SearchAjaxTest extends TestCase {

	/**
	 * @dataProvider min_chars_cases
	 */
	public function test_term_meets_min_chars( string $term, int $min, bool $expected ): void {
		$this->assertSame( $expected, Ajax::term_meets_min_chars( $term, $min ) );
	}

	public static function min_chars_cases(): array {
		return array(
			'empty term fails'              => array( '', 2, false ),
			'whitespace-only fails'         => array( '   ', 2, false ),
			'one char below min fails'      => array( 'a', 2, false ),
			'exactly min passes'            => array( 'ab', 2, true ),
			'trimmed length is what counts' => array( '  ab  ', 2, true ),
			'hebrew counts characters'      => array( 'אב', 2, true ),
			'hebrew one char fails min 2'   => array( 'א', 2, false ),
			'min clamps to at least 1'      => array( 'a', 0, true ),
			'empty fails even at min 0'     => array( '', 0, false ),
		);
	}
}
