<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * §11.4 row 5 — the shared theme template loader (PLP).
 *
 * The loader is theme-side PHP (unnamespaced, no autoloader): required
 * directly below, with the real repo theme dir as the fixture — the
 * ProductPageTemplateTest precedent. Covers the claim seam
 * (should_claim_plp — where the Ruling-2 search carve-out is pinned in CI,
 * including the is_search()=false + request-term Elementor-search shape),
 * the flag gate ("file presence alone must never change the render",
 * §11.3), the missing-file info-log fallback, the Ruling-10 fonts warning,
 * and the per-surface context value. The actual render (hook stacks,
 * ShopFilters slot, census) is integration — live-QA per
 * tools/qa/plp-template.md.
 *
 * @covers ShopOS_Theme_Template_Loader
 */
final class ThemeTemplateLoaderTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/../shopos-theme/inc/class-shopos-template-loader.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset(
			$GLOBALS['fr_page_type'],
			$GLOBALS['fr_stylesheet_dir'],
			$GLOBALS['fr_is_search'],
			$GLOBALS['fr_is_main_query']
		);
		$_GET = array();
		ShopOS_Theme_Template_Loader::reset_context();
	}

	protected function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	/** The real repo theme dir — its templates/woo/archive-product.php IS the fixture. */
	private function theme_dir(): string {
		return realpath( __DIR__ . '/../shopos-theme' );
	}

	private function log_entries( string $level ): array {
		$log = $GLOBALS['fr_opts']['shopos_core_log'] ?? array();
		return array_values(
			array_filter(
				is_array( $log ) ? $log : array(),
				static function ( $entry ) use ( $level ) {
					return ( $entry['level'] ?? '' ) === $level;
				}
			)
		);
	}

	/** A fresh loader per test so once-per-request guards stay order-independent. */
	private function fresh_loader(): ShopOS_Theme_Template_Loader {
		return new ShopOS_Theme_Template_Loader();
	}

	public function test_theme_template_exists_on_disk(): void {
		$this->assertFileExists( $this->theme_dir() . '/templates/woo/archive-product.php' );
	}

	public function test_flag_off_ignores_a_present_template(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();

		$this->assertSame(
			'/theme/archive.php',
			$this->fresh_loader()->maybe_load_template( '/theme/archive.php' ),
			'file presence alone must never change the render (§11.3)'
		);
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
		$this->assertSame( array(), $this->log_entries( 'info' ), 'flag off logs nothing' );
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'flag off warns nothing' );
	}

	public function test_flag_on_resolves_the_theme_template_on_shop(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/archive-product.php',
			$this->fresh_loader()->maybe_load_template( '/theme/archive.php' )
		);
		$this->assertSame( 'plp', ShopOS_Theme_Template_Loader::context(), 'the §11.3 per-surface context value' );
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'fonts on ⇒ no Ruling-10 warning' );
	}

	public function test_flag_on_resolves_on_product_taxonomy_archives(): void {
		$GLOBALS['fr_page_type']      = 'product_taxonomy';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/archive-product.php',
			$this->fresh_loader()->maybe_load_template( '/theme/tax.php' )
		);
	}

	public function test_claim_is_disjoint_from_core_surfaces(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		foreach ( array( 'product', 'cart', 'checkout', 'account', '' ) as $type ) {
			$GLOBALS['fr_page_type'] = $type;
			$this->assertSame(
				'/theme/current.php',
				$this->fresh_loader()->maybe_load_template( '/theme/current.php' ),
				"'{$type}' is never claimed — PDP belongs to Core's loader, pages to WP"
			);
		}
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
	}

	/**
	 * The Ruling-2 carve-out pinned in CI: both search shapes refuse, and the
	 * matrix is pure (no WP stubs consulted).
	 */
	public function test_should_claim_plp_matrix(): void {
		$claim = array( ShopOS_Theme_Template_Loader::class, 'should_claim_plp' );

		// Claimed: shop / taxonomy product-archive main queries, no term.
		$this->assertTrue( $claim( false, true, true, false, false, '' ), 'shop page' );
		$this->assertTrue( $claim( false, true, false, true, false, '' ), 'product taxonomy' );

		// Native product search: is_shop() AND is_search() simultaneously.
		$this->assertFalse( $claim( false, true, true, false, true, 'test' ), 'native product search' );
		// The live Elementor search page: is_search() FALSE, term only in the
		// request (Results_Query::should_handle mirror) — an is_search()-only
		// guard would wrongly claim this page.
		$this->assertFalse( $claim( false, true, true, false, false, 'test' ), 'Elementor search shape' );
		$this->assertFalse( $claim( false, true, true, false, false, '  test  ' ), 'whitespace term still a term' );

		// Shape guards.
		$this->assertFalse( $claim( true, true, true, false, false, '' ), 'admin' );
		$this->assertFalse( $claim( false, false, true, false, false, '' ), 'not the main query' );
		$this->assertFalse( $claim( false, true, false, false, false, '' ), 'not a product archive' );
	}

	public function test_runtime_refuses_search_requests_via_request_term(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';
		$_GET['s']                    = 'test';

		$this->assertSame(
			'/theme/archive.php',
			$this->fresh_loader()->maybe_load_template( '/theme/archive.php' ),
			'a product archive carrying a request term belongs to the search surface (§11 Ruling 2)'
		);
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
	}

	public function test_flag_on_with_missing_template_falls_back_and_logs_once(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = __DIR__; // A real dir with no templates/woo/.
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$loader = $this->fresh_loader();

		$this->assertSame( '/theme/archive.php', $loader->maybe_load_template( '/theme/archive.php' ), 'missing file ⇒ the current render (§11.3)' );
		$this->assertSame( '/theme/archive.php', $loader->maybe_load_template( '/theme/archive.php' ) );
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
		$this->assertCount( 1, $this->log_entries( 'info' ), 'fallback logs once per request, not per call' );
	}

	public function test_flag_on_with_fonts_off_warns_once_and_still_resolves(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled'] = '1';

		$loader = $this->fresh_loader();

		$this->assertSame( $this->theme_dir() . '/templates/woo/archive-product.php', $loader->maybe_load_template( '/theme/archive.php' ) );
		$loader->maybe_load_template( '/theme/archive.php' );

		$warnings = $this->log_entries( 'warning' );
		$this->assertCount( 1, $warnings, 'Ruling-10 warning once per request' );
		$this->assertStringContainsString( 'fonts_selfhost', $warnings[0]['message'] );
	}

	public function test_request_search_term_reads_the_raw_request(): void {
		$this->assertSame( '', ShopOS_Theme_Template_Loader::request_search_term() );
		$_GET['s'] = '  hello  ';
		$this->assertSame( 'hello', ShopOS_Theme_Template_Loader::request_search_term() );
	}

	/**
	 * Ruling 2 is presence-based: payloads that sanitize to nothing (arrays,
	 * tag-only strings) still count as a request search term — the loader
	 * must refuse, not claim on the stripped value.
	 */
	public function test_request_search_term_is_presence_based_for_unparseable_payloads(): void {
		$_GET['s'] = array( 'knife' );
		$this->assertNotSame( '', ShopOS_Theme_Template_Loader::request_search_term(), 'array payload counts as a term' );

		$_GET['s'] = '<b></b>';
		$this->assertNotSame( '', ShopOS_Theme_Template_Loader::request_search_term(), 'tag-only payload counts as a term' );

		$_GET['s'] = '   ';
		$this->assertSame( '', ShopOS_Theme_Template_Loader::request_search_term(), 'whitespace-only is no term' );
	}

	public function test_runtime_refuses_unparseable_search_payloads(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_plp_enabled']   = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';
		$_GET['s']                    = array( 'knife' );

		$this->assertSame( '/theme/archive.php', $this->fresh_loader()->maybe_load_template( '/theme/archive.php' ) );
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
	}
}
