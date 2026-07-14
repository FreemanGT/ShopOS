# Card Image Effects — Public API

Module id `hover_swap` (frozen). Enhances the product image on shop / archive
cards. The **Card image mode** setting picks one behaviour:

- **None** — leave the card image alone.
- **Hover swap** — hovering a card cross-fades the main image to the product's
  second (gallery) image. Pure CSS; mobile shows the primary image only.
- **Gallery slider** — replaces the card image with a small swipeable slider of
  all the product's images (swipe is native scroll-snap; optional hover
  arrows).

Both modes inject on `woocommerce_before_shop_loop_item_title`, which the
standard WC loop and the ShopOS ProductSlider grid both render through
`content-product.php`. This is **not** the VariationSwatches `card_image_swap`
feature (swatch-click → variation image).

## Activation

One control, no feature flags: **enable the module** (ShopOS → Modules) and
**pick the Card image mode** (ShopOS → Card Image Effects). The mode defaults
to `none`, so an enabled module shows nothing until a mode is chosen. The module
is also absent from `shopos_core_modules` on existing installs, so it stays off
until enabled there.

## Settings (ShopOS → Card Image Effects)

| Key (`shopos_core_hover_swap_…`) | Type | Default | Notes |
|---|---|---|---|
| `card_image_mode` | select | `none` | `none` / `hover_swap` / `gallery_slider` |
| `slider_arrows` | checkbox | `1` | Gallery slider — prev/next arrows (hover-reveal). Off = swipe only. |

## Filters

### `shopos_core/hover_swap/show`
Hover-swap mode only. Whether the hover overlay renders for a product card.

```php
add_filter( 'shopos_core/hover_swap/show', function ( $show, $product ) {
    return has_term( 'no-swap', 'product_cat', $product->get_id() ) ? false : $show;
}, 10, 2 );
```

| Param | Type | Description |
|---|---|---|
| `$show` | `bool` | Whether to render the overlay. Default `true`. |
| `$product` | `WC_Product` | Current loop product. |

Since 1.15.0.

## Injection points

- **Hover swap:** an overlay `<img>` echoed on
  `woocommerce_before_shop_loop_item_title` priority 11 (after WC's thumbnail).
- **Gallery slider:** swaps WC's default loop thumbnail
  (`woocommerce_template_loop_product_thumbnail`, removed) for the slider on the
  same hook priority 10. Single-image products fall back to a plain image.

## Asset handles

`shopos-core-hover-swap` (hover-swap CSS) · `shopos-core-card-slider`
(gallery-slider CSS + JS). Dequeue to remove a surface without disabling the
module.
