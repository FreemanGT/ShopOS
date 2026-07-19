# ShopOS — Module Catalog

**ShopOS** is a modular WooCommerce theme + plugin suite: a Hello-Elementor child theme (`shopos-theme`) for presentation, a single plugin (`shopos-core`) that ships every feature as an independently togglable module, and an optimization plugin (`shopos-digital`). This document is the canonical list of every ShopOS module — what is **built**, what is **planned**, and how the target feature set maps onto the code.

**Goal:** ~20 production modules covering the full WooCommerce storefront lifecycle (discover → decide → buy → account → retain), localized for the Israeli market (Hebrew/RTL UI, shekel pricing).

- **Built today:** 16 modules in `shopos-core/src/Modules/`
- **Planned:** 8 net-new + 1 consolidation to close the gap to a complete storefront OS (see [TODO.md](TODO.md) for the build queue, [roadmap.md](roadmap.md) for strategy)
- **Legend:** ✅ Built · 🟡 Partial (exists, needs consolidation/expansion) · ⬜ Planned

---

## Category map

| # | Category | Built | Planned |
|---|----------|-------|---------|
| 1 | Storefront & Discovery | Search, ShopFilters, InfiniteScroll, CategorySlider, ProductSlider, HoverSwap | — |
| 2 | Product & Conversion | ProductPage, QuickView, VariationSwatches, CheapestDefaultVariation, RestockNotify | Advanced Add-to-Cart, Product Reviews |
| 3 | Cart & Checkout | — | Side Cart, Checkout |
| 4 | Sales & Promotion | Bundle Deals | Fortune Wheel, Product Badges, Flash Sale Banner |
| 5 | Customer Account | MyAccount | — |
| 6 | Merchandising & Ops | ProductFeed, VariableStockFix | Bulk Price Editor, Custom Email Templates |
| 7 | Experience & Platform | PageTransitions | — |

---

## 1. Storefront & Discovery

### ✅ Search — `src/Modules/Search/`
In-house full-text product search engine (replaced Advanced Woo Search). FULLTEXT index (`shopos_search_index`), ranked `MATCH…AGAINST` with prefix/infix/SKU-shaped boosts, debounced live dropdown (images, price, SKU), `[shopos_search]` shortcode, and an engine-driven results page that feeds ShopFilters. Mobile floating command-palette overlay. In-stock filtering everywhere. Always-on.

### ✅ ShopFilters — `src/Modules/ShopFilters/`
AJAX/reload faceted filtering for shop & category archives. Own index (`shopos_shop_filter_index`) with per-variation in-stock truth, category tree, price bands, on-sale/in-stock flags, refined pill UI + mobile drawer, filtered-URL SEO policy (noindex/canonical), transient facet cache with event-driven invalidation. Constrains both the main query and Elementor ProductSlider grids. Always-on.

### ✅ InfiniteScroll — `src/Modules/InfiniteScroll/`
Archive infinite scroll with real-card-measured loading skeletons, back/forward scroll restoration (per-archive anchored snapshots), and a clean-URL default (no `/page/N/` pushState) so shared links stay canonical.

### ✅ CategorySlider — `src/Modules/CategorySlider/`
Elementor widget: homepage/landing category carousel with head-enqueued CSS (FOUC-free).

### ✅ ProductSlider — `src/Modules/ProductSlider/`
Elementor widget: product carousel **or** grid, current-query / archive / curated sources, popularity/rating/price orderby via `wc_product_meta_lookup`, DPR-correct `sizes`/`large` thumbnails, grid pagination.

### ✅ HoverSwap — `src/Modules/HoverSwap/`
Archive-card image behavior: cross-fade hover-swap to the second image, or a full scroll-snap gallery slider (swipe/drag). Routed by a single `card_image_mode` setting.

---

## 2. Product & Conversion

### ✅ ProductPage — `src/Modules/ProductPage/`
Single-product (PDP) takeover: editorial scroll-snap gallery (no lightbox), coupon-notice card with live discounted price, stock-urgency badges ("only X left"), trust block, relocated additional-information, buy-box color control. Template-loader ladder that detaches WC defaults cleanly.

### ✅ QuickView — `src/Modules/QuickView/`
Modal (desktop) / bottom-drawer (mobile) product preview: gallery, price, rating, attributes, stock, quantity, direct add-to-cart. Reuses the HoverSwap card-slider gallery component. Locale-aware render. → *reference: Product Quick View*

