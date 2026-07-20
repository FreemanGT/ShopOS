# Checkout skin QA — checkout (§11-B surface 5, §11 Ruling 9)

Per-surface acceptance artifact for the theme checkout skin behind
`theme.style_checkout`. Shares the `tools/qa/hook-listener.php` harness.

## Mechanism (skin-only — the family exception)

Unlike surfaces 1–4 this forks **NO templates** (Ruling 9 resolved-as-moot,
owner 2026-07-20). It is a single flag-gated stylesheet enqueue in
`ShopOS_Theme::enqueue_assets()`, on `is_checkout()` only. WooCommerce keeps
ownership of every checkout field, nonce, and payment gateway — the theme only
restyles them by their stable classes (the My Account CSS-skin doctrine applied
to the whole page).

- **Flag OFF** → no `shopos-checkout` asset, no listener → the current checkout
  render, byte-identical (Ruling 6). Zero new assets anywhere.
- **Flag ON** → `shopos-checkout.css` enqueues **only** on `is_checkout()`. No
  JS (WC owns checkout behaviour). No `locate_woo_template` arm — checkout is not
  a `woo_surface_enabled()` surface, so `checkout/*` templates always pass
  through to WooCommerce (unit-pinned by `CheckoutSkinTest`).

Because it is skin-only it works on **both** transports — the shortcode
(`[woocommerce_checkout]`, `form.checkout` classes) and the block checkout
(`.wc-block-checkout` classes) — so **no per-store content-migration is ever
required**, and `cart_checkout_blocks` stays declared `true`.

Single pinned flag read: `ShopOS_Theme::checkout_enabled()` (FQCN).

## Acceptance checklist

| Check | How |
|---|---|
| Flag-OFF byte-identical | `render-diff.sh` the checkout page vs a pre-skin baseline → exit 0 (no `shopos-checkout` asset) |
| Flag-ON asset gated | `shopos-checkout.css` present only flag-ON **and** only on `is_checkout()`; absent on every other page; **no JS** |
| Shortcode checkout skin | on a `[woocommerce_checkout]` page: fields/place-order CTA restyled; nonces + gateway fields intact and submit works end-to-end |
| Block checkout skin | on a `wp:woocommerce/checkout` page: place-order CTA + headings restyled; block still functions (restyle only, no relayout) |
| Hook census identical both states | `hook-listener` diff of `woocommerce_checkout_*` / `woocommerce_review_order_*` → identical (no forked partials means WC fires everything) |
| Ruling-10 fonts warning | flag-ON + `theme.fonts_selfhost` OFF on a checkout request → one `warning` Logger row; turn fonts on first |
| RTL (he_IL) | logical properties — verify field grid + order-review mirror; owner screenshot |

## wp-env script

```sh
ENV=~/shopos-wp-env; wpc() { "$ENV/shopos-env.sh" wp "$@"; }   # define a fn — zsh won't word-split
BASE=http://localhost:8888
CID=$(wpc option get woocommerce_checkout_page_id)
cp tools/qa/hook-listener.php "$ENV/wp-content/mu-plugins/"
# A non-empty cart is needed to render the checkout form (vs the empty notice).

# Flag-OFF identity
wpc shopos flags set theme.style_checkout off
bash tools/render-diff.sh compare "$BASE/?page_id=$CID" /path/to/pre-skin-baseline   # exit 0
curl -sL "$BASE/?page_id=$CID" | grep -c 'shopos-checkout'                           # 0

# Flag-ON skin
wpc shopos flags set theme.style_checkout on
curl -sL "$BASE/?page_id=$CID" | grep -c 'css/shopos-checkout.css'                   # 1 (asset enqueued)

# Restore
wpc shopos flags set theme.style_checkout off
rm "$ENV/wp-content/mu-plugins/hook-listener.php"
```

## Results — surface-5 PR

| Check | Result |
|---|---|
| PHPUnit `CheckoutSkinTest` (`@group theme`) | **PASS** — 7 cases, full suite 1063/3320 green (php@8.3) |
| Flag-OFF byte-identical (render-diff) | _pending — pre-flip_ |
| Flag-ON skin, shortcode + block, submit works | _pending — pre-flip_ |
| Ruling-10 fonts warning | _pending — pre-flip_ |
| Owner screenshots + RTL (he_IL) | **pending — pre-flip acceptance gate** (wp-env is en_US) |
