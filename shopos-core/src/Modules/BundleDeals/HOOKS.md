# Bundle Deals — extension points

Always-on when the module is enabled (ShopOS → Dashboard). The module ships
**default OFF** and the enable toggle is the sole kill switch — this is the
first module in the suite that prices the cart, so disabling it removes every
cart hook, all markup, the assets and the public endpoint.

## Filters

| Filter | Since | Args | Purpose |
|---|---|---|---|
| `shopos_core/bundle_deals/active_bundles` | 1.46.0 | `array $bundles` | Filter the bundle set evaluated for a cart calculation (add/remove/reorder). |
| `shopos_core/bundle_deals/apply_discount` | 1.46.0 | `array $discount`, `array $cart_item`, `array $bundles` | Adjust or veto one line's resolved discount (`{ unit, bundle_id, saved }`). Return an empty value to skip the line. |
| `shopos_core/bundle_deals/savings_html` | 1.46.0 | `string $tag`, `array $cart_item`, `float $saved` | Filter the cart-line "you save" tag markup. |

## Storefront

- Shortcode `[shopos_bundle_deals]` — the product-page offer block (the same
  output the summary hook auto-places at `woocommerce_single_product_summary`
  priority 25, and the `ShopOS Bundle Deals` Elementor widget renders).
- Elementor widget id `shopos_bundle_deals` (frozen).

## AJAX

- `shopos_core_bundle_deals_add` (public, nonce `shopos_core_bundle_deals_add`,
  per-IP rate limited) — adds the checked frequently-bought-together set to the
  cart. Payload: `products[]` ids.

## Options

- `shopos_core_bundle_deals_bundles` — the bundle definition list (managed by
  the builder; normalised by `Bundle_Config::sanitize()`; portable via the
  Store Blueprint `bundle` surface).
- `shopos_core_bundle_deals_label_*` — owner-editable storefront wording.

## Templates

- `templates/admin-builder.php` — the admin builder repeater (not
  theme-overridable; it is an admin surface).

## Pricing model

Every discount is a per-line effective **unit price** (`set_price()`), never a
fee or a phantom free line item. Partial-quantity discounts (BOGO, mix-&-match
fixed price) blend into the unit: `((qty − d)·base + d·disc) / qty`. A line
that matches several bundles takes the single **largest** saving — discounts
never stack — and a bundle never raises a price or drops it below zero. The
math is recalc-safe: base prices are read from fresh product objects, so
WooCommerce re-firing `before_calculate_totals` never compounds a discount.
