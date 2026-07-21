<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * §20 block 20: asserts docs/DESIGN.md frontmatter mirrors the canonical
 * token values in shopos-theme/assets/css/shopos-tokens.css by running
 * tools/tokens-check.php. A failing assertion means a value drifted between
 * the two places without both being updated in the same PR (§4 Anti-drift).
 */
final class TokensParityTest extends TestCase {

	public function test_design_md_frontmatter_matches_tokens_css(): void {
		$script = dirname( __DIR__ ) . '/tools/tokens-check.php';
		$this->assertFileExists( $script, 'tokens-check.php missing' );

		$cmd = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ) . ' 2>&1';
		exec( $cmd, $out, $code );

		$this->assertSame(
			0,
			$code,
			"tokens:check reported drift between DESIGN.md and shopos-tokens.css:\n" . implode( "\n", $out )
		);
	}
}
