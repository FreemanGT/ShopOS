# ProductSlider Grid Mode — Parity Audit vs Elementor Pro Products Widget

**Date**: 2026-06-11
**Wave**: 7.1 (see `/docs/roadmap.md` Wave 7, `/docs/decisions-2026-04-28.md` §6.1)
**Scope**: `freeman_product_slider` with `display_mode = grid` (`freeman-core/src/Modules/ProductSlider/Widget.php` + `assets/css/product-slider.css`) compared against Elementor Pro's **Products** widget (the reference grid on this store's archive pages).

## Summary

The card layer is already at parity by construction: both grids render `wc_get_template_part( 'content', 'product' )` cards inside a `.woocommerce`-scoped `ul.products.columns-N`, so the WC hook stack, plugin integrations (VariationSwatches picker, sale-flash customisers, quick-view buttons), and `.woocommerce ul.products li.product …` plugin CSS match both grids identically. The gaps are in the **container layer**: two real defects (G1, G2) and a set of intentional divergences documented as no-fix.

## Findings

### G1 — MEDIUM — fractional mobile columns produce invalid CSS in grid mode → fix (7.1a, core)

The `per_view_mobile` control allows fractional values (default **1.4**) so the slider can show a "peek" of the next card. Grid mode feeds the same value into:

```397:399:freeman-core/src/Modules/ProductSlider/assets/css/product-slider.css
	.cs.cs-products.cs-grid-mode .cs-grid.products {
		grid-template-columns: repeat(var(--cs-per-mobile), minmax(0, 1fr));
	}
```

`repeat()` requires an integer count. With `--cs-per-mobile: 1.4` the declaration is invalid at computed-value time, `grid-template-columns` collapses to its initial `none`, and the mobile grid renders **one column** regardless of the control — e.g. a configured `2.5` silently renders 1 column instead of ~3. Elementor Pro's grid always honours its integer mobile column control.

**Fix**: in `Widget::render()`, when `display_mode = grid`, coerce the mobile value to a whole number (`max( 1, round() )`) before emitting `--cs-per-mobile`. Slider mode keeps the float (peek is slider-only by design). No control change, no saved-data change.

### G2 — MEDIUM — theme mobile-columns Customizer rule hijacks Freeman containers on archives → fix (7.1b, theme)

`freeman-theme/inc/woocommerce.php` emits (only on product archives, only when the admin opted in):

```css
@media (max-width:767px){.woocommerce ul.products,.woocommerce ul.products.elementor-grid{display:grid !important;grid-template-columns:repeat(N,minmax(0,1fr)) !important;}}
```

`.woocommerce ul.products` also matches the ProductSlider's `ul.cs-track.products` (slider) and `ul.cs-grid.products` (grid) because the widget wrapper carries `.woocommerce`. On an archive page containing the widget (e.g. an Elementor archive template section, or `source = current_query`):

- **Slider mode breaks outright**: the flex track is forced to `display: grid !important`, killing drag/overflow scrolling.
- **Grid mode loses its per-instance mobile column control** to the site-wide value.

The rule is meant for the main archive grid (stock WC or `elementor-grid`), not for Freeman's self-managing containers.

**Fix**: exclude the widget containers in the emitted selector — `.woocommerce ul.products:not(.cs-track):not(.cs-grid)` (both selector branches). Behaviour on the main archive grid is unchanged.

## Intentional divergences — documented, no fix

| # | Divergence | Assessment |
|---|---|---|
| D1 | **Fixed image height** (`--cs-card-h`, default 320px, `object-fit: cover`) vs Elementor Pro's natural `woocommerce_thumbnail` aspect ratio. | By design — the widget exposes an "Image height" control so editorial rows align across cards with mixed-ratio photos. Not a defect. |
| D2 | **No pagination / results count / orderby header.** Elementor Pro's Products widget offers pagination; ours caps at `limit` (max 24). | By design — this is an editorial widget for curated sections; the catalog archive grid (pagination, sort, result counts) is owned by Elementor Pro + WooCommerce. InfiniteScroll/ShopFilters operate on that archive grid, not on this widget. |
| D3 | **Empty query renders nothing on the storefront** (editor-only notice). | Matches Elementor Pro's Products widget, which also renders nothing when the query is empty. Parity OK. |
| D4 | **Sale flash restyled** as an accent pill (top-left, RTL-flipped) vs WC's green circle. | Design language; Elementor Pro stores restyle the flash too. The `show_sale_badge` toggle removes WC's hook per-instance, which third-party flash plugins respect. |
| D5 | **Hover ring + image scale** vs Elementor Pro's unstyled hover. | Design language, hover-capability-gated. |
| D6 | **Gap from `--cs-gap` control** (default 20px) vs the theme's `--fm-card-gap` token on the archive grid. | Per-instance control wins at higher specificity by design; an editor can match the token value manually. |

## Deferred / watch list

- **W1 — InfiniteScroll container detection**: the generic `ul.products` fallback in `infinite-scroll.js` could select a ProductSlider UL on an archive whose main grid sits in an unrecognized wrapper. On this store the archive grid matches the Elementor-Pro-specific selectors first, so there is no live exposure. If IS ever appends into a `.cs-*` container, add `.cs-track`/`.cs-grid` to its exclusion list (InfiniteScroll module change — out of Wave 7 scope).

## Verdict

Card-level parity: **already met** (shared `content-product` render path — the quick-view trigger hook in Wave 7.2 will behave identically on both grids). Container-level parity: met after **7.1a** (core patch) and **7.1b** (theme patch).
