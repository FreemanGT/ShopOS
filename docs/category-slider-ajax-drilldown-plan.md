# Phase 2 — No-reload category drill-down (reuse InfiniteScroll's fetch/swap)

**Status:** ON HOLD (2026-07-19). Reviewed via `/plan-eng-review` — scope reduced,
then held pending a real-store test of Phase 1 + the existing PageTransitions View
Transitions (see "Review outcome" below and the GSTACK REVIEW REPORT at the bottom).
Phase 1 (reload-based drill-down) is built — the `source: current_children` control
in `CategorySlider/Widget.php`.

## Review outcome (2026-07-19) — test before building

The eng review's outside voice verified that **the felt "no reload" experience may
already ship**: the PageTransitions module ([PageTransitions/Module.php](../shopos-core/src/Modules/PageTransitions/Module.php))
does cross-document View Transitions (`@view-transition { navigation: auto }`) plus
an overlay ShopFilters already calls, so a Phase 1 drill *navigates with a smooth
morph, no white flash*. Decision: **ship Phase 1, evaluate it on a real store with
PageTransitions on, and only build Phase 2 if the reload still feels insufficient.**
Do not start Phase 2 without that evidence.

### Verified defects to fix IF Phase 2 is resumed (found by the outside voice)
1. **Stale pagination (biggest).** The WooCommerce pagination `<nav>` is a *sibling*
   of `ul.products`, outside the grid container. Replacing only the grid's innerHTML
   leaves the OLD category's page-2 link; IS's `resync()` re-derives `nextUrl` over
   the whole scope ([infinite-scroll.js:620](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L620))
   and infinite-scrolls the WRONG category in. Fix: swap the whole main region
   (grid + nav + panel), which is close to a full-region replace.
2. **IS already handles popstate** — [infinite-scroll.js:881](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L881)
   registers `popstate → resync`. Risk #1 below is WRONG; T5 must reconcile with the
   existing handler, not add a second one (double-fire + race).
3. **Concurrency.** A drill click mid-IS-fetch: IS's stale-guard
   ([:645](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L645))
   and 200ms `resync` debounce mean an in-flight OLD-category response can
   `appendChild` page-2 onto the freshly-swapped grid. The drill must abort IS's
   in-flight fetch on click.
4. **T2 under-scoped.** shop-filters.js captures module-scope state and adds a
   document-level `keydown` in `openDrawer` removed only by `closeDrawer` — swapping
   the panel with the drawer open leaks that listener on the dead node. Idempotent
   `init(scope)` is a real refactor, not ~20min.
5. **T3 depends on T4** (calls `slider.refresh` before T4 builds it) — reorder.

## Goal

Clicking a Category Slider card drills the rail to that category's sub-categories
**and** re-queries the product grid to that category — with no page reload.

## Why "no reload" is the whole task

