# Transactional email skin QA — emails (§11-B surface 6 — CLOSES §11-B)

Per-surface acceptance artifact for the ShopOS Line transactional-email skin
behind `theme.style_emails`. This is the ONLY §11-B surface that is **Core-side,
not theme-side**.

## Mechanism (Core-side, skin-only)

WooCommerce emails send from cron / webhook / REST contexts where the active
theme may not be ShopOS Line, so a theme-level email-template override would
vanish (decisions §11 line 304). This surface therefore lives in Core:
`ShopOS\Core\Core\Email_Skin`, booted from `Plugin::boot()` at method scope
(so it registers in cron/REST send contexts) **only when `theme.style_emails`
is on**.

Skin-only like the checkout surface — `Email_Skin::boot()` hooks
`woocommerce_email_styles` at priority 20 and **appends** email-safe CSS
(`$css . Email_Skin::styles()`, never replaces) that WooCommerce inlines onto
the email markup via **Emogrifier**. It forks **NO** email templates, so there
is no WooCommerce email `@version` to chase.

**KEY CONSTRAINT:** email clients (Outlook / Gmail / Apple Mail) do not support
CSS custom properties, `@media`, or logical properties — so `styles()` is
**literal** hex/px values (the resolved ShopOS Line palette), not the
`--shopos-ui-*` token CSS the storefront surfaces use. Unit-pinned by
`EmailSkinTest::test_appended_css_is_email_safe`.

- **Flag OFF** → the filter is never added → the WooCommerce default email
  styling with nothing appended, byte-identical (Ruling 6).
- **Flag ON** → the ShopOS Line brand styles are appended to every WooCommerce
  transactional email (new order, processing, completed, invoice, password
  reset, …). Core-only: does NOT need the ShopOS theme active.

## Acceptance checklist

| Check | How |
|---|---|
| Flag-OFF byte-identical | send a test email flag-OFF → identical to a pre-skin capture (no ShopOS rules inlined) |
| Flag-ON skin applied | send a test email flag-ON → header band ink `#1b1b1b`, body ink text, hairline `#e6e6e2` table borders, muted `#6b6b6b` footer |
| Appends, never replaces | flag-ON email still carries WooCommerce's own base styling (order table, structure) — we only reskin brand surfaces |
| Cron-safe | trigger an email from a cron/webhook context (e.g. a scheduled order status change) → skin still applied (theme-independent) |
| Email-safe CSS | inlined styles contain no `var(`, `@media`, `:root`, or logical props (Emogrifier drops unsupported rules; the skin uses none) |
| RTL (he_IL) | WooCommerce's own email RTL still governs direction; verify the ShopOS colours/typography read correctly in a Hebrew email |

## wp-env script

```sh
ENV=~/shopos-wp-env; wpc() { "$ENV/shopos-env.sh" wp "$@"; }   # define a fn — zsh won't word-split

# Flag-OFF baseline: send a WC test email (customer new account / any order email)
wpc shopos flags set theme.style_emails off
wpc wc email --help >/dev/null 2>&1   # confirm WC email CLI surface; else trigger via an order status change

# Flag-ON skin: flip and re-send the same email, compare the two inboxes (MailHog/mailpit in wp-env)
wpc shopos flags set theme.style_emails on
# → the ShopOS Line skin is inlined; flag-OFF inbox is unchanged.

# Restore
wpc shopos flags set theme.style_emails off
```

Inspect the rendered email in the wp-env mail catcher (MailHog/mailpit) or via
`Preview` in **WooCommerce → Settings → Emails**; the flag flips the appended
styles live per send.

## Results — surface-6 PR

| Check | Result |
|---|---|
| PHPUnit `EmailSkinTest` (`@group theme`) | **PASS** — 5 cases, full suite 1068/3361 green (php@8.3) |
| Flag-OFF byte-identical | _pending — pre-flip_ |
| Flag-ON skin, appends not replaces | _pending — pre-flip_ |
| Cron-safe (email from cron/webhook) | _pending — pre-flip_ |
| Owner screenshots + RTL (he_IL) | **pending — pre-flip acceptance gate** (wp-env is en_US) |

## Rollback

```sh
wp option update shopos_core_theme_style_emails_enabled 0
```
