#!/usr/bin/env php
<?php
/**
 * tokens:check — assert docs/DESIGN.md frontmatter mirrors the canonical
 * token values in shopos-theme/assets/css/shopos-tokens.css (§4 Anti-drift,
 * §20 block 20). A value that lives in two places by hand will drift; this
 * fails the build the moment it does.
 *
 * Checks the frontmatter groups that map 1:1 to --shopos-ui-* tokens by raw
 * value (palette, spacing, radius, layout, shadows, z, breakpoints, and the
 * typography scale/leading/tracking/weight). Reference-based groups (colors,
 * inverted, accentThemes, components) resolve through those, so they are not
 * re-checked here.
 *
 * Usage:  php tools/tokens-check.php
 * Exit:   0 = in sync, 1 = drift (details on stderr), 2 = usage/parse error.
 */

$root       = dirname( __DIR__ );
$tokens_css = $root . '/shopos-theme/assets/css/shopos-tokens.css';
$design_md  = $root . '/docs/DESIGN.md';

foreach ( array( $tokens_css, $design_md ) as $f ) {
	if ( ! is_readable( $f ) ) {
		fwrite( STDERR, "tokens:check — cannot read {$f}\n" );
		exit( 2 );
	}
}

/** Collapse a CSS/YAML value to a comparable form: unquote, strip spaces, lowercase. */
function tc_norm( $v ) {
	$v = trim( (string) $v );
	// A quoted value carries its content up to the closing quote (a trailing
	// YAML `# comment` sits outside it); an unquoted value strips the comment.
	if ( preg_match( '/^"([^"]*)"/', $v, $m ) || preg_match( "/^'([^']*)'/", $v, $m ) ) {
		$v = $m[1];
	} else {
		$v = preg_replace( '/\s+#.*$/', '', $v );
	}
	return strtolower( preg_replace( '/\s+/', '', trim( $v ) ) );
}

/* ---- 1. Parse tokens.css into name => normalized value ---------------- */
$css   = file_get_contents( $tokens_css );
$tok   = array();
if ( preg_match_all( '/--shopos-ui-([a-z0-9-]+)\s*:\s*([^;]+);/i', $css, $m, PREG_SET_ORDER ) ) {
	foreach ( $m as $row ) {
		// first definition wins (the :root base block); later @media re-decls ignored.
		if ( ! isset( $tok[ $row[1] ] ) ) {
			$tok[ $row[1] ] = tc_norm( $row[2] );
		}
	}
}

/* ---- 2. Parse the DESIGN.md YAML frontmatter -------------------------- */
$md = file_get_contents( $design_md );
if ( ! preg_match( '/^---\n(.*?)\n---/s', $md, $fm ) ) {
	fwrite( STDERR, "tokens:check — no frontmatter block in DESIGN.md\n" );
	exit( 2 );
}
$lines = explode( "\n", $fm[1] );

// group => token prefix ('' means the key is already the full suffix).
$prefix = array(
	'palette'     => 'palette-',
	'spacing'     => 'space-',
	'rounded'     => 'radius-',
	'layout'      => '',
	'shadows'     => 'shadow-',
	'zIndex'      => 'z-',
	'breakpoints' => 'bp-',
	'scale'       => 'text-',      // typography.scale
	'leading'     => 'leading-',   // typography.leading
	'tracking'    => 'tracking-',  // typography.tracking
	'weight'      => 'weight-',    // typography.weight
);

$group = null;   // active top-level group
$sub   = null;   // active typography sub-group
$checks = array(); // token-name => expected normalized value

foreach ( $lines as $line ) {
	if ( '' === trim( $line ) || preg_match( '/^\s*#/', $line ) ) {
		continue;
	}
	// Top-level group: no indentation, `key:` with nothing (or a comment) after.
	if ( preg_match( '/^([A-Za-z][A-Za-z0-9]*):\s*(#.*)?$/', $line, $g ) ) {
		$group = $g[1];
		$sub   = null;
		continue;
	}
	// typography sub-group: 2-space indent, `key:` with nothing after.
	if ( 'typography' === $group && preg_match( '/^  ([a-z]+):\s*(#.*)?$/', $line, $s ) ) {
		$sub = $s[1];
		continue;
	}
	// A leaf `  key: value` (any indent ≥2). key may be quoted.
	if ( preg_match( '/^\s+"?([A-Za-z0-9_-]+)"?:\s*(.+)$/', $line, $kv ) ) {
		$active = ( 'typography' === $group ) ? $sub : $group;
		if ( null === $active || ! isset( $prefix[ $active ] ) ) {
			continue; // group we don't check (colors/inverted/fonts/roles/…)
		}
		$name = '--shopos-ui-' . $prefix[ $active ] . $kv[1];
		$key  = $prefix[ $active ] . $kv[1];
		$checks[ $key ] = tc_norm( $kv[2] );
	}
}

/* ---- 3. Diff --------------------------------------------------------- */
$drift = array();
foreach ( $checks as $key => $expected ) {
	if ( ! isset( $tok[ $key ] ) ) {
		$drift[] = "  MISSING  --shopos-ui-{$key}  (in DESIGN.md, absent from tokens.css)";
		continue;
	}
	if ( $tok[ $key ] !== $expected ) {
		$drift[] = "  MISMATCH --shopos-ui-{$key}  DESIGN.md='{$expected}'  tokens.css='{$tok[$key]}'";
	}
}

if ( $drift ) {
	fwrite( STDERR, "tokens:check — DESIGN.md frontmatter drifted from shopos-tokens.css:\n" );
	fwrite( STDERR, implode( "\n", $drift ) . "\n" );
	exit( 1 );
}

fwrite( STDOUT, 'tokens:check — OK (' . count( $checks ) . " tokens in sync)\n" );
exit( 0 );
