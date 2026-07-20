# Account template QA — My Account (§11-B surface 3, §11 Ruling 7)

Per-template acceptance artifact for the theme-owned My Account pages behind
`theme.template_account`. Shares the `tools/qa/hook-listener.php` harness.

## Mechanism (shared with cart)

Same flag-gated `woocommerce_locate_template` filter as the cart, **generalized**
to `ShopOS_Theme::locate_woo_template()` — one callback, a claim arm per surface
in `woo_surface_enabled()` (`cart/` → `cart_enabled()`, `myaccount/` →
`account_enabled()`). Registered unconditionally (Ruling 7.1).

- **Flag OFF** → passthrough → WooCommerce's own `myaccount/*` render.
  Byte-identical (Ruling 6). Forks live at `templates/woo/myaccount/`, never the
  auto-located `{theme}/woocommerce/` path (§11.3).
- **Flag ON** → the two forked **structural** templates redirect:
  `my-account.php` (adds the `.shopos-account` sidebar-grid shell) and
  `navigation.php` (the account nav rail). Everything else under `myaccount/`
  falls through `is_readable` to WooCommerce — the **content** (dashboard,
  orders, view-order, downloads, payment-methods, addresses) and the
  **auth/payment forms** (login, edit-account, add-payment-method, password
  reset) are **CSS-skinned** under `.shopos-account`, not forked, so WC keeps
  ownership of every nonce + gateway field.

Account is the `[woocommerce_my_account]` **shortcode** (no My-Account block in
WC core), so Ruling 9 is low-risk here — unlike cart, flag-on renders on a
standard store.

Single pinned flag read: `ShopOS_Theme::account_enabled()` (FQCN).

## Acceptance checklist

| Check | How |
|---|---|
| Flag-OFF passthrough byte-identical | `render-diff.sh` the account page vs a pre-account baseline → exit 0 |
| Flag-ON structural render | `.shopos-account` wrapper; `.shopos-account__nav` rail; `.shopos-account__content`; nav is a sidebar beside content |
| Forms stay WC-owned | login / edit-account still carry their WC nonces + gateway fields (not forked) — CSS-skinned only |
| Hook census identical both states | `hook-listener` diff of `woocommerce_account_navigation` / `_account_content` / `_before/after_account_navigation` → identical |
| Endpoints | dashboard / orders / view-order / downloads / edit-address / edit-account / payment-methods all render under the scope |
| Asset gated | `shopos-account.css` present only flag-ON **and** only on `is_account_page()`; no JS |
| Cart arm isolation | account-on + cart-off ⇒ a cart template does NOT redirect (each arm gated by its own flag) |
| RTL (he_IL) | logical properties — verify nav rail + tables mirror; owner screenshot |

## wp-env script

```sh
ENV=~/shopos-wp-env; wpc() { "$ENV/shopos-env.sh" wp "$@"; }   # define a fn — zsh won't word-split
BASE=http://localhost:8888
AID=$(wpc option get woocommerce_myaccount_page_id)
cp tools/qa/hook-listener.php "$ENV/wp-content/mu-plugins/"

# A logged-in session is needed to see the account dashboard (vs the login form).
# Flag-OFF passthrough identity
wpc shopos flags set theme.template_account off
bash tools/render-diff.sh compare "$BASE/?page_id=$AID" /path/to/pre-account-baseline   # exit 0

# Flag-ON render
wpc shopos flags set theme.template_account on
curl -sL "$BASE/?page_id=$AID" | grep -c 'class="shopos-account"'        # shell
curl -sL "$BASE/?page_id=$AID" | grep -c 'shopos-account__nav'           # nav rail

# Restore
wpc shopos flags set theme.template_account off
rm "$ENV/wp-content/mu-plugins/hook-listener.php"
```

## Results — surface-3 PR

| Check | Result |
|---|---|
| PHPUnit `AccountTemplateTest` (`@group theme`) | _pending run_ |
| Flag-OFF passthrough (render-diff) | _pending_ |
| Flag-ON structural render + form ownership | _pending_ |
| Owner screenshots + RTL (he_IL) | **pending — pre-flip acceptance gate** (wp-env is en_US) |
