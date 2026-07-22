<?php
/**
 * ShopOS Line transactional-email skin — §11-B surface 6 (the final surface).
 *
 * Core-side by construction. WooCommerce transactional emails send from cron /
 * webhook / REST contexts where the active theme may not be ShopOS Line, so a
 * theme-level email-template override would vanish the moment a store switches
 * theme or an order fires from cron (decisions §11 line 304). This surface
 * therefore lives in Core and is booted from Plugin::boot() (which runs on
 * plugins_loaded in every send context), gated by theme.style_emails.
 *
 * Skin-only, like the checkout surface (§11-B Ruling 9 doctrine): it hooks
 * `woocommerce_email_styles` and APPENDS email-safe CSS that WooCommerce inlines
 * onto the email markup via Emogrifier. It forks NO email templates, so there is
 * no WooCommerce email template @version to chase, and WooCommerce keeps
 * ownership of every email header, footer, and template.
 *
 * KEY CONSTRAINT: email clients (Outlook / Gmail / Apple Mail) do not support
 * CSS custom properties, @media, or logical properties — the --shopos-ui-* token
 * CSS every prior storefront surface used CANNOT be reused here. styles() is
 * literal hex/px values (the resolved ShopOS Line palette), pinned email-safe by
 * EmailSkinTest.
 *
 * @package ShopOSCore
 */

namespace ShopOS\Core\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Email_Skin.
 */
final class Email_Skin {

	/**
	 * Register the WooCommerce email-styles hook. Called only when
	 * theme.style_emails is on (gated in Plugin::boot()), so a flag-off store
	 * never adds the filter and its emails are byte-identical (§11 Ruling 6).
	 *
	 * Priority 20 runs after WooCommerce's own base styles so, at equal
	 * selector specificity, our rules win on source order after Emogrifier
	 * resolves the cascade.
	 */
	public function boot() {
		add_filter( 'woocommerce_email_styles', array( $this, 'append_styles' ), 20, 1 );
	}

	/**
	 * Append the ShopOS Line email skin to WooCommerce's base email CSS. Never
	 * replaces $css — returns it with our block concatenated, so every
	 * WooCommerce default rule still applies (we only reskin brand surfaces).
	 *
	 * @param string $css WooCommerce's assembled email CSS.
	 * @return string
	 */
	public function append_styles( $css ) {
		return (string) $css . self::styles();
	}

	/**
	 * The email-safe ShopOS Line skin. LITERAL values only — no CSS custom
	 * properties, no @media, no logical properties (email clients drop all
	 * three); the hex values are the resolved ShopOS Line palette (ink #1b1b1b,
	 * mute #6b6b6b, hairline #e6e6e2, paper #ffffff, paper-alt #faf9f7). Public
	 * static so EmailSkinTest can assert the email-safety contract directly.
	 *
	 * @return string
	 */
	public static function styles() {
		return '
			#wrapper { background-color: #f1efea; }
			#template_container { border: 1px solid #e6e6e2; border-radius: 8px; box-shadow: 0 1px 4px rgba(17,17,17,0.06); }
			#template_header { background-color: #1b1b1b; border-radius: 8px 8px 0 0; }
			#template_header h1, #template_header h1 a { color: #ffffff; font-family: "Assistant", "Heebo", Arial, sans-serif; font-weight: 700; letter-spacing: 0.2px; }
			#body_content { background-color: #ffffff; }
			#body_content, #body_content td, #body_content p { color: #1b1b1b; font-family: "Heebo", "Assistant", Arial, sans-serif; }
			#body_content h2, #body_content h3 { color: #111111; font-family: "Assistant", "Heebo", Arial, sans-serif; }
			#body_content a { color: #1b1b1b; text-decoration: underline; }
			.order_details th { background-color: #faf9f7; color: #1b1b1b; border-bottom: 1px solid #e6e6e2; }
			.order_details td { border-bottom: 1px solid #e6e6e2; }
			#template_footer #credit { color: #6b6b6b; font-family: "Heebo", "Assistant", Arial, sans-serif; }
		';
	}
}
