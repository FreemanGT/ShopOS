# Phase 2 — No-reload category drill-down (ShopFilters AJAX grid-swap transport)

**Status:** planned, not started. Phase 1 (reload-based drill-down) shipped — see
the `source: current_children` control in `CategorySlider/Widget.php`.

## Goal

Clicking a Category Slider card drills the rail to that category's sub-categories
**and** re-queries the product grid to that category — with no page reload.

## Why this is a transport project, not a slider feature

The whole ShopFilters stack is **reload-based today**. A facet tick calls
`location.assign()` to a filtered URL ([shop-filters.js:96-103](../shopos-core/src/Modules/ShopFilters/assets/js/shop-filters.js#L96-L103));
the server applies the selection to the WooCommerce main query via
`pre_get_posts` / `post__in` / `posts_clauses` ([Query.php:63-77](../shopos-core/src/Modules/ShopFilters/Query.php#L63-L77)).
The `shopos_core_shop_filters_query` AJAX endpoint returns **facet counts, not
rendered grid HTML** ([Query_Builder::query](../shopos-core/src/Modules/ShopFilters/Query_Builder.php#L501)),
and the storefront JS never calls it.

So "no reload" means building an **in-place grid-swap transport that does not
exist anywhere in the codebase**. It belongs in ShopFilters (the slider then
rides it for free). Building a one-off AJAX path just for the slider would create
two divergent filter transports — do not do that.

What already exists and gets reused:
- `Query_Builder::query()` — resolves filter state → filtered product id set,
  facet counts, category tree, count, pagination. Pure/tested core.
- `Category_Tree::build()` — pruned, count-rolled-up parent→child tree.
- `buildUrl()` in shop-filters.js — canonical filtered URL from a selection.
- ProductSlider grid mode — already renders a WC product loop in a non-archive
  context (`setup_postdata` + `wc_get_template_part('content','product')`) and
  builds archive pagination off a constrained id set. Copy this approach for the
  grid-render step.

## Architecture

### 1. Server — grid-render endpoint
Extend the ShopFilters Ajax handler (or a sibling action) to return, in addition
to today's facet payload:
- `grid_html` — the product-grid markup for `Query_Builder`'s resolved
  `grid_products` id set, rendered through the **same** WC loop the archive uses
  (reuse ProductSlider's `setup_postdata` + `wc_get_template_part` loop and
  `wc_setup_loop`, so themes/plugins/hooks style it identically).
- `pagination_html` — built off the constrained id set (mirror ProductSlider's
  `constrained_grid_pages` / `paginate_links` path).
- `category_tree`, `count`, `url` — already produced by `query()`.

Keep the existing nonce + per-IP rate-limit guard ([Ajax.php:42-52](../shopos-core/src/Modules/ShopFilters/Ajax.php#L42-L52)).

### 2. Client — shared swap transport (in ShopFilters JS)
A progressive-enhancement layer, gated by a feature flag (below). When on:
- Intercept facet changes / category clicks; POST the current selection; receive
  `grid_html`; swap the grid container; update chips + count; `history.pushState`
  the returned `url`.
- `popstate` handler re-fetches/restores on back/forward.
- Reset + re-arm **Infinite Scroll** to page 1 on every filter change.
- Dispatch a `shopos:grid-updated` DOM event after each swap so dependent
  per-card modules re-scan the new DOM: **InfiniteScroll, QuickView,
  VariationSwatches, hover-swap, ProductSlider**. Each subscribes to it. This
  cross-module re-init is the largest coupling in the project.
- Flag-off or no-JS → fall back to today's `location.assign()` reload untouched.

### 3. CategorySlider integration
- New behavior toggle on the widget: `drill mode = reload | ajax` (ajax only
  meaningful when the ShopFilters flag is on; otherwise the control degrades to
  reload).
- In ajax mode, cards emit `data-cat-id` and call a small public API,
  `window.ShopOSShopFilters.selectCategory(termId)`, which (a) sets
  `filter_product_cat` state, (b) runs the transport fetch, (c) uses the
  response's `category_tree` children to re-render the slider track in place.
- The slider runtime is currently **init-once** (`INIT_FLAG` on the root,
  `totalCards`/dots/progress computed once — [category-slider.js:421-471](../shopos-core/src/Modules/CategorySlider/assets/js/category-slider.js#L421-L471)).
  Add a `refresh(root)` that recomputes measurement state after an innerHTML
  swap of the track. Drag/arrow/progress listeners are bound to **stable**
  elements (track, root, `.cs-progress`) so they survive child swaps — only the
  measurement state and dots/foot references need rebuilding. Add a "Back"
  affordance to climb up a level.

## Risks / hard parts (ranked)
1. **Dependent-module re-init on swapped DOM** — the `shopos:grid-updated`
   contract must be adopted by every per-card module. Highest coupling, most
   regression surface.
2. **Grid markup fidelity** — the AJAX-rendered loop must set up WC's loop
   context exactly (globals, `wc_setup_loop`) or columns/hooks/pagination drift
   from the reload path. ProductSlider grid mode is the proven template.
3. **URL/SEO coherence** — pushState URLs must equal what the reload path
   produces; `Seo.php` derives canonical/rel from `Url_State`. Reuse `buildUrl`.
4. **Infinite Scroll ↔ filter reset** — a filter change must reset IS to page 1
   and re-arm against the new result set.

## Rollout
- Feature flag / module setting: **"AJAX filtering (beta)"**, default OFF. Off
  leaves today's reload behavior byte-identical.
- Ship the transport first (facets swap the grid no-reload), verify, then layer
  the slider triggers.
- Own branch + `/plan-eng-review` before implementation — this is multi-day and
  touches ShopFilters, InfiniteScroll, QuickView, VariationSwatches, hover-swap,
  ProductSlider, CategorySlider.

## Test plan
- Unit: the pure state-mapping helpers (category selection → filter state) and
  any new pure shaping; grid/pagination HTML is live-QA (echoes markup).
- Live QA (wp-env): filter tick swaps grid no-reload; back/forward restores;
  infinite scroll re-arms; slider drill swaps both rail and grid; Back climbs a
  level; flag-off and no-JS both fall back to reload.