### ✅ VariationSwatches — `src/Modules/VariationSwatches/`
Live color/label/image swatches on **catalog cards and the PDP** — one click swaps image + price, full srcset support, stock-status preview, out-of-stock/unavailable tooltips, optional "name & price only" shop mode. → *reference: Live Color Swatches*

### ✅ CheapestDefaultVariation — `src/Modules/CheapestDefaultVariation/`
Auto-selects the cheapest (or configurable strategy) variation on load so the buy box shows a real price immediately.

### ✅ RestockNotify — `src/Modules/RestockNotify/`
"Notify me when back in stock" capture on out-of-stock products, locale-correct (RTL/LTR) emails, CSV export (formula-injection-safe), privacy export/erase hooks.

### ⬜ Advanced Add-to-Cart *(planned — consolidation)*
A unified rich buy-button that composes existing pieces (VariationSwatches selector + CheapestDefaultVariation + RestockNotify + ProductPage urgency) and adds: dedicated buy-now button, AJAX add-to-cart without refresh, inventory-hold "reserved for you" timer, sticky mobile buy bar. Mostly wiring over modules we already ship. → *reference: Advanced Add-to-Cart*

### ⬜ Product Reviews *(planned)*
Visual rating summary (average, per-star breakdown, % recommend), verified-purchase badge, customer photos, helpful voting, official store replies, live star+text+image submission form. → *reference: Product Reviews*

---

## 3. Cart & Checkout

### ⬜ Side Cart *(planned)*
Slide-out cart drawer: line items with images/qty, remove-with-undo, live subtotal/coupon/total, free-shipping progress meter, coupon field, recommended products, quick-checkout button, add-to-cart animations. → *reference: Side Cart*

### ⬜ Checkout *(planned)*
WooCommerce checkout replacement: compact 3-step or classic layout, Israeli settlement/city autocomplete, real-time field validation, upsell screen, trust badges. → *reference: Checkout*

---

## 4. Sales & Promotion

### ✅ Bundle Deals — `src/Modules/BundleDeals/`
Four bundle types — volume/tiered, BOGO, curated (frequently-bought-together) and mix-&-match — targeted by product/category/tag, built in a card-based visual builder. The suite's first cart-pricing module: per-line effective-price discounts via `woocommerce_before_calculate_totals` (recalc-safe from fresh base prices; best-wins, never stacks/raises/goes-negative). PDP block (summary hook 25 + `[shopos_bundle_deals]` shortcode + Elementor widget) with a tier table, BOGO badge, mix-&-match progress bar and an FBT "add bundle to cart" box; cart lines show struck original + "you save". Portable via the Store Blueprint `bundle` surface. Default OFF (module toggle is the kill switch). → *reference: Bundle Deals*

### ⬜ Fortune Wheel *(planned)*
Gamified spin-to-win for discounts/gifts + lead capture. Triggers: entry / scroll / exit-intent / post-purchase. Auto coupon application; configurable odds, colors, coupon validity, signup fields. → *reference: Fortune Wheel*

### ⬜ Product Badges *(planned)*
Auto badges by condition (sale / new / low stock / OOS / featured) + manual assignment. Shapes (ribbon, starburst, diamond, hexagon, speech bubble), animations (pulse, glow, metallic, glass, neon), per-corner + separate mobile placement, bilingual dual-line text. → *reference: Product Tags / Badges*

### ⬜ Flash Sale Banner *(planned)*
Per-visitor-timezone countdown to sale end, placements (sticky top / page top / before product / after headline), full design control, auto-hide at expiry, bilingual, sticky mode. → *reference: Flash Sale Banner*

---

## 5. Customer Account

### ✅ MyAccount — `src/Modules/MyAccount/`
WooCommerce "My Account" replacement: connected-user card, dashboard (order count, spend, recommendations), order history with tracking timeline, address management, favorites, account details. → *reference: Customer Account Area*

---

## 6. Merchandising & Ops

### ✅ ProductFeed — `src/Modules/ProductFeed/`
Scheduled product-feed generator (Google/Facebook shopping), atomic tmp→final promotion with failure-safe run recording.

### ✅ VariableStockFix — `src/Modules/VariableStockFix/`
Corrects WooCommerce variable-product stock display/behavior.

### ⬜ Bulk Price Editor *(planned — admin tool)*
Increase/decrease by % or fixed amount, smart rounding (whole / ending-in-9), targeting (all / category / tag / selected), regular vs sale price, full preview + rollback, batch processing for large catalogs. → *reference: Bulk Price Editor*

### ⬜ Custom Email Templates *(planned)*
WooCommerce email replacement: branded per-status templates (confirmation, shipped, completed, canceled, refund), block-based visual editor, dynamic variables, conditional blocks, live preview + test send. → *reference: Custom Email Templates*

