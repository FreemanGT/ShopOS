<?php
/**
 * ShopOS perf probe — per-template diagnostic response headers.
 *
 * The measurement half of the Phase-3 perf-budget item: when the
 * `shopos_core_perf_probe_enabled` flag is ON (default OFF, wired in
 * Plugin::boot()) AND the request carries `?shopos_perf=1`, the front-end
 * response gains three headers —
 *
 *   X-ShopOS-Queries    total DB queries for the pageview (get_num_queries)
 *   X-ShopOS-Render-Ms  wall time since WP's own $timestart, in ms
 *   X-ShopOS-Mem-MB     peak memory for the request
 *
 * — which `tools/perf-budget.php` reads to enforce the committed per-template
 * budgets (tools/perf-budgets.json). Query-Monitor-style, but deliberately
 * tiny and scoped: numbers only, no query text, no paths, nothing sensitive.
 *
 * Off (flag or param missing) ⇒ boot() registers ZERO listeners ⇒
 * byte-identical responses. The per-flag
 * `shopos_core/feature_flag/perf/probe` filter built into
 * Feature_Flags::is_enabled() is the code-level kill-switch — no extra
 * filter needed.
 *
 * Timing: arm() opens an output buffer at template_redirect:0 so headers
 * stay sendable until shutdown; emit() runs at shutdown:0 — before WP core's
 * own `wp_ob_end_flush_all` (shutdown:1) flushes the page — with a
 * headers_sent() guard for exotic flush setups.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Perf probe.
 */
final class Perf {

	/** Probe query param — presence-only, the value is ignored. */
	const PARAM = 'shopos_perf';

	/**
	 * Whether a front-end template request actually started (template_redirect
	 * fired). Admin/AJAX/REST requests never arm, so they never emit.
	 *
	 * @var bool
	 */
	private $armed = false;

	/**
	 * Register hooks — only when the request opts in via the probe param.
	 * Called from Plugin::boot() behind the perf/probe feature flag, so a
	 * normal request (flag off, or no param) registers nothing at all.
	 */
	public function boot() {
		// Presence check only — a read-only diagnostic, no state change.
		if ( ! isset( $_GET[ self::PARAM ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		add_action( 'template_redirect', array( $this, 'arm' ), 0 );
		add_action( 'shutdown', array( $this, 'emit' ), 0 );
	}

	/**
	 * Front-end template request confirmed: buffer output so the headers are
	 * still sendable at shutdown, after the whole page has been measured.
	 */
	public function arm() {
		$this->armed = true;
		ob_start();
	}

	/**
	 * Emit the probe headers. shutdown:0 — before WP core flushes buffers.
	 */
	public function emit() {
		if ( ! $this->armed || headers_sent() ) {
			return;
		}
		$queries = function_exists( 'get_num_queries' ) ? get_num_queries() : 0;
		foreach ( self::headers( $queries, self::elapsed_ms(), memory_get_peak_usage( true ) ) as $name => $value ) {
			header( $name . ': ' . $value );
		}
	}

	/**
	 * Milliseconds since WP's own request start marker.
	 *
	 * @return float
	 */
	public static function elapsed_ms() {
		global $timestart;
		if ( empty( $timestart ) ) {
			return 0.0;
		}
		return ( microtime( true ) - (float) $timestart ) * 1000;
	}

	/**
	 * Pure: the probe header map. Values are clamped non-negative and
	 * rounded so the budget tool parses stable shapes.
	 *
	 * @param int   $queries    Query count.
	 * @param float $elapsed_ms Render wall time in ms.
	 * @param int   $peak_bytes Peak memory in bytes.
	 * @return array<string,string>
	 */
	public static function headers( $queries, $elapsed_ms, $peak_bytes ) {
		return array(
			'X-ShopOS-Queries'   => (string) max( 0, (int) $queries ),
			'X-ShopOS-Render-Ms' => (string) round( max( 0.0, (float) $elapsed_ms ), 1 ),
			'X-ShopOS-Mem-MB'    => (string) round( max( 0, (int) $peak_bytes ) / 1048576, 1 ),
		);
	}
}
