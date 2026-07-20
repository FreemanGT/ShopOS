<?php
declare(strict_types=1);

use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 5 — theme checkout skin (theme.style_checkout).
 *
 * SKIN-ONLY (Ruling 9 resolved-as-moot): this surface forks NO templates — it
 * gates a single stylesheet enqueue on is_checkout(), so WooCommerce keeps every
 * checkout field/nonce/gateway and it works on both the shortcode and block
 * checkout. The enqueue + Ruling-10 fonts warning live inside enqueue_assets()
 * and are integration/live-QA per tools/qa/checkout-skin.md (the fonts-off warn
 * pattern is already unit-pinned by the cart/account locate-path tests). This
 * pins the flag seam (`ShopOS_Theme::checkout_enabled()`), the frozen flag name,
 * the registry entry, the on-disk stylesheet, and — the property unique to a
 * skin-only surface — that the flag never drives a locate_template fork.
 *
 * ShopOS Line theme CI lane (decisions §11-B).
 *
 * @covers ShopOS_Theme::checkout_enabled
 * @group theme
 */
final class CheckoutSkinTest extends TestCase {

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

	// -- Flag identity + gate --------------------------------------------------

	public function test_flag_name_is_frozen(): void {
		$this->assertSame(
			'shopos_core_theme_style_checkout_enabled',
			Feature_Flags::option_name( 'theme', 'style_checkout' )
		);
	}

	public function test_checkout_disabled_by_default(): void {
		$this->assertFalse( ShopOS_Theme::checkout_enabled() );
	}

	public function test_checkout_enabled_when_flag_option_set(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_style_checkout_enabled'] = '1';
		$this->assertTrue( ShopOS_Theme::checkout_enabled() );
	}

	public function test_checkout_off_for_zero_value(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_style_checkout_enabled'] = '0';
		$this->assertFalse( ShopOS_Theme::checkout_enabled() );
	}

	public function test_registry_exposes_the_checkout_flag(): void {
		$found = null;
		foreach ( Feature_Flags::registry() as $entry ) {
			if ( 'theme' === $entry['module'] && 'style_checkout' === $entry['feature'] ) {
				$found = $entry;
				break;
			}
		}
		$this->assertNotNull( $found, 'theme/style_checkout must be in the flag registry.' );
		$this->assertFalse( $found['shared'], 'Checkout skin is a permanent per-store kill-switch.' );
	}

	// -- Skin-only: the flag must never fork a template -----------------------

	public function test_checkout_flag_never_forks_a_template(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		// Checkout skin on (fonts on to isolate from the Ruling-10 warning path):
		// a checkout/* template must pass through untouched — checkout is not a
		// woo_surface_enabled() arm, so locate_woo_template never redirects it.
		$GLOBALS['fr_opts']['shopos_core_theme_style_checkout_enabled']  = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']  = '1';

		$this->assertSame(
			'/wc/checkout/form-checkout.php',
			ShopOS_Theme::locate_woo_template( '/wc/checkout/form-checkout.php', 'checkout/form-checkout.php', '' ),
			'checkout is skin-only — WooCommerce keeps the checkout templates'
		);
	}

	// -- On-disk surface ------------------------------------------------------

	public function test_checkout_stylesheet_exists(): void {
		$this->assertFileExists( $this->theme_dir() . '/assets/css/shopos-checkout.css' );
	}
}
