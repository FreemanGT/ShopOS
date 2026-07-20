<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 4 — the theme-owned search-results template, resolved by the
 * shared theme loader's search claim arm (ShopOS_Theme_Template_Loader).
 *
 * The loader is theme-side PHP (unnamespaced, no autoloader): required in
 * ThemeTemplateLoaderTest::setUpBeforeClass and shared here (one class-load per
 * run), with the real repo theme dir as the fixture. Covers the search claim
 * seam (should_claim_search — the §11 Ruling 2 carve-out's mirror-positive:
 * both the native product-search shape and the is_search()=false + request-term
 * Elementor shape claim, while a generic non-product search never does), its
 * disjointness from should_claim_plp, the flag gate ("file presence alone must
 * never change the render", §11.3), the missing-file info-log fallback, the
 * Ruling-10 fonts warning, and the 'search' context value. The actual render
 * (hook stacks, ShopFilters slot, engine-ranked grid) is integration — live-QA
 * per tools/qa/search-template.md.
 *
 * Part of the ShopOS Line theme CI lane (decisions §11-B): run explicitly via
 * `phpunit --group theme`.
 *
 * @covers ShopOS_Theme_Template_Loader
 * @group theme
 */
final class SearchTemplateTest extends TestCase {

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
			$GLOBALS['fr_is_main_query'],
			$GLOBALS['fr_is_admin']
		);
		$_GET = array();
		ShopOS_Theme_Template_Loader::reset_context();
	}

	protected function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	/** The real repo theme dir — its templates/woo/search-results.php IS the fixture. */
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

	public function test_search_template_exists_on_disk(): void {
		$this->assertFileExists( $this->theme_dir() . '/templates/woo/search-results.php' );
	}

	/**
	 * The Ruling-2 carve-out's positive half, pinned in CI. Args:
	 * [is_admin, is_main, is_shop, is_product_taxonomy, is_search, request_term].
	 */
	public function test_should_claim_search_matrix(): void {
		$claim = array( ShopOS_Theme_Template_Loader::class, 'should_claim_search' );

		// Native product search: is_shop() AND is_search() simultaneously.
		$this->assertTrue( $claim( false, true, true, false, true, '' ), 'native product search' );
		// The live Elementor search page: is_search() FALSE, term only in the
		// request (Results_Query::should_handle mirror).
		$this->assertTrue( $claim( false, true, true, false, false, 'hoodie' ), 'Elementor search shape' );
		$this->assertTrue( $claim( false, true, false, true, false, 'hoodie' ), 'product taxonomy + request term' );
		$this->assertTrue( $claim( false, true, false, true, true, '' ), 'product taxonomy + is_search' );
		$this->assertTrue( $claim( false, true, true, false, false, '  hoodie  ' ), 'whitespace-padded term still a term' );

		// A product archive with NO search belongs to the PLP arm, not this one.
		$this->assertFalse( $claim( false, true, true, false, false, '' ), 'bare shop = PLP, not search' );
		$this->assertFalse( $claim( false, true, false, true, false, '   ' ), 'whitespace-only term is no term' );

		// A generic (non-product) search must never be claimed by the product
		// search template — Results_Query refuses it too (its is_product gate).
		$this->assertFalse( $claim( false, true, false, false, true, 'hoodie' ), 'generic post/page search' );

		// Shape guards.
		$this->assertFalse( $claim( true, true, true, false, true, 'hoodie' ), 'admin' );
		$this->assertFalse( $claim( false, false, true, false, true, 'hoodie' ), 'not the main query' );
	}

	/**
	 * The two full-page arms partition the product-archive space by search
	 * presence: across every route shape, they never both fire (their relative
	 * order in maybe_load_template() is therefore irrelevant).
	 */
	public function test_plp_and_search_claims_are_disjoint(): void {
		$plp    = array( ShopOS_Theme_Template_Loader::class, 'should_claim_plp' );
		$search = array( ShopOS_Theme_Template_Loader::class, 'should_claim_search' );

		foreach ( array( true, false ) as $is_admin ) {
			foreach ( array( true, false ) as $is_main ) {
				foreach ( array( true, false ) as $is_shop ) {
					foreach ( array( true, false ) as $is_tax ) {
						foreach ( array( true, false ) as $is_search ) {
							foreach ( array( '', 'hoodie', '   ' ) as $term ) {
								$both = $plp( $is_admin, $is_main, $is_shop, $is_tax, $is_search, $term )
									&& $search( $is_admin, $is_main, $is_shop, $is_tax, $is_search, $term );
								$this->assertFalse(
									$both,
									"PLP and search both claimed [admin=$is_admin main=$is_main shop=$is_shop tax=$is_tax search=$is_search term='$term']"
								);
							}
						}
					}
				}
			}
		}
	}

	public function test_flag_off_ignores_a_present_search_template(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_is_search']      = true; // native product search shape.
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();

		$this->assertSame(
			'/theme/search.php',
			$this->fresh_loader()->maybe_load_template( '/theme/search.php' ),
			'file presence alone must never change the render (§11.3)'
		);
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
		$this->assertSame( array(), $this->log_entries( 'info' ), 'flag off logs nothing' );
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'flag off warns nothing' );
	}

	public function test_flag_on_resolves_the_search_template_on_native_product_search(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_is_search']      = true;
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_search_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']  = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/search-results.php',
			$this->fresh_loader()->maybe_load_template( '/theme/search.php' )
		);
		$this->assertSame( 'search', ShopOS_Theme_Template_Loader::context(), 'the §11.3 per-surface context value' );
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'fonts on ⇒ no Ruling-10 warning' );
	}

	public function test_flag_on_resolves_via_request_term_on_the_elementor_shape(): void {
		// The live Elementor search page: a product archive where is_search() is
		// FALSE and the term reaches only the request URL.
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_search_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']  = '1';
		$_GET['s']                    = 'hoodie';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/search-results.php',
			$this->fresh_loader()->maybe_load_template( '/theme/archive.php' ),
			'a product archive carrying a request term is the search surface (§11 Ruling 2)'
		);
		$this->assertSame( 'search', ShopOS_Theme_Template_Loader::context() );
	}

	public function test_generic_non_product_search_is_never_claimed(): void {
		// is_search() with no product archive (fr_page_type unset) — a generic
		// post/page search. Neither arm claims it; the current render stands.
		$GLOBALS['fr_is_search']      = true;
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_search_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']  = '1';
		$_GET['s']                    = 'hoodie';

		$this->assertSame(
			'/theme/search.php',
			$this->fresh_loader()->maybe_load_template( '/theme/search.php' )
		);
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
	}

	public function test_flag_on_with_missing_template_falls_back_and_logs_once(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_is_search']      = true;
		$GLOBALS['fr_stylesheet_dir'] = __DIR__; // A real dir with no templates/woo/.
		$GLOBALS['fr_opts']['shopos_core_theme_template_search_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']  = '1';

		$loader = $this->fresh_loader();

		$this->assertSame( '/theme/search.php', $loader->maybe_load_template( '/theme/search.php' ), 'missing file ⇒ the current render (§11.3)' );
		$this->assertSame( '/theme/search.php', $loader->maybe_load_template( '/theme/search.php' ) );
		$this->assertSame( '', ShopOS_Theme_Template_Loader::context() );
		$this->assertCount( 1, $this->log_entries( 'info' ), 'fallback logs once per request, not per call' );
	}

	public function test_flag_on_with_fonts_off_warns_once_and_still_resolves(): void {
		$GLOBALS['fr_page_type']      = 'shop';
		$GLOBALS['fr_is_search']      = true;
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_search_enabled'] = '1';

		$loader = $this->fresh_loader();

		$this->assertSame( $this->theme_dir() . '/templates/woo/search-results.php', $loader->maybe_load_template( '/theme/search.php' ) );
		$loader->maybe_load_template( '/theme/search.php' );

		$warnings = $this->log_entries( 'warning' );
		$this->assertCount( 1, $warnings, 'Ruling-10 warning once per request' );
		$this->assertStringContainsString( 'fonts_selfhost', $warnings[0]['message'] );
		$this->assertStringContainsString( 'template_search', $warnings[0]['message'], 'the warning names the surface flag' );
	}
}