The ShopFilters stack is **reload-based**. A facet tick calls `location.assign()`
to a filtered URL ([shop-filters.js:96-103](../shopos-core/src/Modules/ShopFilters/assets/js/shop-filters.js#L96-L103));
the server applies the selection to the WooCommerce main query via `pre_get_posts` /
`post__in` / `posts_clauses` ([Query.php:63-77](../shopos-core/src/Modules/ShopFilters/Query.php#L63-L77)).
So "no reload" means swapping the grid (and the filter panel) in place instead of
navigating.

## What already exists (and is reused, not rebuilt)

The `/plan-eng-review` scope pass found InfiniteScroll already solves the two
hardest pieces the first draft of this plan wanted to build:

- **Fetch + extract, no custom endpoint.** IS `fetch()`es the plain filtered
  archive URL (current query params preserved) and pulls the grid out of the
  returned document with `DOMParser`
  ([infinite-scroll.js:625-672](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L625-L672)).
  That URL renders the filtered grid **server-side through the existing reload
  path**, so there is no markup-fidelity risk and little-to-no new PHP. The URL
  is exactly what `buildUrl()` in shop-filters.js already produces.
- **Grid cards need no re-init.** IS appends product `<li>`s with zero re-init and
  ships in production. Per-card modules are **document-delegated**: QuickView binds
  one listener at `document` ([quick-view.js:137](../shopos-core/src/Modules/QuickView/assets/js/quick-view.js#L137)),
  VariationSwatches uses the same `closest()` delegation. So they survive DOM
  injection automatically — no `shopos:grid-updated` contract needed.
- **IS self-resyncs on grid swap.** IS's `watchMain()` MutationObserver detects the
  grid container changing and re-seeds itself
  ([infinite-scroll.js:594-623](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L594-L623)) —
  including pagination reset. Replacing the grid just works with IS on; with IS
  off, a static swapped grid is fine too.
- **`Category_Tree::build()`** — pruned, count-rolled-up parent→child tree, reused
  for the slider's children after a drill.

## Architecture (reduced)

```
Click a category card (drill mode = ajax)
  │
  ├─ build filtered URL   (reuse shop-filters.js buildUrl, with product_cat set)
  ├─ fetch(url)           (reuse IS fetch + DOMParser + extract — shared util)
  │     └─ one server-rendered document, already filtered
  ├─ REPLACE grid container innerHTML         (IS appends; we replace)
  ├─ REPLACE [data-shopos-sf] panel contents  (keep counts/chips coherent)
  ├─ history.pushState(url)
  └─ slider.refresh(children of clicked term) (from the doc's category_tree / cards)

  IS watchMain() auto-resyncs the new grid. QuickView/swatches: delegated, just work.
```

### The re-init reality (accurate, not hand-waved)
- **Grid cards:** delegated → **no re-init**. This is the piece the first draft
  over-engineered.
- **Filter panel + slider:** they bind **element-scoped** listeners (the panel's
  `change`/chip handlers live on the panel node; the slider's drag/arrow handlers
  live on the track). Replacing those nodes drops their listeners, so both need a
  **re-init after swap**. This is a clean, single-module pattern — the same one
  `category-slider.js` already exposes via `window.ShopOSCategorySlider.init(scope)`.
  shop-filters.js currently has **no** re-init export (it's a bare IIFE binding on
  DOMContentLoaded) — Phase 2 must add one (idempotent, like the slider's).

### DRY: refactor-first (make the change easy, then make the easy change)
IS's `buildFetchUrl` / `normalizeUrl` / `firstMatchIn` / container-extract are
private inside its IIFE. To reuse them without copy-paste, extract a small shared
`grid-fetch` helper (fetch URL → filtered grid element) that **both** IS and the
new drill controller import. Do this refactor first, verify IS is unchanged, then
build the controller on top. Don't fork 30 lines.

## Real risks (what's left after the reduction)
1. **Back/forward (popstate).** ~~IS does not handle popstate.~~ **CORRECTION:** IS
   DOES handle popstate ([infinite-scroll.js:881](../shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js#L881)).
   A no-reload drill must *reconcile with* that existing handler (re-fetch + re-swap
   grid + panel + slider on the popped URL) — not register a second one. See "Verified
   defects" #2.
2. **Panel re-init.** Swapping `[data-shopos-sf]` kills its `change`/chip/show-more/
   collapse handlers until re-init. Requires exposing an idempotent init on
   shop-filters.js and calling it after swap. In scope (see task T2).
3. **IS-off stores.** The shared grid-fetch util must not depend on IS being booted
   (extract it as a standalone module, not a function hanging off IS state).
4. **Bandwidth.** Fetching the full archive URL ships header/footer/sidebar bytes
   per drill. IS already accepts this cost. A grid-only render (`?shopos_grid_only=1`)
   is a **future perf optimization**, explicitly NOT in scope (below).

## NOT in scope (considered, deferred)
- **Grid-only render endpoint / query param** — perf optimization to avoid shipping
  whole-page HTML per drill. Deferred; the full-page fetch is IS's proven baseline.
- **`shopos:grid-updated` cross-module re-init contract** — dropped. Grid cards are
  delegated; only the panel and slider need re-init, and they do it per-module.
- **Facet-panel show-more/collapse state preservation across a swap** — after a
  panel swap the refined-style toggles reset to default (collapsed/expanded per
  server render). Acceptable; revisit only if a store complains.

## Rollout
- Feature flag / module setting: **"AJAX filtering (beta)"**, default OFF. Off keeps
  today's reload behavior byte-identical. On enables the drill controller + panel/
  slider re-init exports.
- Ship order: (1) extract shared grid-fetch util, verify IS unchanged; (2) add
  idempotent init exports to shop-filters.js; (3) build the drill controller
  (fetch → replace grid + panel → pushState → slider.refresh); (4) popstate.

## Test plan
- **Unit:** the category→filtered-URL mapping (extend/params on `buildUrl`), and the
  shared grid-extract util's pure parts (given a document string → the grid element).
  Slider children resolution from `category_tree`.
- **Live QA (wp-env):** drill swaps grid + panel + slider no-reload; back/forward
  restores prior state; IS resyncs and pagination works after a drill; QuickView +
  swatches work on the swapped grid (delegation); flag-off falls back to reload;
  IS-disabled store still swaps a static grid.
- JS DOM behavior (swap/pushState/popstate) is integration/live-QA, matching how IS's
  own JS init carries no unit coverage.

## Implementation Tasks
Synthesized from the review. Checkbox as you ship.

- [ ] **T1 (P1, human: ~3h / CC: ~20min)** — InfiniteScroll — extract a standalone
  `grid-fetch` util (build URL, fetch, DOMParser, extract grid container) that IS and
  the drill controller both use.
  - Surfaced by: Architecture / DRY — IS's fetch+extract is private in its IIFE.
  - Files: `shopos-core/src/Modules/InfiniteScroll/assets/js/*`, new shared asset.
  - Verify: IS behavior unchanged on wp-env (infinite scroll still works); unit test the pure extract.
- [ ] **T2 (P1, human: ~3h / CC: ~20min)** — ShopFilters — expose an idempotent
  `init(scope)` on shop-filters.js so a swapped panel rebinds change/chip/show-more/collapse.
  - Surfaced by: Architecture — panel is element-scoped, dies on swap.
  - Files: `shopos-core/src/Modules/ShopFilters/assets/js/shop-filters.js`.
  - Verify: swap panel node in wp-env, filtering + toggles still work.
- [ ] **T3 (P1, human: ~1d / CC: ~half day)** — CategorySlider — drill controller:
  on ajax-mode card click, fetch filtered URL, replace grid + panel, pushState, refresh slider.
  - Surfaced by: core feature.
  - Files: `CategorySlider/assets/js/*`, `CategorySlider/Widget.php` (ajax drill toggle + data-cat-id).
  - Verify: live QA drill; flag-off reload fallback.
- [ ] **T4 (P1, human: ~3h / CC: ~20min)** — CategorySlider — `refresh(root)` on the
  slider runtime to recompute measurement/dots/progress after a track swap (init is currently one-shot).
  - Surfaced by: Architecture — slider is init-once.
  - Files: `CategorySlider/assets/js/category-slider.js`.
  - Verify: swap track contents, arrows/progress/dots still track.
- [ ] **T5 (P1, human: ~4h / CC: ~30min)** — CategorySlider — popstate handler that
  re-fetches and re-swaps grid + panel + slider on back/forward.
  - Surfaced by: Architecture risk #1.
  - Files: drill controller.
  - Verify: live QA back/forward restores state.
- [ ] **T6 (P2, human: ~2h / CC: ~15min)** — ShopFilters — "AJAX filtering (beta)"
  module setting gating the whole controller; off = reload path untouched.
  - Files: `ShopFilters/Module.php` settings + enqueue gate.
  - Verify: flag off → byte-identical reload behavior.

_All tasks are ON HOLD pending the Phase 1 + View Transitions evaluation (see Review
outcome). If resumed, fold in the 5 verified defects and reorder T3 after T4._

## GSTACK REVIEW REPORT

| Review | Trigger | Why | Runs | Status | Findings |
|--------|---------|-----|------|--------|----------|
| CEO Review | `/plan-ceo-review` | Scope & strategy | 0 | — | — |
| Codex Review | `/codex review` | Independent 2nd opinion | 0 | not installed | fell back to Claude subagent |
| Eng Review | `/plan-eng-review` | Architecture & tests (required) | 1 | issues_found | scope reduced (drop endpoint + re-init contract), then held; 5 verified defects logged |
| Design Review | `/plan-design-review` | UI/UX gaps | 0 | — | — |
| DX Review | `/plan-devex-review` | Developer experience gaps | 0 | — | — |

- **OUTSIDE VOICE (Claude subagent):** verified 3 claims against source — IS handles popstate (881), stale-pagination defect (nav is a sibling of the grid), and a pre-existing PageTransitions View-Transitions module that already smooths the reload. Found 5 concrete defects the review missed.
- **CROSS-MODEL:** Review staged a reduced Phase 2 as buildable; outside voice argued the smooth "no reload" feel already ships (Phase 1 + View Transitions) and Phase 2 is costlier than the reduced plan claimed. Resolved by the user: **test Phase 1 first, build Phase 2 only if insufficient.**
- **VERDICT:** ENG reviewed. Phase 2 ON HOLD by decision — not cleared to implement (by design). Phase 1 is the shippable path.

NO UNRESOLVED DECISIONS
