<?php
declare(strict_types=1);

use ShopOS\Core\Core\Logger;
use ShopOS\Core\Modules\ProductFeed\Generator;
use PHPUnit\Framework\TestCase;

/**
 * Guards the 1.21.27 ProductFeed silent-failure fix: promote_feed() must gate
 * a successful run on the main feed rename, log on failure, and treat the size
 * sidecar as best-effort. Exercises the pure promotion seam with real files
 * under the tmp uploads dir (see tests/bootstrap.php wp_upload_dir stub).
 */
final class ProductFeedPromoteTest extends TestCase {

	private Generator $gen;

	protected function setUp(): void {
		parent::setUp();
		$this->gen = new Generator();
		if ( ! is_dir( $this->gen->feed_dir() ) ) {
			mkdir( $this->gen->feed_dir(), 0777, true );
		}
		$this->cleanup();
		Logger::clear();
	}

	protected function tearDown(): void {
		$this->cleanup();
		parent::tearDown();
	}

	private function cleanup(): void {
		foreach (
			array(
				$this->gen->feed_file(),
				$this->gen->feed_file() . '.tmp',
				$this->gen->feed_file() . '.tmp.size',
				$this->gen->size_file(),
			) as $f
		) {
			if ( is_file( $f ) ) {
				unlink( $f );
			}
		}
	}

	/** Invoke the private promote_feed() seam. */
	private function promote( string $tmp ): bool {
		$m = new ReflectionMethod( Generator::class, 'promote_feed' );
		$m->setAccessible( true );
		return (bool) $m->invoke( $this->gen, $tmp );
	}

	public function test_promote_feed_moves_files_and_returns_true(): void {
		$tmp = $this->gen->feed_file() . '.tmp';
		file_put_contents( $tmp, 'GZDATA' );
		file_put_contents( $tmp . '.size', '1234' );

		$this->assertTrue( $this->promote( $tmp ) );

		$this->assertFileExists( $this->gen->feed_file() );
		$this->assertFileExists( $this->gen->size_file() );
		$this->assertSame( 'GZDATA', file_get_contents( $this->gen->feed_file() ) );
		$this->assertSame( '1234', file_get_contents( $this->gen->size_file() ) );
		// tmp files consumed by the rename.
		$this->assertFileDoesNotExist( $tmp );
		$this->assertFileDoesNotExist( $tmp . '.size' );
	}

	public function test_promote_feed_returns_false_and_logs_error_when_source_missing(): void {
		$tmp = $this->gen->feed_file() . '.tmp';
		// No tmp file written — rename() of a missing source returns false.
		$this->assertFalse( $this->promote( $tmp ) );

		$this->assertFileDoesNotExist( $this->gen->feed_file() );
		$entries = Logger::entries();
		$last    = end( $entries );
		$this->assertSame( 'error', $last['level'] );
		$this->assertStringContainsString( 'failed to promote', $last['message'] );
	}

	public function test_promote_feed_survives_missing_size_sidecar(): void {
		$tmp = $this->gen->feed_file() . '.tmp';
		file_put_contents( $tmp, 'GZDATA' );
		// No .size sidecar — the feed still promotes and the run succeeds.
		$this->assertTrue( $this->promote( $tmp ) );

		$this->assertFileExists( $this->gen->feed_file() );
		$this->assertFileDoesNotExist( $this->gen->size_file() );
	}
}
