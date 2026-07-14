# Quick View — Public API

## Feature flag

Everything (trigger, assets, drawer shell, AJAX endpoint) is gated by
`shopos_core_quick_view_frontend_enabled` (default `false`). Flag-off boots
nothing.

```bash
wp option update shopos_core_quick_view_frontend_enabled 1   # enable
wp option update shopos_core_quick_view_frontend_enabled 0   # disable (rollback)
```

## Filters

### `shopos_core/quick_view/show_trigger`
Whether the quick-view trigger renders for a product card.

```php
add_filter( 'shopos_core/quick_view/show_trigger', function ( $show, $product ) {
    return $product->is_in_stock() ? $show : false;
}, 10, 2 );
```

| Param | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to render. Default `true`. |
| `$product` | `WC_Product` | Current loop product. |

Since 1.13.0.

### `shopos_core/quick_view/drawer_html`
Filters the rendered drawer-content markup before the AJAX endpoint returns it.

| Param | Type | Description |
|---|---|---|
| `$html` | `string` | Drawer content HTML. |
| `$product_id` | `int` | Product id. |

Since 1.13.0.

## JS events

### `shopos_core_quick_view_loaded`
Fired on `document` (both as a jQuery event and a native `CustomEvent`)
after drawer content is injected. VariationSwatches listens to this to
(re)initialise an injected buy box; other plugins can do the same.

## AJAX endpoint

`admin-ajax.php?action=shopos_core_quick_view_product` (`wp_ajax_` +
`wp_ajax_nopriv_`). POST `product_id` + `_ajax_nonce` (nonce action
`shopos_core_quick_view_product`). Nonce-checked and per-IP rate-limited
(30 req / 60 s). Returns `{ success, data: { html } }`. Only published,
catalog-visible products are served.

## Template override

Drawer content: copy
`src/Modules/QuickView/templates/drawer-content.php` to
`<your-theme>/shopos/quick_view/drawer-content.php`.

## Settings

All storefront strings (trigger aria-label, drawer title, close, full-details
link, loading, error) are editable on ShopOS → Quick View
(`shopos_core_quick_view_label_<key>` options); a blank field falls back to
the English default.

## Asset handles

`shopos-core-quick-view` (style + script). Dequeue both to remove the
storefront surface without disabling the module.
