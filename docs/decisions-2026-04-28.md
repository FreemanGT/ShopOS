# ShopOS Plugin Suite — Strategic Decisions

**Date**: 2026-04-28
**Owner**: Yiftach
**Purpose**: Resolve the Open Questions from the Gap Audit (§4.1–§4.8) so the roadmap can be executed without per-item human approval cycles.

---

## §4.1 Product positioning — **Internal-only**

ShopOS Core is a private suite for ShopOS Digital's clients. Not sold standalone, not competing with commercial WC plugins.

**Implications**:
- Drop competitor-parity features (Wishlist A1, Quick-View A2) unless a specific client asks.
- Opinionated defaults are fine — no need to support every merchant configuration.
- White-labeling and telemetry are not needed (see §4.7, §4.8).

---

## §4.2 Locale strategy — **English defaults, Hebrew opt-in**

New installs ship English defaults. Hebrew is selectable via a settings toggle or via WP locale (`get_locale() === 'he_IL'` → suggest Hebrew but don't force).

**Implications**:
- Roadmap #2 builds `RestockNotify/locales/en_US.php` (default) and `he_IL.php` (opt-in).
- **Existing Hebrew installs**: do NOT overwrite their option values. Activation routine checks if values exist; if yes, leave alone.
- Email templates move from option-string defaults to template files (one file per locale).

---

## §4.3 Block editor commitment — **Elementor-only forever**

No Gutenberg block patterns, no block-based templates, no FSE.

**Implications**:
- Drop Roadmap #7 (theme.json → `--shopos-ui-*` unification, B7). The block editor's pickers don't matter.
- Drop D5 (theme.json filters). Modules don't need to contribute presets to Gutenberg.
- Design-token work (B1–B6, B8) stays — those serve Elementor controls and CSS, not Gutenberg.

---

## §4.4 REST vs admin-AJAX — **Keep AJAX. REST per-feature only if needed.**

No headless app, no SPA, no third-party integration pressure. Existing AJAX works.

**Implications**:
- Drop Roadmap #8 (broad REST API surface, D2, A23) from P1.
- New endpoints default to `wp_ajax_*` / `admin_post_*` like the existing surface.
- If a specific feature later needs REST (e.g., a React-based admin tool), add the namespace then.
- Wave 1.2 of the system prompt (REST scaffold) is **deferred** — do not build until a concrete need appears.

---

## §4.5 Legacy code sunset (VariationSwatches `legacy/`, `shopos_*` keys) — **No hard date. Migrate carefully.**

Zero-downtime migration is the priority over speed. Existing sites must keep working.

**Implications**:
- Roadmap #4 (VariationSwatches → Settings_Hub) proceeds, but:
  - New Settings_Hub options are **additive** alongside `shopos_vs_*` keys.
  - A read-shim reads new key first, falls back to legacy key.
  - A migration routine copies old → new on plugin upgrade, but never deletes old.
  - Legacy code stays in `legacy/` directory indefinitely.
  - Sunset of `shopos_*` keys is a separate future decision, requires explicit approval.

---

## §4.6 shopos-digital coupling — **Stay independent**

Two separate plugins. shopos-digital does not depend on shopos-core, and vice versa.

**Implications**:
- No cross-plugin function calls. Shared code (if any) gets duplicated, not extracted into a third package.
- Logger lives in shopos-core; shopos-digital uses its own logging.
- Hooks are namespaced per package (`shopos_core/...` vs `shopos_digital/...`).

---

## §4.7 White-labeling — **No**

Internal use only. No agency resale, no per-merchant branding.

**Implications**:
- Drop D9 (frontend asset URL filter for white-labeling).
- Drop the "admin-skin filter" idea for shopos-digital.
- D8 (Logger filters) **stays** — it's needed for observability/Wave 0, not for white-labeling.

---

## §4.8 Telemetry — **Skip**

Don't ship usage analytics. Internal client-base is small and reachable directly.

**Implications**:
- Drop A25 (telemetry/opt-in usage analytics) from the roadmap entirely.
- No new privacy policy obligations, no GDPR data-flow review, no anonymization layer.

---

## Revised Roadmap Summary

After applying these decisions, the original 15-item roadmap becomes ~9 items:

| # | Status | Reason |
|---|---|---|
| 1 (D1 hooks) | **Keep — P0** | Extensibility is internal-team value |
| 2 (RestockNotify locale) | **Keep — P0**, simplified per §4.2 |
| 3 (ProductFeed multi-channel) | **Keep — P0** if any client needs Facebook/Pinterest; otherwise P2 |
| 4 (VariationSwatches → Settings_Hub) | **Keep — P0**, gated by §4.5 migration plan |
| 5 (InfiniteScroll trigger modes) | **Keep — P1** |
| 6 (Sliders autoplay/dots/lazy) | **Keep — P1** |
| 7 (theme.json unification) | **DROP** per §4.3 |
| 8 (REST API) | **DROP / defer** per §4.4 |
| 9 (CheapestDefaultVariation strategies) | **Keep — P1** |
| 10 (RestockNotify CSV export + GDPR) | **Keep — P2** |
| 11 (Slider design tokens as Elementor controls) | **Keep — P2** |
| 12 (InfiniteScroll skeleton tokens) | **Keep — P2** |
| 13 (Wishlist) | **DROP** per §4.1 |
| 14 (Quick-View) | **DROP** per §4.1 |
| 15 (Critical CSS / WebP) | **Keep — P3**, only if a client asks |

**Net**: 9 active items, in priority order: 1, 2, 4, 3 (P0) → 5, 6, 9 (P1) → 10, 11, 12 (P2).

---

## Revisit triggers

Re-open these decisions if any of the following happen:
- A client requests a feature that was dropped (Wishlist, Quick-View, REST endpoint, etc.)
- The product direction shifts to commercial / agency / standalone sale
- A second engineer joins and needs the codebase to be more conventional
- Maintenance cost of keeping shopos-core and shopos-digital separate becomes painful

---

## §5 Shop Filters module (added 2026-05-20)

A net-new module replacing the third-party "Filter Everything Pro" plugin (faceted AJAX shop/category filters). Decisions taken at planning time so the Wave 6 phases execute without per-item re-litigation:

- **§5.1 Build vs §4.1 competitor-parity caution.** Shop Filters is built despite §4.1's "drop competitor-parity unless a client asks" because it serves a concrete internal need — the suite owner relies on shop filtering today and the current third-party tool renders poorly on this Elementor store. Internal-only, opinionated-by-default (auto-derives filters from product attributes; explicit config is optional).
- **§5.2 Dedicated index table — schema change APPROVED.** A new module-owned table `{prefix}shopos_shop_filter_index` (narrow term/category membership + per-variation in-stock flag) is approved (Hard Rule #6: dbDelta install + downgrade note). It deliberately stores only what WooCommerce's `wc_product_meta_lookup` can't (per-attribute-value in-stock truth + category membership); price/stock/rating are read from `wc_product_meta_lookup`, never duplicated. Dropped on uninstall; inert on a version downgrade.
- **§5.3 Indexing approach.** Event-driven dirty-queue (WC product/stock/term hooks, ~30s debounce) + a ~5–10 min reconciliation sweep, batched. Action Scheduler when available (WooCommerce bundles it) behind a `function_exists` guard, with wp-cron as the zero-config fallback — never a hard dependency. (Existing modules use wp-cron; AS is adopted only for this rolling-reindex workload.)
- **§5.4 Placement = shortcode.** `[shopos_shop_filters]` (drops into an Elementor HTML/shortcode element), per §4.3 Elementor-only. No Elementor widget in v1.
- **§5.5 Transport = admin-AJAX.** Per §4.4, the filter endpoint is `wp_ajax_*` / `wc_ajax`, not REST.
- **§5.6 Filtered-URL SEO.** Filtered URLs (query-string params only — no rewrite rules) get canonical → clean category/shop URL + `noindex,follow`, gated by a default-off flag so an SEO-plugin-governed site can opt out. Pretty/indexable filter URLs are an explicit non-goal.
- **§5.7 Cross-module decoupling.** ShopFilters does NOT call VariationSwatches' `ShopOS_VS_Plugin` (loaded only when that module is enabled); it duplicates the two pure helpers it needs and reads swatch term-meta directly — per §4.6's "duplicate, don't extract" leaning.

These are scoped to the new module and change nothing about existing modules or prior decisions.

### §5.8 Module graduated to always-on — all flags hard-removed (added 2026-06-07)

After the Wave 6 epic completed, the owner directed that Shop Filters ship **on by default with no feature flags**. All four `shop_filters` flags (`indexer`, `frontend`, `seo_policy`, `admin_config`) are **hard-removed**: the `is_enabled()` gates, the `Feature_Flags::registry()` entries, and the module's `indexer_enabled` settings toggle are deleted, so the module (background index, storefront panel, filtered-URL SEO policy, and the facet-configuration matrix) is unconditionally active whenever the module itself is enabled. The Index diagnostic surface (`Diagnostics`) was also removed from the admin page.

This is a **deliberate owner-approved override** of:
- **Hard Rule #1** (ship behind a flag) — the module graduates from flagged to default-on.
- **Hard Rule #2** (deprecate option keys with a 2-minor sunset) — the flag option keys (`shopos_core_shop_filters_*_enabled`) are no longer read; existing rows in `wp_options` become inert/orphaned (not deleted — no migration). Supersedes **§5.6**'s "default-off SEO flag so a site can opt out": the filtered-URL `noindex,follow` policy is now always-on; opt-out is only via the SEO-plugin layer or disabling the module.
- **Hard Rule #4** (one item per PR) and the **>12-file ceiling** — graduation + diagnostic removal shipped together in one oversized PR.

**Consequence — no option-based rollback.** There is no `wp option update … 0` kill-switch; rolling the module back means disabling it via the modules registry, or reverting the release. The module-registry enable/disable remains the only coarse switch.

---

## §6 Quick-View module — A2 re-opened (added 2026-06-11)

Roadmap #14 / audit item A2 (Quick View) was dropped per §4.1. The first revisit trigger has fired — the suite owner has requested the feature — so A2 is **re-opened** as Wave 7. Decisions taken at planning time:

- **§6.1 Two-stage scope.** Before the module ships, the ShopOS ProductSlider **grid mode** gets a parity audit against Elementor Pro's Products widget (markup / layout / behavior / function); approved gaps are fixed as 7.1x patch releases. The QuickView module lands after, as its trigger icon must behave identically on both grids.
- **§6.2 Trigger surface = WC loop hook.** The quick-view button is injected via `woocommerce_before_shop_loop_item_title`, which both the Elementor Pro archive grid and the ShopOS ProductSlider (slider + grid modes) render through `content-product.php` — one injection point covers every product card site-wide. No per-widget wiring.
- **§6.3 Transport = admin-AJAX.** Per §4.4: `wp_ajax_*` / `wp_ajax_nopriv_*` endpoint returning rendered drawer HTML. No REST.
- **§6.4 Drawer placement.** Slide-in panel anchored to the **inline-end** edge (`inset-inline-end`) — the left edge on this RTL store, the right edge on an LTR site. Focus-trapped, ESC/overlay close, `prefers-reduced-motion`, mirroring the ShopFilters mobile-drawer pattern.
- **§6.5 Flag.** `shopos_core_quick_view_frontend_enabled` (default `false`) gates button injection, asset enqueue, and the public AJAX endpoint (Hard Rule #1; ShopFilters 6.3a precedent). The module is additionally absent from `shopos_core_modules` on existing installs, so it is double-off until explicitly enabled.
- **§6.6 Strings.** Drawer labels are editable settings with blank-falls-back-to-English defaults (the ShopFilters `Labels` precedent, per §4.2) so the Hebrew storefront is configurable without code.
- **§6.7 VariationSwatches coupling.** The drawer renders the standard single-product summary so VariationSwatches' existing hooks light up unaided, and fires a `shopos_core_quick_view_loaded` DOM event after injecting content. The only cross-module edit is **additive**: the ShopOS event name joins the third-party quick-view event list (`woosq_loaded`, `yith_quick_view_loaded`, …) that `shopos-swatches.js` already listens to. No PHP cross-module calls (§4.6 / §5.7 stance unchanged).

Scoped to Wave 7; changes nothing about prior decisions. §4.1's "internal-only" stance stands — this ships because the owner asked, not for competitor parity.

## §7 Product Page module (added 2026-07-06)

Owner request: a new module that replaces the current WooCommerce/Elementor single-product page with a fully designed, responsive PDP and productizes two functions.php snippets (coupon-discount price notice, variation stock-scarcity badge) as configurable toggles. Decisions taken at planning time, all owner-confirmed:

- **§7.1 One wave / one PR.** The owner explicitly chose a single wave over the proposed 9.1/9.2/9.3 split, overriding the 12-file per-PR ceiling for this PR (precedent: the 1.21.39/1.21.40 owner-approved ceiling overrides). Sub-features remain independently flagged, so the blast radius stays per-feature despite the single PR.
- **§7.2 Delivery = full PHP template takeover (Option A).** `template_include` at priority 9999 wins over Elementor Pro's Theme Builder single-product location; the takeover template renders the standard WC hook stacks so existing modules light up unaided. Rejected Option B (restyling the Elementor widgets in place) as fragile against widget markup and capped by the existing template structure. Rollback is the layout flag — off restores the Elementor page byte-identically.
- **§7.3 Coupon notice is manual but validated.** The advertised code + percent are owner settings (no auto-derivation from the coupon object), but the notice renders only while a live WC coupon with that exact code exists and hasn't expired — owner chose "validate; if not, hide" over silent manual display.
- **§7.4 Strings via Labels.** All storefront wording is per-string settings with blank-falls-back-to-English defaults (§4.2 / ShopFilters-QuickView-Search precedent); the owner types the Hebrew. The urgency badge's hardcoded font-family is stripped (inherits the page font) per owner request.
- **§7.5 Legacy shortcode aliases.** `[discounted_price]` and `[stock_urgency]` (the snippet tags) are registered as aliases of the namespaced `[shopos_discounted_price]` / `[shopos_stock_urgency]` so existing Elementor placements keep working when the snippets are removed. These aliases are part of the public surface (Hard Rule #2 applies from 1.22.0 on).
- **§7.6 Design authority = DESIGN.md.** The PDP is styled to the documented "Quiet Boutique" system (ink-first, hairlines, tonal ramp, RTL-first, Hebrew flat tracking) through the `--shopos-ui-*` tokens with literal fallbacks. One deliberate deviation from the snippets: the urgency badge uses the warning-amber semantic (DESIGN.md assigns low-stock to warning), not the snippets' red.

Scoped to Wave 9; changes nothing about prior decisions.

## §8 Suite-wide flag graduation sweep (added 2026-07-06)

Owner request (verbatim scope: "remove Settings import flag; remove and on by default: Product Page, Quick View, Restock Notify, Infinite Scroll, Cheapest Variation, Sliders"). Nine flags hard-removed in 1.23.0, extending the §5.8 (Shop Filters), HoverSwap 1.16.1 and Search 1.21.0 graduation precedent to the rest of the suite:

- `tools/settings_import`, `sliders/advanced_controls`, `cheapest_variation/strategy`, `infinite_scroll/trigger_modes`, `restock_notify/csv_export`, `quick_view/frontend`, `product_page/coupon_notice`, `product_page/stock_urgency`, `product_page/layout`.
- **Hard Rules #1/#2 owner-approved override**, same shape as §5.8: the flag option keys are no longer read; existing `wp_options` rows become inert (not deleted, no migration). The registry keeps only the five VariationSwatches flags.
- **Kill-switch** is now per-module (the `shopos_core_modules` enable map) plus git revert. Module-enable defaults are unchanged — a disabled module (e.g. ProductPage on a fresh install) stays fully off, so graduation arms nothing by itself.
- **Behavior at defaults**: sliders / strategy / trigger-modes settings default to the legacy behavior; the visible deltas are admin-only surfaces appearing unconditionally (Tools import form, RN Export Subscribers submenu, slider advanced controls in the Elementor editor) and two DOM deltas — the CategorySlider root now always carries `data-cs-indicator`, and the IS wrapper div renders on archives (previously flag-gated).
- **Single oversized PR** (~25 files / 7 modules) owner-approved, mirroring the §5.8 and §7.1 ceiling overrides.
