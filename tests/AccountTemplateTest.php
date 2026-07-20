<?php
declare(strict_types=1);

use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 3 — theme-owned My Account pages (theme.template_account).
 *
 * Reuses the shared `locate_woo_template` filter (generalized from the cart
 * surface). Two structural templates are forked (my-account.php shell +
 * navigation.php rail); the account content + auth/payment forms are
 * CSS-skinned, not forked, so a claimed-by-prefix but unshipped myaccount
 * template (e.g. dashboard.php) must fall through to WooCommerce. This pins the
 * flag seam (`ShopOS_Theme::account_enabled()`), the frozen flag name, the
 * registry entry, the pure claim helper, the flag-gated redirect (flag-off
 * passthrough = §11 Ruling 6), and the on-disk forks/asset.
 *
 * @covers ShopOS_Theme::account_enabled
 * @covers ShopOS_Theme::should_claim_account_template
 * @covers ShopOS_Theme::locate_woo_template
 * @group theme
 */
final class AccountTemplateTest extends TestCase {

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

	/** The structural myaccount templates the theme forks (content is CSS-skinned). */
	private function shipped_account_templates(): array {
		return array(
			'myaccount/my-account.php',
			'myaccount/navigation.php',
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
			'shopos_core_theme_template_account_enabled',
			Feature_Flags::option_name( 'theme', 'template_account' )
		);
	}

	public function test_account_disabled_by_default(): void {
		$this->assertFalse( ShopOS_Theme::account_enabled() );
	}

	public function test_account_enabled_when_flag_option_set(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '1';
		$this->assertTrue( ShopOS_Theme::account_enabled() );
	}

	public function test_account_off_for_zero_value(): void {
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '0';
		$this->assertFalse( ShopOS_Theme::account_enabled() );
	}

	public function test_registry_exposes_the_account_flag(): void {
		$found = null;
		foreach ( Feature_Flags::registry() as $entry ) {
			if ( 'theme' === $entry['module'] && 'template_account' === $entry['feature'] ) {
				$found = $entry;
				break;
			}
		}
		$this->assertNotNull( $found, 'theme/template_account must be in the flag registry.' );
		$this->assertFalse( $found['shared'], 'Account is a permanent per-store kill-switch.' );
	}

	// -- Pure claim helper -----------------------------------------------------

	public function test_should_claim_account_template_matrix(): void {
		$claim = array( ShopOS_Theme::class, 'should_claim_account_template' );

		// Claimed by prefix: every myaccount/* template (shipped or CSS-skinned).
		$this->assertTrue( $claim( 'myaccount/my-account.php' ) );
		$this->assertTrue( $claim( 'myaccount/navigation.php' ) );
		$this->assertTrue( $claim( 'myaccount/dashboard.php' ), 'content templates match the prefix (fall through at runtime)' );
		$this->assertTrue( $claim( 'myaccount/form-login.php' ), 'forms match the prefix (fall through at runtime)' );

		// Never claimed by the account arm.
		$this->assertFalse( $claim( 'cart/cart.php' ), 'cart belongs to the cart arm' );
		$this->assertFalse( $claim( 'single-product.php' ), 'PDP belongs to Core\'s loader' );
		$this->assertFalse( $claim( '' ), 'empty name' );
	}

	// -- Flag-gated redirect (Ruling 6 byte-identity) -------------------------

	public function test_flag_off_never_redirects_an_account_template(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$this->assertSame(
			'/wc/myaccount/my-account.php',
			ShopOS_Theme::locate_woo_template( '/wc/myaccount/my-account.php', 'myaccount/my-account.php', '' )
		);
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'flag off warns nothing' );
	}

	public function test_flag_on_redirects_the_forked_structural_templates(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']   = '1';

		foreach ( $this->shipped_account_templates() as $name ) {
			$this->assertSame(
				$this->theme_dir() . '/templates/woo/' . $name,
				ShopOS_Theme::locate_woo_template( '/wc/' . $name, $name, '' ),
				"{$name} redirects to the theme copy when the flag is on"
			);
		}
		$this->assertSame( array(), $this->log_entries( 'warning' ), 'fonts on ⇒ no Ruling-10 warning' );
	}

	public function test_flag_on_unforked_account_template_falls_through_to_wc(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']   = '1';

		// dashboard.php + the auth/payment forms are CSS-skinned, not forked ⇒
		// not readable under templates/woo/myaccount/ ⇒ WC default renders.
		foreach ( array( 'myaccount/dashboard.php', 'myaccount/form-login.php', 'myaccount/form-edit-account.php' ) as $name ) {
			$this->assertSame(
				'/wc/' . $name,
				ShopOS_Theme::locate_woo_template( '/wc/' . $name, $name, '' ),
				"{$name} is CSS-skinned, not forked — WC keeps ownership"
			);
		}
	}

	public function test_account_flag_does_not_leak_into_the_cart_arm(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		// Account on, cart OFF: a cart template must NOT redirect.
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '1';
		$GLOBALS['fr_opts']['shopos_core_theme_fonts_selfhost_enabled']   = '1';

		$this->assertSame(
			'/wc/cart/cart.php',
			ShopOS_Theme::locate_woo_template( '/wc/cart/cart.php', 'cart/cart.php', '' ),
			'each surface arm is gated by its own flag'
		);
	}

	public function test_flag_on_with_fonts_off_warns_once(): void {
		$GLOBALS['fr_stylesheet_dir'] = $this->theme_dir();
		$GLOBALS['fr_opts']['shopos_core_theme_template_account_enabled'] = '1';

		$this->assertSame(
			$this->theme_dir() . '/templates/woo/myaccount/my-account.php',
			ShopOS_Theme::locate_woo_template( '/wc/myaccount/my-account.php', 'myaccount/my-account.php', '' )
		);
		ShopOS_Theme::locate_woo_template( '/wc/myaccount/navigation.php', 'myaccount/navigation.php', '' );

		$warnings = $this->log_entries( 'warning' );
		$this->assertCount( 1, $warnings, 'Ruling-10 warning once per request across surfaces' );
		$this->assertStringContainsString( 'fonts_selfhost', $warnings[0]['message'] );
	}

	// -- On-disk surface ------------------------------------------------------

	public function test_account_templates_and_asset_exist(): void {
		$dir = $this->theme_dir();
		foreach ( $this->shipped_account_templates() as $name ) {
			$this->assertFileExists( $dir . '/templates/woo/' . $name );
		}
		$this->assertFileExists( $dir . '/assets/css/shopos-account.css' );
	}
}
