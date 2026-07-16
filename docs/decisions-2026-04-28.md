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

### §5.9 §5.4 reversed — Elementor widget added (added 2026-07-15)

**§5.4's "No Elementor widget in v1"** is **reversed** (owner-approved 2026-07-15), shipped as the Phase-2 widget fan-out's item 4 (shopos-core 1.33.0). Shop Filters gains a first-class Elementor widget (`ShopFilters\Widget`, `get_name()` frozen `shopos_shop_filters`) in the `shopos` panel category, mirroring the Search / ProductPage / RestockNotify widgets (items 1–3). §5.4 was scope-control during the Wave 6 build, not a permanent architectural line like §4.3 (Elementor-only) — the reversal is **narrow and additive**:

- The `[shopos_shop_filters]` shortcode is **unchanged** (Hard Rule #2); the widget is a *second* placement surface, not a replacement. §5.5 (AJAX transport), §5.6 (filtered-URL SEO) and §5.8 (always-on) are untouched.
- The widget is a thin shell delegating to the existing `Shortcode::render()` (a throwaway instance — that render registers no deferred hooks), so it inherits the shortcode's context resolution verbatim and adds no new query-context handling. It carries no per-instance controls (the panel is driven by page context + the module's global settings).
- Purely additive → **no feature flag** (Hard Rule #1 additive exception; the slider / items-1–3 widget precedent). Rollback is git-revert; the module-registry toggle is the coarse switch.

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

---

## §9 ShopOS Design panel (added 2026-07-15, shipped 1.35.0)

Expansion-roadmap Phase 3 (`settings` dimension). One of the three STOP-and-ask gated items; **owner-approved 2026-07-15** (decisions 1–5 below ruled by the owner). Reverses nothing; adds a new admin surface + a new front-end token-override lane, both behind a default-off flag. Superseded the throwaway proposal at `docs/proposals/design-panel.md`.

- **§9.1 What it is / is not.** A **ShopOS → Design** admin page: an accent-preset picker + a small **curated** allow-list of `--shopos-ui-*` overrides (accent/primary, text, text-soft, surface, surface-alt, borders — colours — plus one corner-radius). It is **not** a theme customizer, a free-form CSS box, a typography designer, or a block-editor push. Curated, not exhaustive — a "set the store accent + key surfaces" control.
- **§9.2 Why it fits.** It is the write-side counterpart to the Phase-1 `theme.json → --shopos-ui-*` bridge (shopos-theme 1.11.28) and reuses the Phase-1 field API (1.28.0). One override flows suite-wide through the token layer with no per-module edits. It is the natural predecessor to the (still-gated) Store Blueprint, which would snapshot exactly these options.
- **§9.3 Placement & storage.** New **Design** submenu under the single ShopOS menu, cap `manage_woocommerce`. Options under the `shopos_core_design_*` prefix (`_accent` + six `_col_*` + `_radius`) in a `shopos_core_design_group` settings group. Flag `shopos_core_design_panel_enabled`, **default false**.
- **§9.4 Render lane.** On `wp_enqueue_scripts` prio 30 (after the theme bridge's 21), emit a `:root{}` block of only the tokens the owner set, via an inline-only `wp_add_inline_style` depending on the `shopos-tokens` handle when present. The `--e-global-*` Style-Kit values stay the **fallback** in the `var()` chains, never the target. Kill-switch: the `shopos_core/design/tokens_css_enabled` filter (default true) + the flag.
- **§9.5 Presets.** Five code-defined accent presets (`default`/terracotta/forest/indigo/plum), `default` = the theme's gold and contributes nothing. Individual colour overrides win over the preset.
- **§9.6 Relationship to §4.3.** No conflict. §4.3 keeps Elementor as the page-design tool; this panel sets **semantic design tokens** the modules already consume via CSS variables — the same theme.json→CSS lane §4.3's reconciliation kept, extended with an admin-authored override source. No FSE/block-template commitment, no block-editor picker push.
- **Owner's decisions (2026-07-15):** (1) approved in principle; (2) curated list "a little wider" → 8 controls (accent + 6 colours + radius); (3) 3–5 presets, gold default → 5, `default`=gold; (4) one PR; (5) keep independent of Store Blueprint for now.
- **Backward compat.** Additive-only when off (no menu, no CSS → byte-identical). No hook/option/shortcode/route removed; the theme token bridge is untouched. One new flag key, one `shopos_core_design_*` option group, one additive emit filter.

---

## §10 Store Blueprint — settings-as-code (added 2026-07-16, shipped 1.39.0)

Expansion-roadmap Phase 3 (`mechanisms` dimension), the last Phase-3 item. The last of the three STOP-and-ask gated items; **owner-approved 2026-07-16** (decisions 1–5 below ruled by the owner, all as recommended). Superseded the throwaway proposal at `docs/proposals/store-blueprint.md`.

- **§10.1 What it is / is not.** A **Blueprint** is a named, versioned JSON file capturing the suite's five behavioural option surfaces — the modules map, every `Feature_Flags::registry()` flag, the four modules' label overrides (QuickView/ShopFilters/Search/ProductPage), the ShopFilters facet config, and the §9 Design tokens — so store #2 starts configured instead of re-clicked. It is **not** a full-site migration (the Wave 0.3 Tools export covers every `shopos_core_*`/`shopos_digital_*` key), not content/products, not a stored-preset library in wp_options, and not a web/REST surface (§4.4 untouched — the only transport is `wp shopos`, the reserved 1.36.0 slot).
- **§10.2 File format.** A valid Wave 0.3 envelope (`version: 1` + the same required fields — `Settings_Tools::validate_envelope()` tolerates the extra key) plus one `blueprint` block: `{format: 1, name, generator}`. Every Blueprint file is therefore also importable through ShopOS → Tools (raw semantics), and Blueprint applies share the same rolling-5 auto-backup + Restore machinery. `wp shopos blueprint import` **requires** the block, so a full-store envelope never accidentally gets Blueprint semantics; `format` is Blueprint's own version knob; a newer `generator` warns.
- **§10.3 Curated key set.** 52 keys today, enumerated **from code registries at export time** (never a DB scan): `shopos_core_modules` (1) + flag options (7, auto-tracking the registry) + `shopos_core_<module>_label_<key>` (35) + `shopos_core_shop_filters_facet_config` (1) + `shopos_core_design_*` (8). Runtime state (log, boot failures, backups, onboarding, index rows) can never leak in. Out of scope: `shopos_digital_*` (§4.6 independence) and VariationSwatches labels (locale-switched in code, not option-backed).
- **§10.4 Apply semantics.** `Core\Blueprint` owns its write loop rather than reusing `Settings_Tools::import()`, for three code-level reasons: import() halts on `update_option() === false` which also fires on *unchanged* values (fatal for idempotent re-apply), it writes raw values with no per-key sanitisation (and on CLI no `register_setting` sanitisers are hooked), and surface-aware semantics don't exist in the generic loop. Validation is strict — settings-as-code means a typo (`unexpected_key`, `invalid_value`) rejects the whole file with **zero writes**. Cross-store data drift warns instead: unknown module ids drop with a warning; facet rows for taxonomies missing from the target's filter index are **kept with a warning** (implementation note on ruling 5: the index is empty on exactly the fresh store #2 this exists for — the rows are inert until `reindex shop-filters` and activate after; dropping would lose config). Modules **merge by id** (blueprint ids win, the store's other ids keep state — a stale preset can never disable modules shipped after the snapshot). Every write is unchanged-skipped (`written`/`skipped` counts). Reused from Wave 0.3: `backup_current('blueprint')` before the first write → same rolling-5 store + Tools Restore as the rollback path. New actions `shopos_core/blueprint/before_apply` + `after_apply` mirror the Tools import pair.
- **§10.5 CLI surface.** One new `blueprint` method on the existing `Core\CLI` class (zero new `add_command` sites): `wp shopos blueprint export <file> [<name>]` (name defaults to the file stem), `diff <file>` (dry-run table: option / action / current / blueprint — the reviewability story), `import <file>`. Positional args, strict vocabulary, untranslated strings — all per the 1.36.0 conventions. Widens the CLI's "deliberately narrow" header by one operator-invoked subcommand; still no web surface, still inside the §4.4 line.
- **Owner's decisions (2026-07-16):** (1) scope = the five surfaces / 52 keys, shopos-digital + VariationSwatches labels out; (2) CLI-only transport in v1 — settings-as-code means files in a repo, no admin UI, no stored-preset option; (3) `blueprint diff` included in v1; (4) flagless-additive per the 1.36.0 CLI precedent (WP_CLI-guarded, writes only on explicit operator invocation, like the already-flagless `flags set`); (5) modules merge-by-id.
- **Backward compat.** Purely additive: no new options, no new flag, no web surface; two new actions; existing Tools export/import/restore byte-identical. Rollback of any apply = the auto-backup it just took (Tools page Restore, or `wp eval '( new \ShopOS\Core\Core\Settings_Tools() )->restore( 0 );'`).
