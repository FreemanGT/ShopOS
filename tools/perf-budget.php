<?php
/**
 * ShopOS per-template perf-budget check (Phase-3 perf-budget item).
 *
 * Probes each storefront template listed in tools/perf-budgets.json with
 * `?shopos_perf=1` (requires the `shopos_core_perf_probe_enabled` flag ON —
 * `wp shopos flags set perf.probe on`), reads the X-ShopOS-* diagnostic
 * headers plus the body size, and fails when any metric exceeds its budget.
 *
 * NOT a CI gate — repo CI has no WordPress. This is a local / staging gate:
 * run it against the wp-env or a store before/after perf-sensitive changes.
 *
 * Usage:
 *   php tools/perf-budget.php <base-url> [budgets.json]         # check (default)
 *   php tools/perf-budget.php <base-url> [budgets.json] --seed  # measure + write
 *                                                               # budgets with the
 *                                                               # headroom factor
 *
 * The committed tools/perf-budgets.json is seeded against the local wp-env
 * (the reference store). Re-seed per store: budgets are a per-store contract,
 * not a universal constant.
 *
 * Exit codes: 0 all within budget · 1 breach or missing probe headers · 2 usage.
 *
 * @package ShopOS
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

const HEADROOM = 1.25; // --seed writes measured value × this, rounded up.

$args    = array_slice( $argv, 1 );
$seed    = in_array( '--seed', $args, true );
$args    = array_values( array_diff( $args, array( '--seed' ) ) );
$base    = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';
$budgets = isset( $args[1] ) ? $args[1] : __DIR__ . '/perf-budgets.json';

if ( '' === $base ) {
	fwrite( STDERR, "Usage: php tools/perf-budget.php <base-url> [budgets.json] [--seed]\n" );
	exit( 2 );
}

$config = json_decode( (string) file_get_contents( $budgets ), true );
if ( ! is_array( $config ) || empty( $config['templates'] ) ) {
	fwrite( STDERR, "Cannot read budgets file: {$budgets}\n" );
	exit( 2 );
}

/**
 * Fetch a URL and return [headers-map (lowercased names), body-bytes].
 *
 * @param string $url URL.
 * @return array{0:array<string,string>,1:int}|null
 */
function probe( $url ) {
	$ctx  = stream_context_create(
		array(
			'http' => array(
				'method'        => 'GET',
				'timeout'       => 30,
				'ignore_errors' => true,
				'header'        => "User-Agent: shopos-perf-budget\r\n",
			),
		)
	);
	$body = @file_get_contents( $url, false, $ctx );
	if ( false === $body ) {
		return null;
	}
	$headers = array();
	foreach ( (array) $http_response_header as $line ) {
		if ( false !== strpos( $line, ':' ) ) {
			list( $name, $value )                  = explode( ':', $line, 2 );
			$headers[ strtolower( trim( $name ) ) ] = trim( $value );
		}
	}
	return array( $headers, strlen( $body ) );
}

$metrics = array(
	'max_queries'   => array( 'header' => 'x-shopos-queries', 'label' => 'queries' ),
	'max_render_ms' => array( 'header' => 'x-shopos-render-ms', 'label' => 'render_ms' ),
	'max_mem_mb'    => array( 'header' => 'x-shopos-mem-mb', 'label' => 'mem_mb' ),
	'max_bytes'     => array( 'header' => null, 'label' => 'bytes' ), // body size, not a header.
);

$failures = 0;
$seeded   = array( 'templates' => array() );

foreach ( $config['templates'] as $name => $tpl ) {
	$path = isset( $tpl['path'] ) ? (string) $tpl['path'] : '/';
	$url  = $base . $path . ( false === strpos( $path, '?' ) ? '?' : '&' ) . 'shopos_perf=1';

	$result = probe( $url );
	if ( null === $result ) {
		echo "[FAIL] {$name}  {$path}  — request failed\n";
		$failures++;
		continue;
	}
	list( $headers, $bytes ) = $result;

	if ( ! isset( $headers['x-shopos-queries'] ) ) {
		echo "[FAIL] {$name}  {$path}  — no probe headers (is the perf.probe flag on?)\n";
		$failures++;
		continue;
	}

	$measured = array(
		'max_queries'   => (float) $headers['x-shopos-queries'],
		'max_render_ms' => (float) ( isset( $headers['x-shopos-render-ms'] ) ? $headers['x-shopos-render-ms'] : 0 ),
		'max_mem_mb'    => (float) ( isset( $headers['x-shopos-mem-mb'] ) ? $headers['x-shopos-mem-mb'] : 0 ),
		'max_bytes'     => (float) $bytes,
	);

	if ( $seed ) {
		$row = array( 'path' => $path );
		foreach ( $measured as $key => $value ) {
			$row[ $key ] = (int) ceil( $value * HEADROOM );
		}
		$seeded['templates'][ $name ] = $row;
		printf(
			"[SEED] %-10s %-40s queries=%d render_ms=%d mem_mb=%d bytes=%d\n",
			$name,
			$path,
			$row['max_queries'],
			$row['max_render_ms'],
			$row['max_mem_mb'],
			$row['max_bytes']
		);
		continue;
	}

	$breaches = array();
	foreach ( $metrics as $key => $meta ) {
		if ( ! isset( $tpl[ $key ] ) ) {
			continue; // metric not budgeted for this template.
		}
		if ( $measured[ $key ] > (float) $tpl[ $key ] ) {
			$breaches[] = sprintf( '%s %s > %s', $meta['label'], rtrim( rtrim( number_format( $measured[ $key ], 1, '.', '' ), '0' ), '.' ), $tpl[ $key ] );
		}
	}

	if ( $breaches ) {
		printf( "[FAIL] %-10s %-40s %s\n", $name, $path, implode( ', ', $breaches ) );
		$failures++;
	} else {
		printf(
			"[ OK ] %-10s %-40s queries=%d/%s render_ms=%s mem_mb=%s bytes=%d\n",
			$name,
			$path,
			$measured['max_queries'],
			isset( $tpl['max_queries'] ) ? $tpl['max_queries'] : '-',
			$headers['x-shopos-render-ms'],
			$headers['x-shopos-mem-mb'],
			$measured['max_bytes']
		);
	}
}

if ( $seed ) {
	file_put_contents( $budgets, json_encode( $seeded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" );
	echo "Wrote {$budgets} (headroom ×" . HEADROOM . ")\n";
	exit( 0 );
}

echo $failures ? "\n{$failures} template(s) over budget.\n" : "\nAll templates within budget.\n";
exit( $failures ? 1 : 0 );
