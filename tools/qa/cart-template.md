# Cart template QA — cart page (§11-B surface 2, §11 Ruling 7)

Per-template acceptance artifact for the theme-owned cart page behind
`theme.template_cart` (`shopos-theme/templates/woo/cart/*.php`). Shares the
`tools/qa/hook-listener.php` harness with pdp/plp/chrome.

## Mechanism (differs from PDP/PLP and from chrome)

The cart is a WooCommerce page rendering the `[woocommerce_cart]` shortcode; the
markup comes from WC's `cart/*.php` sub-templates. So this surface uses **neither**
the `template_include` loader (that seizes the whole page — it would drop the
chrome and any page content) **nor** the chrome child-template hierarchy. It uses a
flag-gated **`woocommerce_locate_template` filter**:

- **Flag OFF** → `ShopOS_Theme::locate_cart_template()` returns the resolved path
  untouched → WooCommerce's own `cart/*.php` render. Byte-identical to today
  (Ruling 6). The theme forks live at `templates/woo/cart/`, **not** the
  auto-located `{theme}/woocommerce/cart/` path, so file presence alone never
  changes the render (§11.3).
- **Flag ON** → any `cart/*` template the theme ships is redirected to
  `templates/woo/cart/<name>`. Templates not shipped (e.g. `cart/mini-cart.php`)
  fail `is_readable` and fall through to WC — the mini-cart is a header-widget
  surface, not the cart page, deliberately out of scope.

Full forked set (WC source versions in each file header): `cart.php`,
`cart-empty.php`, `cart-totals.php`, `cart-shipping.php`, `shipping-calculator.php`,
`proceed-to-checkout-button.php`, `cross-sells.php`. Every WC hook, filter,
class/id and the `woocommerce-cart` / `woocommerce-shipping-calculator` nonces are
preserved verbatim; the reskin is additive `shopos-cart__*` classes only.

Single pinned flag read: `ShopOS_Theme::cart_enabled()` (FQCN — the theme is
unnamespaced). Registered unconditionally so the callback census is identical in
both flag states (Ruling 7.1).

## Ruling 9 caveat (block cart) — carry, don't solve

`woocommerce_locate_template` fires only for the **shortcode** cart. A store whose
cart page uses the **Cart block** renders its own React markup and this filter
never fires — flag-on is inert there. That is page content, not a template:
block-cart stores need a per-store block→shortcode content-migration under Hard
Rule #3. Not in this PR.

## Acceptance checklist

| Check | How |
|---|---|
| Flag-OFF passthrough byte-identical | `render-diff.sh compare` the cart page vs a pre-cart baseline (determinism harness on) → exit 0 |
| Flag-ON renders theme cart | `.shopos-cart` wrapper; `.shopos-cart__table` items; `.shopos-cart__totals`; `.shopos-cart__checkout-button`; single form, `woocommerce-cart-nonce` present |
| Hook census identical both states | `hook-listener.php` diff of the cart-page hook stack flag-off vs flag-on → identical (Ruling 7.2) |
| Empty cart | empty the cart → `.shopos-cart--empty` + return-to-shop link; `woocommerce_cart_is_empty` fires |
| Coupon path | apply/remove a coupon → totals update, no double-POST (busy-guard), nonce round-trips |
| Shipping calc | enable shipping calc → `.shopos-cart__calc` country/state selects work (WC JS bound), `calc_shipping` nonce round-trips |
| Cross-sells | a product with cross-sells → `.shopos-cart__cross-sells` renders `content-product` cards |
| Assets gated | `shopos-cart.css` / `.js` present only flag-ON **and** only on the cart page (`is_cart()`) |
| Update-cart | change a qty + Update → totals recompute; `update_cart` button value reaches WC (aria-disabled, never `disabled`) |
| RTL (he_IL) | logical properties — verify table/totals/calc mirror; owner screenshot |

## wp-env script

```sh
ENV=~/shopos-wp-env; wpc() { "$ENV/shopos-env.sh" wp "$@"; }   # NOTE: define a fn — zsh won't word-split "$ENV/shopos-env.sh wp"
BASE=http://127.0.0.1:8888
cp tools/qa/hook-listener.php "$ENV/wp-content/mu-plugins/"     # determinism harness

CART=$(wpc option get woocommerce_cart_page_id)                 # the cart page id
# Put a product in the session cart for a populated render (see wp-env-cart-qa memory
# for the WC()->cart bootstrap null-cart gotcha), then hit the cart URL with -L.

# Flag-OFF passthrough identity
wpc shopos flags set theme.template_cart off
bash tools/render-diff.sh compare "$BASE/?page_id=$CART" /path/to/pre-cart-baseline   # exit 0

# Flag-ON render
wpc shopos flags set theme.template_cart on
curl -sL "$BASE/?page_id=$CART" | grep -c 'class="shopos-cart"'          # wrapper
curl -sL "$BASE/?page_id=$CART" | grep -c 'shopos-cart__totals'          # totals rail
curl -sL "$BASE/?page_id=$CART" | grep -c 'woocommerce-cart-nonce'       # nonce preserved

# Restore env as-found
wpc shopos flags set theme.template_cart off
rm "$ENV/wp-content/mu-plugins/hook-listener.php"
```

## Results — surface-2 PR

| Check | Result |
|---|---|
| PHPUnit `CartTemplateTest` (`@group theme`) | _pending run_ |
| Flag-OFF passthrough (render-diff) | _pending_ |
| Flag-ON render + hook census identity | _pending_ |
| Empty / coupon / shipping-calc / cross-sells paths | _pending_ |
| Owner screenshots + RTL (he_IL) | **pending — pre-flip acceptance gate** (wp-env is en_US) |