---

## 7. Experience & Platform

### ✅ PageTransitions — `src/Modules/PageTransitions/`
Smooths the reload-transport UX: scrim+spinner overlay on grid pagination / search submit / filter navigation, cross-document View Transitions with a render-blocking readiness marker (no white flash), back/forward-aware (skips the fade on traversal so InfiniteScroll restore is clean). Off by default.

### Core infrastructure — `src/Core/`
Not a storefront module, but the platform every module plugs into: `Module_Registry`, `Module_Base`/`Module_Interface`, `Settings_Hub`, `Feature_Flags`, `Security` (nonces + rate limiting), `Logger` (final), `Migrations`, and legacy importers. New modules are auto-discovered from `src/Modules/`.

---

## Reference feature set → ShopOS mapping

Target feature set (14 items) from the market reference, mapped to ShopOS modules. Overlaps are marked; **New** rows are the build gap.

| # | Category | Reference feature | ShopOS module | Status |
|---|----------|-------------------|---------------|--------|
| 1 | Shopping | Checkout | Checkout | ⬜ New |
| 2 | Shopping | Live Search | Search | ✅ Built |
| 3 | Shopping | Side Cart | Side Cart | ⬜ New |
| 4 | Shopping | Product Quick View | QuickView | ✅ Built |
| 5 | Shopping | Advanced Add-to-Cart | Advanced Add-to-Cart (VariationSwatches + RestockNotify + CheapestDefaultVariation + ProductPage) | 🟡 Consolidate |
| 6 | Shopping | Live Color Swatches | VariationSwatches | ✅ Built |
| 7 | Sales | Bundle Deals | Bundle Deals | ✅ Built |
| 8 | Sales | Fortune Wheel | Fortune Wheel | ⬜ New |
| 9 | Sales | Product Tags / Badges | Product Badges | ⬜ New |
| 10 | Sales | Flash Sale Banner | Flash Sale Banner | ⬜ New |
| 11 | Account | Customer Account Area | MyAccount | ✅ Built |
| 12 | Trust | Product Reviews | Product Reviews | ⬜ New |
| 13 | Management | Bulk Price Editor | Bulk Price Editor | ⬜ New |
| 14 | Automation | Custom Email Templates | Custom Email Templates | ⬜ New |

**ShopOS modules with no reference equivalent** (our storefront/merchandising edge): ShopFilters, InfiniteScroll, CategorySlider, ProductSlider, HoverSwap, ProductPage, ProductFeed, VariableStockFix, PageTransitions.

---

## Build roadmap to ~20 modules

16 built + the following closes the gap to a complete storefront OS (~22 modules total):

1. **Side Cart** — highest cart-conversion lift; standalone.
2. **Checkout** — Israeli-market autocomplete + trust; large, standalone.
3. ✅ **Bundle Deals** — AOV growth; standalone. *(built 1.46.0)*
4. **Product Reviews** — trust/social proof; standalone.
5. **Product Badges** — merchandising; light, high-visibility.
6. **Flash Sale Banner** — promotion; light.
7. **Fortune Wheel** — lead capture + gamification; light-to-medium.
8. **Bulk Price Editor** — admin ops tool; standalone.
9. **Custom Email Templates** — retention/branding; medium.
10. **Advanced Add-to-Cart** — consolidation wave over existing buy-box modules (not net-new code, mostly).

Sequencing note: Side Cart → Checkout is the primary revenue path and should lead. Badges / Flash Sale / Fortune Wheel are light promotional modules that can slot between the larger builds.

---

## Conventions for a new module

Follow the existing pattern (see `src/Core/Module_Base.php` and any current module):

1. Create `src/Modules/<Name>/` with a `Module.php` extending `Module_Base` (auto-discovered — no registry edit needed).
2. Settings via `Settings_Hub` schema (`choices` for selects — never `options`).
3. User-facing strings through a per-module `Labels` class (locale-aware He/En defaults + per-string option overrides).
4. Public extension points documented in the module's `HOOKS.md`; hooks namespaced `shopos_core/<module>/…`; options prefixed `shopos_core_<module>_…`.
5. Tests under `tests/` mirroring the pattern; regenerate baselines after any hook/option/REST/CLI surface change.

---

*Reference source: market competitor "StoreOS by BroStudio" module rundown — `https://demo.brostudio.co.il/modules/` (fetched 2026-07-14). Used only as a target feature checklist; all ShopOS modules are original.*
