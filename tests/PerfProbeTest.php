<?php
declare(strict_types=1);

use ShopOS\Core\Core\Perf;
use PHPUnit\Framework\TestCase;

/**
 * The perf probe's pure seams (header map shape/clamps, elapsed-ms source)
 * and boot()'s zero-listener contract: no probe param → no hooks at all,
 * param present → exactly the template_redirect arm + shutdown emit pair.
 * The live header emit against a real pageview is live-QA
 * (tools/perf-budget.php against the wp-env).
 *
 * @covers \ShopOS\Core\Core\Perf
 */
final class PerfProbeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_hooks'] = array();
		unset( $_GET[ Perf::PARAM ] );
	}

	protected function tearDown(): void {
		unset( $_GET[ Perf::PARAM ] );
		parent::tearDown();
	}

	/* -------- boot() gating -------- */

	public function test_boot_registers_nothing_without_the_probe_param(): void {
		( new Perf() )->boot();
		$this->assertSame( array(), $GLOBALS['fr_hooks'] );
	}

	public function test_boot_registers_the_arm_emit_pair_with_the_param(): void {
		$_GET[ Perf::PARAM ] = '1';
		( new Perf() )->boot();

		$this->assertArrayHasKey( 'template_redirect', $GLOBALS['fr_hooks'] );
		$this->assertArrayHasKey( 'shutdown', $GLOBALS['fr_hooks'] );
		// Both at priority 0 — arm before the template renders, emit before
		// WP core's shutdown:1 buffer flush.
		$this->assertSame( 0, $GLOBALS['fr_hooks']['template_redirect'][0]['priority'] );
		$this->assertSame( 0, $GLOBALS['fr_hooks']['shutdown'][0]['priority'] );
	}

	public function test_emit_without_arm_is_a_noop(): void {
		// Param present but template_redirect never fired (admin/AJAX shape):
		// emit() must bail before reading any metric or sending headers.
		$_GET[ Perf::PARAM ] = '1';
		$perf                = new Perf();
		$perf->boot();
		$perf->emit(); // would fatal on header() after output if not guarded.
		$this->addToAssertionCount( 1 );
	}

	/* -------- headers() pure map -------- */

	public function test_headers_shape_and_values(): void {
		$headers = Perf::headers( 42, 123.456, 50 * 1048576 );

		$this->assertSame(
			array(
				'X-ShopOS-Queries'   => '42',
				'X-ShopOS-Render-Ms' => '123.5',
				'X-ShopOS-Mem-MB'    => '50',
			),
			$headers
		);
	}

	public function test_headers_clamp_negative_and_garbage_inputs(): void {
		$headers = Perf::headers( -5, -1.0, -100 );

		$this->assertSame( '0', $headers['X-ShopOS-Queries'] );
		$this->assertSame( '0', $headers['X-ShopOS-Render-Ms'] );
		$this->assertSame( '0', $headers['X-ShopOS-Mem-MB'] );
	}

	/* -------- elapsed_ms() -------- */

	public function test_elapsed_ms_reads_wp_timestart(): void {
		global $timestart;
		$timestart = microtime( true ) - 0.05; // 50ms ago.
		$ms        = Perf::elapsed_ms();
		unset( $GLOBALS['timestart'] );

		$this->assertGreaterThan( 40.0, $ms );
		$this->assertLessThan( 5000.0, $ms );
	}

	public function test_elapsed_ms_is_zero_without_timestart(): void {
		unset( $GLOBALS['timestart'] );
		$this->assertSame( 0.0, Perf::elapsed_ms() );
	}
}
