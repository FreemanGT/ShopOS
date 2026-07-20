<?php
declare(strict_types=1);

use ShopOS\Core\Core\Email_Skin;
use ShopOS\Core\Core\Feature_Flags;
use PHPUnit\Framework\TestCase;

/**
 * §11-B surface 6 — theme transactional email skin (theme.style_emails).
 *
 * The final §11-B surface, and the only Core-side one: WooCommerce emails send
 * from cron / webhook / REST where the active theme may not be ShopOS Line, so a
 * theme email override would vanish (decisions §11 line 304). Like the checkout
 * surface it is SKIN-ONLY — it hooks woocommerce_email_styles and appends
 * email-safe CSS that Emogrifier inlines; it forks no email templates.
 *
 * Pins: the frozen flag name, the registry entry, that boot() registers the
 * styles filter, that appended CSS is added (never replacing WooCommerce's), and
 * — the property unique to an email surface — that the CSS is email-safe (no CSS
 * custom properties / @media / logical properties, which email clients drop).
 *
 * ShopOS Line theme CI lane (decisions §11-B).
 *
 * @covers ShopOS\Core\Core\Email_Skin
 * @group theme
 */
final class EmailSkinTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['fr_opts']  = array();
		$GLOBALS['fr_hooks'] = array();
	}

	// -- Flag identity + registry ---------------------------------------------

	public function test_flag_name_is_frozen(): void {
		$this->assertSame(
			'shopos_core_theme_style_emails_enabled',
			Feature_Flags::option_name( 'theme', 'style_emails' )
		);
	}

	public function test_registry_exposes_the_email_flag(): void {
		$found = null;
		foreach ( Feature_Flags::registry() as $entry ) {
			if ( 'theme' === $entry['module'] && 'style_emails' === $entry['feature'] ) {
				$found = $entry;
				break;
			}
		}
		$this->assertNotNull( $found, 'theme/style_emails must be in the flag registry.' );
		$this->assertFalse( $found['shared'], 'Email skin is a permanent per-store kill-switch.' );
		$this->assertSame( '1.53.0', $found['since'] );
	}

	// -- boot() registers the styles filter -----------------------------------

	public function test_boot_registers_email_styles_filter(): void {
		( new Email_Skin() )->boot();
		$this->assertArrayHasKey(
			'woocommerce_email_styles',
			$GLOBALS['fr_hooks'],
			'boot() must hook woocommerce_email_styles.'
		);
	}

	// -- Appends, never replaces ----------------------------------------------

	public function test_styles_are_appended_not_replacing_woocommerce(): void {
		( new Email_Skin() )->boot();
		$out = apply_filters( 'woocommerce_email_styles', '/* wc-base */' );
		$this->assertStringContainsString( '/* wc-base */', $out, "WooCommerce's base email CSS must be preserved." );
		$this->assertStringContainsString( '#template_container', $out, 'ShopOS email skin CSS must be appended.' );
		$this->assertGreaterThan( strlen( '/* wc-base */' ), strlen( $out ) );
	}

	// -- The property unique to email: no client-unsupported CSS --------------

	public function test_appended_css_is_email_safe(): void {
		$css = Email_Skin::styles();
		$this->assertStringNotContainsStringIgnoringCase( 'var(', $css, 'Email clients drop CSS custom properties.' );
		$this->assertStringNotContainsStringIgnoringCase( '@media', $css, 'Email clients drop @media.' );
		$this->assertStringNotContainsStringIgnoringCase( ':root', $css, 'No custom-property scope belongs in email CSS.' );
		foreach ( array( 'margin-inline', 'padding-inline', 'inset-inline', 'margin-block', 'padding-block' ) as $logical ) {
			$this->assertStringNotContainsStringIgnoringCase( $logical, $css, "Email clients drop the logical property {$logical}." );
		}
	}
}
