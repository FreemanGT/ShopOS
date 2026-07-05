# ProductPage — public surface

## Feature flags

| Flag | Default | Gates |
| --- | --- | --- |
| `freeman_core_product_page_coupon_notice_enabled` | off | Coupon-price notice (hook render + shortcodes + assets) |
| `freeman_core_product_page_stock_urgency_enabled` | off | Low-stock urgency badge (hook render + shortcodes + assets) |
| `freeman_core_product_page_layout_enabled` | off | Single-product template takeover + designed-page assets |

## Filters

| Hook | Since | Description |
| --- | --- | --- |
| `freeman_core/product_page/show_coupon_notice` | 1.22.0 | `(bool $show, WC_Product $product)` — veto the coupon notice per product. |
| `freeman_core/product_page/coupon_notice_html` | 1.22.0 | `(string $html, WC_Product $product)` — rendered coupon-notice markup (`''` = hidden). |
| `freeman_core/product_page/show_stock_urgency` | 1.22.0 | `(bool $show, WC_Product $product)` — veto the urgency badge per product. |
| `freeman_core/product_page/urgency_messages` | 1.22.0 | `(array<int,string> $messages, WC_Product $product)` — per-variation urgency texts (variation id ⇒ text); empty map = no badge. |

## Shortcodes

| Tag | Description |
| --- | --- |
| `[freeman_discounted_price]` | Coupon-price notice (product pages; needs the coupon_notice flag). |
| `[discounted_price]` | Legacy alias of the above — matches the owner's original snippet so existing Elementor placements keep working. |
| `[freeman_stock_urgency]` | Low-stock urgency badge (variable products; needs the stock_urgency flag). |
| `[stock_urgency]` | Legacy alias of the above. |

## Template override

The takeover template is theme-overridable at
`freeman/product_page/single-product.php`.

## Settings (option keys)

`freeman_core_product_page_coupon_code`, `freeman_core_product_page_coupon_percent`,
`freeman_core_product_page_urgency_max`, plus one
`freeman_core_product_page_label_<key>` option per editable string
(see `Labels::defaults()`).
