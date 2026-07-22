# Side Cart — hooks

All additive; the module ships no feature flag (the module-enable toggle is the
kill-switch).

## Filters

| Hook | Since | Default | Purpose |
|------|-------|---------|---------|
| `shopos_core/side_cart/free_ship_min` | 1.55.0 | smallest enabled free-shipping `min_amount` (0 = none) | Pin/override the threshold the progress meter measures against. |
| `shopos_core/side_cart/recommendations` | 1.55.0 | the cart's cross-sell product ids | Supply your own recommendation product ids (capped at 4). |
| `shopos_core/side_cart/fragments` | 1.55.0 | `{ '.shopos-side-cart__body' => <body html> }` | Adjust the WC cart-fragment map the drawer body ships in. |

## JS

- `window.ShopOSSideCartOpen()` — open the drawer and refresh its body. Other
  modules can call this to route "view cart" affordances into the drawer.
- Binds WooCommerce's jQuery `added_to_cart` event (when jQuery is present) to
  open on add-to-cart.

## AJAX

- Action `shopos_core_side_cart` (public, nonce + per-IP rate limit). Body param
  `op` ∈ `refresh | apply_coupon | remove_coupon | remove_item | restore_item`;
  returns `{ html, count }`.
