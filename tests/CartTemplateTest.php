<?php
declare(strict_types=1);

use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 2 — theme-owned cart page (theme.template_cart).
 *
 * The cart render is procedural (forked WooCommerce templates under
 * templates/woo/cart/, reached via a woocommerce_locate_template redirect) —
 * hook-stack / census identity is integration/live-QA per
 * tools/qa/cart-template.md. This pins the flag seam every consumer routes
 * through (`ShopOS_Theme::cart_enabled()` — the locate filter + the cart-asset
 * enqueue), the frozen flag name, the registry entry, the pure claim helper
 * (only cart/* names, never the PDP/PLP templates the theme also ships), the
 * flag-gated redirect (flag-off passthrough = §11 Ruling 6 byte-identity), the
 * Ruling-10 fonts warning + missing-template log, and the on-disk templates/
 * assets.
 *
 * ShopOS Line theme CI lane (decisions §11-B): run via `phpunit --group theme`.
 *
 * @covers ShopOS_Theme::cart_enabled
 * @covers ShopOS_Theme::should_claim_cart_template
 * @covers ShopOS_Theme::locate_woo_template
 * @group theme
 */
final class CartTemplateTest extends TestCase {

	public static function setUpBeforeClass(): void {
		require_once __DIR__ . '/../shopos-theme/inc/class-shopos-theme.php';
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
		unset( $GLOBALS['fr_stylesheet_dir'] );
		ShopOS_Theme::reset_woo_guards();
	}

	private function theme_dir(): string {
		return realpath( __DIR__ . '/../shopos-theme' );
	}

	/** WooCommerce cart templates the theme forks — the surface's full file set. */
	private function shipped_cart_templates(): array {
		return array(
			'cart/cart.php',
			'cart/cart-empty.php',
			'cart/cart-totals.php',
			'cart/cart-shipping.php',
			'cart/shipping-calculator.php',
			'cart/proceed-to-checkout-button.php',
			'cart/cross-sells.php',
		);
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

	// -- Flag identity + gate --------------------------------------------------

	public function test_flag_name_is_frozen(): void {
		$this->assertSame(
			'shopos_core_theme_template_cart_enabled',
			Feature_Flags::option_name( 'theme', 'template_cart' )
		);
	}

	public function test_cart_disabled_by_default(): void {
		$this->assertFalse( ShopOS_Theme::cart_enabled() );
	}

	public function test_cart_enabled_when_flag_option_set(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled'] = '1';
		$this->assertTrue( ShopOS_Theme::cart_enabled() );
	}

	public function test_cart_off_for_zero_value(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled'] = '0';
		$this->assertFalse( ShopOS_Theme::cart_enabled() );
	}

	public function test_registry_exposes_the_cart_flag(): void {
		$found = null;
		foreach ( Feature_Flags::registry() as $entry ) {
			if ( 'theme' === $entry['module'] && 'template_cart' === $entry['feature'] ) {
				$found = $entry;
				break;
			}
		}
		$this->assertNotNull( $found, 'theme/template_cart must be in the flag registry.' );
		$this->assertFalse( $found['shared'], 'Cart is a permanent per-store kill-switch.' );
	}

	// -- Pure claim helper (the scoping guard) --------------------------------

	public function test_should_claim_cart_template_matrix(): void {
		$claim = array( ShopOS_Theme::class, 'should_claim_cart_template' );

		// Claimed: every cart/* template the surface owns.
		foreach ( $this->shipped_cart_templates() as $name ) {
			$this->assertTrue( $claim( $name ), "{$name} is a cart-surface template" );
		}

		// The mini-cart lives under cart/ too — claimed by name, but the theme
		// ships no fork, so the runtime is_readable guard falls it through to WC
		// (asserted below). The prefix helper still matches it.
		$this->assertTrue( $claim( 'cart/mini-cart.php' ), 'cart/ prefix matches mini-cart by name' );

		// NEVER claimed: the PDP/PLP templates the theme also ships under
		// templates/woo/ (they belong to the template_include loaders — without
		// the cart/ prefix guard, locate_template would collide with them).
		$this->assertFalse( $claim( 'single-product.php' ), 'PDP belongs to Core\'s loader' );
		$this->assertFalse( $claim( 'archive-product.php' ), 'PLP belongs to the theme loader' );
		$this->assertFalse( $claim( 'checkout/form-checkout.php' ), 'checkout is a later surface' );
		$this->assertFalse( $claim( 'myaccount/my-account.php' ), 'account is a later surface' );
		$this->assertFalse( $claim( '' ), 'empty name' );
	}

	// -- Flag-gated redirect (Ruling 6 byte-identity) -------------------------

	public function test_flag_off_never_redirects_a_cart_template(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		// Flag off: file presence alone must never change resolution (§11.3 / Ruling 6).
		$this->assertSame(
			'/wc/cart/cart.php',
			ShopOS_Theme::locate_woo_template( '/wc/cart/cart.php', 'cart/cart.php', '' )
		);
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'flag off warns nothing' );
	}

	public function test_flag_on_redirects_every_shipped_cart_template(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled']  = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		foreach ( $this->shipped_cart_templates() as $name ) {
			$this->assertSame(
				$this->theme_dir() . '/templates/woo/' . $name,
				ShopOS_Theme::locate_woo_template( '/wc/' . $name, $name, '' ),
				"{$name} redirects to the theme copy when the flag is on"
			);
		}
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'fonts on ⇒ no Ruling-10 warning' );
	}

	public function test_flag_on_leaves_non_cart_templates_untouched(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled']  = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		$this->assertSame(
			'/wc/single-product.php',
			ShopOS_Theme::locate_woo_template( '/wc/single-product.php', 'single-product.php', '' ),
			'the PDP template the theme also ships is never claimed by the cart filter'
		);
	}

	public function test_flag_on_unshipped_cart_template_falls_through_and_logs_once(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled']  = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled'] = '1';

		// mini-cart.php is claimed by name but not shipped ⇒ is_readable false ⇒
		// the WC default renders, and the miss is logged once per request.
		$this->assertSame(
			'/wc/cart/mini-cart.php',
			ShopOS_Theme::locate_woo_template( '/wc/cart/mini-cart.php', 'cart/mini-cart.php', '' )
		);
		ShopOS_Theme::locate_woo_template( '/wc/cart/mini-cart.php', 'cart/mini-cart.php', '' );
		$this->assertCount( 1, $this->log_entries( 'info' ), 'missing template logs once per request' );
	}

	public function test_flag_on_with_fonts_off_warns_once_and_still_redirects(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_cart_enabled'] = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/cart/cart.php',
			ShopOS_Theme::locate_woo_template( '/wc/cart/cart.php', 'cart/cart.php', '' )
		);
		ShopOS_Theme::locate_woo_template( '/wc/cart/cart.php', 'cart/cart.php', '' );

		$warnings = $this->log_entries( 'warning' );
		$this->assertCount( 1, $warnings, 'Ruling-10 warning once per request' );
		$this->assertStringContainsString( 'fonts_selfhost', $warnings[0]['message'] );
	}

	// -- On-disk surface ------------------------------------------------------

	public function test_cart_templates_and_assets_exist(): void {
		$dir = $this->theme_dir();
		foreach ( $this->shipped_cart_templates() as $name ) {
			$this->assertFileExists( $dir . '/templates/woo/' . $name );
		}
		$this->assertFileExists( $dir . '/assets/css/shopos-cart.css' );
		$this->assertFileExists( $dir . '/assets/js/shopos-cart.js' );
	}
}
