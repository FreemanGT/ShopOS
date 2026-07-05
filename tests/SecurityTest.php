<?php
declare(strict_types=1);

use Freeman\Core\Core\Security;
use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_transients'] = array();
	}

	public function test_rate_limit_allows_within_budget(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertTrue(
				Security::rate_limit( 'unit_test', 3, 60 ),
				"request #$i should be allowed within the budget"
			);
		}
	}

	public function test_rate_limit_rejects_over_budget(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		for ( $i = 0; $i < 3; $i++ ) {
			Security::rate_limit( 'unit_test', 3, 60 );
		}
		$this->assertFalse(
			Security::rate_limit( 'unit_test', 3, 60 ),
			'fourth request should be rejected'
		);
	}

	public function test_rate_limit_bucket_isolation(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		for ( $i = 0; $i < 3; $i++ ) {
			Security::rate_limit( 'bucket_a', 3, 60 );
		}
		// Different bucket must have its own counter.
		$this->assertTrue( Security::rate_limit( 'bucket_b', 3, 60 ) );
	}

	public function test_rate_limit_defaults_filter_overrides_max(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		add_filter(
			'freeman_core/rate_limit_defaults',
			static function ( $defaults ) {
				$defaults['max'] = 1;
				return $defaults;
			}
		);
		try {
			// The call site passes max=5, but the filter forces max=1, so the
			// second request is rejected.
			$this->assertTrue( Security::rate_limit( 'filtered_bucket', 5, 60 ) );
			$this->assertFalse( Security::rate_limit( 'filtered_bucket', 5, 60 ) );
		} finally {
			unset( $GLOBALS['fr_hooks']['freeman_core/rate_limit_defaults'] );
		}
	}

	public function test_rate_limit_defaults_filter_receives_bucket(): void {
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		$seen = null;
		add_filter(
			'freeman_core/rate_limit_defaults',
			static function ( $defaults, $bucket ) use ( &$seen ) {
				$seen = $bucket;
				return $defaults;
			},
			10,
			2
		);
		try {
			Security::rate_limit( 'my_bucket', 5, 60 );
			$this->assertSame( 'my_bucket', $seen );
		} finally {
			unset( $GLOBALS['fr_hooks']['freeman_core/rate_limit_defaults'] );
		}
	}

	public function test_sanitize_recursive_handles_nested(): void {
		$in  = array( 'a' => 'x', 'b' => array( 'c' => 'y' ), 'd' => 42 );
		$out = Security::sanitize_recursive( $in );
		$this->assertSame( array( 'a' => 'x', 'b' => array( 'c' => 'y' ), 'd' => '42' ), $out );
	}

	public function test_sanitize_recursive_drops_non_scalars_leaves(): void {
		$in  = array( 'obj' => new \stdClass() );
		$out = Security::sanitize_recursive( $in );
		// Objects collapse to '' per Security::sanitize_recursive contract.
		$this->assertSame( array( 'obj' => '' ), $out );
	}
}
