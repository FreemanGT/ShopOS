# Variation Swatches — Public API

The module bundles the original `shopos-variation-swatches` codebase, so the
public filters it always exposed still work under their original names. ShopOS
Core adds a namespaced layer on top.

## Filters (legacy names, still supported)

- `shopos_vs_color_swatch_markup` — filter per-swatch HTML output.
- `shopos_vs_sizes_markup`        — filter the size-pill row HTML.
- `shopos_vs_buy_box_enabled`     — return false to hide the buy-box on a product.
- `shopos_vs_shop_picker_enabled` — return false to hide the shop-grid picker.

## Filters (ShopOS Core namespaced)

### `shopos_core/swatches/color_map`
```php
apply_filters( 'shopos_core/swatches/color_map', array $map );
```
`$map` is `'term-slug' => '#hex'`. Overrides for the color-attribute dictionary.

### `shopos_core/swatches/attribute_is_color`
```php
apply_filters( 'shopos_core/swatches/attribute_is_color', bool $is_color, string $taxonomy );
```
Tell the module which extra product attributes should be rendered as color
swatches (defaults: `pa_color`, `pa_colour`, `pa_צבע`).

### `shopos_core/swatches/attribute_is_size`
```php
apply_filters( 'shopos_core/swatches/attribute_is_size', bool $is_size, string $taxonomy );
```
Same idea for size pills.

## Actions

### `shopos_core/swatches/buy_box_before` / `_after`
```php
do_action( 'shopos_core/swatches/buy_box_before', \WC_Product $product );
do_action( 'shopos_core/swatches/buy_box_after',  \WC_Product $product );
```
Inject badges, urgency messaging, bundled-product CTAs, etc.

## Template overrides
Themes (including ShopOS Theme itself) can override:
```
yourtheme/shopos-core/variation-swatches/shop-variation-pick.php
yourtheme/shopos-core/variation-swatches/variation-buy-box.php
```
ShopOS's template loader checks the child theme first, then the parent theme,
then falls back to the module's `legacy/templates/` copy.

## Settings location
WooCommerce → Settings → Products → Swatches (kept under its original key
`shopos_vs_settings` for continuity).
