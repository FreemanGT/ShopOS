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

---

## §11 The ShopOS Line — theme owns the buy path (added 2026-07-16, owner-approved)

Expansion-roadmap Phase 4, row 1 — the last STOP-and-ask gate (§5.9, §9, §10 previously cleared). **Owner-approved 2026-07-16** (rulings 1-10 below, all as recommended). Superseded the throwaway proposal at `docs/proposals/shopos-line.md` (deleted, per the §9/§10 precedent). The proposal was pressure-tested before approval by an 8-agent workflow (3 repo reality-mappers → draft → adversarial compat/scope/process reviews → synthesis; 28 accepted objections folded in); load-bearing code claims were re-verified against the tree (`Template_Loader.php:256-263`, `tests/FeatureFlagsAdminTest.php:31/74`, `tools/perf-budgets.json`).

## §11.1 Thesis — what it is / is not

**The Line:** every surface between "shopper expresses intent" and "order confirmation email" — PLP, PDP, cart, checkout, account, search results, transactional emails — is eventually rendered by ShopOS as **classic PHP templates**. Elementor remains for storytelling pages only. Templates are resolved **only by a flag-gated loader from `shopos-theme/templates/woo/`** — never from the auto-scanned `{theme}/woocommerce/` directory and never from a path any code honors today, so shipping a theme zip can never change a flag-off render by file presence alone.

This **reverses "theme = skin"** (style.css:4 "the theme handles presentation only"; optional-future-features.md:47 "The theme is not a theme — it's a skin"). The theme today ships zero PHP templates, no `woocommerce/` dir, no header/footer — everything renders through the Hello Elementor parent + Elementor Pro Theme Builder. The only PHP template in the suite is plugin-side (ProductPage/Template_Loader.php, the §7.2 takeover).

**It is not:** FSE or block templates (§4.3 is permanent — never `templates/*.html`), a REST expansion (§4.4 stands), a rewrite of Core modules, or a removal of any Elementor surface (Hard Rule #2 — every widget, shortcode, and the Elementor render itself stay intact as the rollback path).

**Safety model:** strictly flag-gated per template; flag off = the current render, byte-identical — including the day the theme zip installs. Ships because the owner asked (§4.1 framing, per §6 precedent).

## §11.2 Owner rulings

### Ruling 1 — Scope of this addendum: Line only

optional-future-features.md:103 bundles three decisions: the Line, a partial §4.8 reversal (first-party measurement), and re-opening Wishlist. **Recommendation: §11 = the Line only.** Measurement and Wishlist get their own numbered decisions when their waves arrive. A mega-§11 would blur what "owner approved §11" authorizes.

### Ruling 2 — Surfaces in v1, ordering, and the §11-B gate

- **Option A:** all seven surfaces in one program.
- **Option B (original draft):** v1 = typography + PLP + header/footer chrome + PDP.
- **Option C (recommended):** v1 = **typography prerequisite → PDP → PLP. Chrome is cut from v1** and joins cart, checkout, account, search-results, and emails behind the **§11-B checkpoint**.

Reasoning for the reorder (scope review, accepted): PDP is already a PHP render — Core's §7.2 takeover — so the theme PDP is the smallest, lowest-risk first template: an M-sized generalization needing **no new theme loader**, only a one-line flag-gated resolution in Core (Ruling 5). The shared theme loader is built when the **second** template (PLP) actually demands it — consumer before platform. Reasoning for the chrome cut: chrome is not a funnel surface (no scorecard row; "if it's not on the funnel scorecard, it waits"), its only real consumer is the Elementor-retirement end-state that Ruling 10 explicitly declares a non-commitment, and flag-on delivers a re-implementation of a header that already works — XL effort, zero shopper-visible delta, no store #2 to make repeatability real. Only the CSS-chain robustness fix survives into v1 (Ruling 6). Chrome flag names are **not minted now** — frozen identifiers for a cut surface are pure liability.

**Search carve-out (binding):** the live search-results page is a product-archive main query (Results_Query.php:78-85). The PLP template claims `is_shop()` + product-taxonomy archives **only, and explicitly never `is_search()`**, until §11-B — the loader's claim condition mirrors `Results_Query::should_handle` ("product main query + request search term ⇒ not mine"), and the exclusion is on the Ruling 7.2 sibling checklist.

**§11-B gate override (owner, 2026-07-20):** the owner elected to **start the §11-B surfaces now**, before the measurable unlock below is met (PDP/PLP have not been flag-on a single day; store #2 is not stood up), and also over the 2026-07-19 design-control pivot's push away from theme-owned templates. The theme-CI-lane precondition (b) **is** genuinely satisfied (Ruling 7.8, `@group theme`). **Surface 1 — header/footer chrome — SHIPPED 2026-07-20** (core 1.47.0 + theme 1.16.0, flag `theme.template_chrome`, default OFF; require-parent passthrough byte-identical + ShopOS chrome verified in wp-env; QA doc `tools/qa/chrome-template.md`). **Surface 2 — cart page — SHIPPED 2026-07-20** (core 1.48.0 + theme 1.17.0, flag `theme.template_cart`, default OFF; the whole cart page theme-owned via a flag-gated `woocommerce_locate_template` redirect — a THIRD mechanism alongside the PDP/PLP `template_include` loader and the chrome child-hierarchy — to `templates/woo/cart/` never `{theme}/woocommerce/`; 7 forked `cart/*` templates with WC hooks/nonces verbatim; flag-off = WC default byte-identical, Ruling 6; only the `[woocommerce_cart]` shortcode cart, so **block-cart stores need a per-store block→shortcode content-migration under Hard Rule #3 — the Ruling 9 note made concrete**; `CartTemplateTest` `@group theme`; QA doc `tools/qa/cart-template.md`). **Surface 3 — My Account — SHIPPED 2026-07-20** (core 1.50.0 + theme 1.18.0, flag `theme.template_account`, default OFF; reuses the cart's locate filter, **generalized** to the shared `locate_woo_template` — one callback, a claim arm per surface, new surfaces add an arm not a registration; two STRUCTURAL templates forked — `myaccount/my-account.php` shell + `navigation.php` rail — while the account content AND the auth/payment forms are **CSS-skinned not forked** so WooCommerce keeps ownership of every nonce + gateway field; flag-off = WC default byte-identical, Ruling 6; the `[woocommerce_my_account]` shortcode account, no WC My-Account block ⇒ Ruling 9 low-risk; `AccountTemplateTest` `@group theme`; QA doc `tools/qa/account-template.md`). Remaining surfaces (checkout · search-results · emails) proceed one plan-first PR each; **checkout still requires the Ruling 9 technology pin resolved first**, and **emails stay Core-side** (ownership map). The measurable unlock below remains the record for the *flip-on-a-live-store* decision even though the *build* gate was waived.

**§11-B unlock (measurable, recorded):** (a) PDP + PLP flag-on on a live store for **≥30 days spanning at least one subsequent theme release, with zero rollbacks** — flip-on dates and any rollback recorded in the decisions doc state line at flip time; (b) a theme PHPUnit/CI lane exists (Ruling 7.8 — CI today runs only `php -l` on the theme); (c) **store #2 is committed, or the owner re-affirms in writing that continuing is architectural-value-only** (Ruling 2's honest ledger: v1 flag-on success is parity, and parity's value is repeatability).

### Ruling 3 — §4.3 reconciliation (explicit re-scoping, not silent narrowing)

§4.3 said "Elementor-only forever. No Gutenberg… no FSE." The Line narrows it to: **no-FSE/no-block-templates remains permanent and unqualified; "Elementor-only" is re-scoped to "Elementor owns storytelling pages; ShopOS classic PHP owns the buy path."** Precedent: the §9.6 reconciliation and the shopos-theme 1.11.28 token-bridge reconciliation both re-scoped explicitly rather than silently. The reaffirmation cites the 1.11.28 theme.json reconciliation to avoid re-litigating the token bridge. **Recommendation: approve the re-scoping as worded.**

### Ruling 4 — Opt-in mechanism: plugin-side Feature_Flags; flags are permanent kill-switches

The roadmap sketched "theme-mod opt-in." But Feature_Flags, `wp shopos flags`, the admin page, and Blueprint's snapshot key-set are all plugin-side; theme_mods are invisible to all of them.

- **Option A:** theme_mods + Customizer, plus a Blueprint format extension.
- **Option B (recommended):** per-template flags in `Feature_Flags::registry()` under a virtual module id `theme`. **Three flags are minted and frozen now** (chrome flags are not — see Ruling 2):
  - `shopos_core_theme_fonts_selfhost_enabled`
  - `shopos_core_theme_template_pdp_enabled`
  - `shopos_core_theme_template_plp_enabled`

  All default false. Filter overrides work as today (`shopos_core/feature_flag/theme/{feature}`).

**Pinned read path (binding):** the theme reads flags **only** as `class_exists( Feature_Flags::class ) ? Feature_Flags::is_enabled( 'theme', $feature ) : false`. Core absent ⇒ hard false ⇒ Elementor renders (the soft Core dependency stands; plugin-dependencies.php stays advisory). **Never a raw `get_option()`** — `Feature_Flags::is_enabled` parses via `FILTER_VALIDATE_BOOLEAN` and applies the override filter, so a naive `(bool) get_option()` disagrees on the strings `'off'`/`'no'`/`'false'` and misses filter overrides: the same option value would render Elementor via Core's check and the PHP template via the theme's.

**Test reality (corrected from the draft):** FeatureFlagsAdminTest is **bidirectional** — it fails any registry entry with no `is_enabled('module','feature')` call site, and its scan root is hardcoded to `shopos-core/src` (tests/FeatureFlagsAdminTest.php:31, :74). Theme flags have no core call sites, so the draft's claim that the existing test "enforces coverage" was backwards: the first flag PR would fail core CI. **The first flag PR extends the test's scan roots to `shopos-theme/` (keeping the bidirectional assertion intact) and moves Blueprint's key-count assertions in the same PR.** These core-test touches by nominally-theme PRs are pre-authorized here.

**Sub-ruling 4a (graduation exemption, and its consequence):** §7.2 promised flag-based byte-identical rollback and §8 removed that flag two weeks later; the 1.23.0 sweep also caused the InfiniteScroll pushState regression (fixed 1.24.8). **Recommendation: buy-path template flags are PERMANENT kill-switches, exempt from graduation sweeps, recorded here as a standing exception to the flag lifecycle.** Rollback for every template is forever `wp option update shopos_core_theme_template_<surface>_enabled 0` (or `wp shopos flags set`). **Consequence, accepted explicitly:** whatever a flag falls back to is equally permanent — the draft's 2-minor deprecation sunset for Core's ProductPage Template_Loader is **withdrawn** (Ruling 5); a sunset would delete the permanent kill-switch's fallback renderer out from under it and make §11.5's flag-off promise false.

### Ruling 5 — Ownership boundary: what the survive-theme-switch promise now means

shopos-core.php:5 promises "Owns all data and business logic so features survive a theme switch." **Recommendation — the promise is redefined, not broken:** *data, options, and module behavior survive a theme switch; the designed buy-path rendering deliberately does not* (a theme switch falls back to Woo-default rendering with all Core hooks still firing — `Template_Loader::$is_takeover` has no consumers outside the loader, so no module logic keys off the takeover). Per-surface ownership map:

| Surface | Owner |
|---|---|
| Templates (PDP/PLP; later cart/account/chrome) | **shopos-theme** — templates live at `shopos-theme/templates/woo/` (non-auto-located), resolved only when the surface flag is on |
| Data, query logic, widgets, AJAX (Search Results_Query, InfiniteScroll, VariationSwatches, QuickView…) | **shopos-core** — unchanged |
| PDP migration | theme ships `templates/woo/single-product.php`; **one small Core change ships in the same PR**: `Template_Loader::template_file()` resolves the theme copy only when `shopos_core_theme_template_pdp_enabled` is on. The public override path `shopos/product_page/single-product.php` remains honored as a public contract (Hard Rule #2), but **the theme never ships a file at it** — Template_Loader.php:256-263 resolves that path unconditionally, so a theme file there would go live flag-OFF the day the zip installs and the flag rollback would be a no-op. The draft's "no Core change needed on day one" claim is deleted: that mechanism was the defect. **Core's Template_Loader and its module template copy are permanent** — they are the flag-off renderer (no sunset; per Ruling 4a) |
| Transactional emails | **shopos-core, never the theme.** Emails send from cron/webhook contexts and theme-side `woocommerce/emails/` overrides die on theme switch — the one surface where theme ownership contradicts the promise outright. Deferred with §11-B; how Core implements it is ruled at §11-B, not pre-designed here |

Header/doc rewrites (shopos-core.php:5 including the stale "eight modules" claim; style.css:4; README scoping line; stale Template_Loader.php:28 docblock; stale Guardrails release.sh note) land as a **dedicated docs-only PR** (sequencing row 1), not bundled into the first code PR.

### Ruling 6 — Theme strategy: stay a child theme; chrome deferred, mechanism pre-ruled

- **Option A:** go standalone theme now.
- **Option B (recommended):** stay a Hello Elementor child through Phase 4. **Standalone-theme is a separate future gate.**

Chrome itself is deferred to §11-B (Ruling 2), but two things are ruled now so §11-B doesn't re-derive them:

1. **The passthrough mechanism must be true delegation, never a copied snapshot.** Hello Elementor is not vendored or version-pinned; a child `header.php` carrying "the parent's exact fallback markup" is byte-identical only on copy day and silently decays with every parent update (a renamed parent helper fatals the storefront). When chrome is built: flag off ⇒ the child `header.php`/`footer.php` `require`s the parent's own file verbatim (`get_template_directory() . '/header.php'`) — byte-identical by construction, survives parent updates. Any vendored copy requires pinning the parent version plus a drift check.
2. **The CSS-chain fragility fix ships in v1 as its own small PR** — class-shopos-theme.php:76 hard-depends on the parent's `hello-elementor-theme-style` handle and silently drops all ShopOS CSS if it's absent. That is a real robustness bug independent of chrome ownership.

### Ruling 7 — Verification bar (theme has no PHPUnit)

**Recommendation — per-template acceptance criteria, mandatory in each PR:**

1. **Hook stacks:** renders the standard `woocommerce_*` hook stacks so QuickView, RestockNotify, VariationSwatches, and structured data light up unaided (the §7.2/§6.2 precedent). Enforced by a **committed per-template hook-firing checklist** verified in wp-env via a listener mu-plugin as part of the QA script. Core's `capture-baselines.sh` does **not** extend to theme templates (that would couple every theme line-shift to core's BaselinesIntegrityTest); the draft's "where feasible" clause is deleted.
2. **Sibling detectors:** classic-markup selectors in shared JS (InfiniteScroll's `.elementor-*` targets, ProductSlider Widget.php:673) are **appended only — never reordered or replaced** (querySelector list order decides the winner when both renders exist), because detector JS ships to all stores including flag-off Elementor ones — the exact 1.23.0→1.24.8 failure shape. Every detector PR includes a **flag-OFF wp-env pass against the Elementor render**, not only the new template. Assert (don't assume) that woocommerce.php:52's mobile-cols CSS survives; the Results_Query search exclusion (Ruling 2) is on this checklist.
3. **Byte-identity, honestly scoped:** a committed **`tools/render-diff.sh`** (curl, normalize nonces/timestamps, diff) is the named mechanism. wp-env runs Elementor **free** — Theme Builder locations don't exist there — so flag-off byte-identity against the real Pro render is verified **on staging-with-Pro**; wp-env covers only the parent-fallback path. A recorded wp-env QA script (checklist + WP-CLI steps) is committed per template, plus live-store QA before flag-on.
4. **Perf, non-self-referential:** DONE = the flag-on render **passes the existing committed flag-off budget for that path** (perf-budgets.json already holds the Elementor-render numbers — shop: 123 queries/56ms; product: 232/113ms), or the owner explicitly approves a stated delta. New **flag-state keys** (e.g. `shop_plp_on`) are added rather than reseeding a laxer baseline (`--seed` writes measured×1.25 of the new template — a template 3× slower would pass its own fresh baseline). perf-budget re-runs per template PR and before each flag-on.
5. **Flag-ON acceptance:** owner screenshot review on named viewports (mobile + desktop) is the acceptance gate, and an explicit **RTL/Hebrew pass** is mandatory — the theme is RTL-first (shopos-rtl.css, he_IL) and pr-template.md:71 already mandates it; a new PHP template is the largest RTL surface the suite has shipped.
6. `.pot` regen; version bumps only via `tools/release.sh` (style.css + SHOPOS_THEME_VERSION lockstep); template PHP isn't cache-busted, so template + CSS ship atomically in the zip.
7. **pr-template reconciliation:** theme PRs fill the Tests section as `Tests: N/A — theme has no PHPUnit; QA script per §11/R7.3 is the substitute`. This fill is sanctioned here so the per-PR contract stays enforceable rather than silently ignored.
8. **Hard precondition for §11-B (cart/checkout):** a theme PHPUnit/CI lane exists first (today CI runs only `php -l` on the theme). Live-QA-only is accepted for PDP/PLP, not for checkout.
   - **SATISFIED 2026-07-20 (owner-approved Option A).** The theme is unnamespaced classic PHP with no standalone suite; rather than duplicate core's WP-stub harness, its template-routing logic is CI-gated **inside core's PHPUnit lane** via the `@group theme` group — the established `ThemeTemplateLoaderTest` / `ProductPageTemplateTest` fixture pattern that loads the real theme files. CI (`ci.yml`) now runs a dedicated named **"ShopOS Theme lane (lint + template tests)"** step: `php -l` over `shopos-theme/` + `phpunit --group theme` (34 tests / 88 assertions green on PHP 8.0–8.3). **Contract for §11-B surfaces:** every deferred theme-template surface (header/cart/checkout/account/search-results/emails) adds its own `@group theme` `*TemplateTest` to `tests/`. This is the CI substitute; live-QA per R7.3 still applies, and checkout keeps its stricter (not live-QA-only) bar.

### Ruling 8 — Blueprint / Design-panel interplay

**Recommendation:** registering a flag in `Feature_Flags::registry()` **is** its Blueprint coverage — `Blueprint::key_set()` derives every flag key from the registry (Blueprint.php:76-78); there is no second key-set edit (the draft implied two acts; it is one). **New buy-path settings default to Core options, not theme_mods** (one config system; §10 store-#2 repeatability); Customizer keeps only live-preview visual controls. Importing a Blueprint with `theme_*` flags = 1 onto a store whose theme predates the template falls to the loader's debug-log fallback (§11.3) — recorded here as intended behavior.

**Cut from the draft (scope review, accepted):** the theme-mod Blueprint format extension — a format-versioned change existing to snapshot `shopos_shop_cols_mobile` (un-snapshotted forever, zero reported pain) plus a kit-slot control that no longer exists — is **deferred entirely**; revisit only when a real Blueprint apply on a real store #2 loses a setting someone needed. The **kit-slot Customizer control is cut**: the sk_type slot mapping is de-hardcoded behind a filterable Core-option default (consistent with this ruling's own settings rule), with **no UI** until a second store actually needs different slots.

### Ruling 9 — Checkout technology pin: rule now or at §11-B? (genuine split between reviews)

- **Option A (process view):** keep the standing pin — future checkout targets the classic `[woocommerce_checkout]` shortcode; `cart_checkout_blocks` stays declared-but-unused; revisit trigger: WooCommerce deprecating the shortcode checkout. Argument: a deferred-surface pin with an observable revisit trigger is exactly the right shape.
- **Option B (scope view, recommended):** **defer the pin to §11-B.** No code consumes it before then, WooCommerce's checkout strategy is actively moving (the pin's own revisit trigger admits the risk), so it buys nothing now and likely arrives stale and re-litigated. The load-bearing residue is carried as a one-line note in the §11-B gate: *block cart/checkout are page content, not templates — stores on block checkout need an explicit per-store content-migration plan under Hard Rule #3; watch Woo's shortcode-checkout deprecation status.*

### Ruling 10 — Elementor end-state, rollback fine print, and ordering enforcement

**Recommendation:** retiring the per-store Elementor Pro + Style Kits dependency is a **stated long-term direction, not a v1 commitment**. The rollback promise is recorded with its full conditions: "byte-identical Elementor rollback" holds only while **(a)** the store's Elementor buy-path templates still exist **and (b)** Elementor Pro + Style Kits remain **active and licensed** on that store — a lapsed license or deactivated plugin degrades the rollback path independently of any ShopOS action, and since rollback is §11's entire safety story, the dependency-liveness condition is part of the promise. Deleting a store's Elementor buy-path templates converts rollback to Woo-default rendering — an irreversible per-store step requiring owner sign-off per store.

The typography de-hardcode must ship before any template flips on, or fonts visibly flip between Elementor and PHP-template pages (sk_type_12/sk_type_2 vars aren't printed where the kit doesn't load — shopos-tokens.css:63-64). **Enforced, not just stated:** flags are per-store option flips, so the theme loader logs a warning whenever any template flag is on while `fonts_selfhost` is off, and every template's rollout checklist lists fonts-flag-on as a precondition.

## §11.3 Guardrails / non-goals

- **No FSE, no block templates, no `templates/*.html` — ever** (§4.3, reaffirmed).
- **No broad REST** (§4.4); templates use the existing AJAX endpoints.
- **Hard Rule #2 holds everywhere:** no Elementor widget, shortcode, hook, or option is removed; `shopos/product_page/single-product.php` remains a public override path (which is exactly why the theme never ships a file there); **Core's ProductPage Template_Loader is permanent** — it is the PDP kill-switch fallback, not a deprecation candidate.
- **Templates are never discoverable by file presence.** Nothing ships under `{theme}/woocommerce/` (WooCommerce loads any `archive-product.php` there at priority 10, flag-off included, and @version-scans it for "outdated template" warnings on every Woo release). All templates live at `shopos-theme/templates/woo/` and resolve only through the flag-gated loader.
- **Woo template drift:** each shipped template records the Woo template @version it was derived from, and the release checklist diffs shipped templates against each new WooCommerce release.
- **One template per PR; ≤12 files.** Theme-only PRs don't consume the ≤3-module budget, but a PR touching theme + >2 Core modules still STOPs and asks. Pre-authorized ceiling exceptions, recorded here per the §7.1 precedent: the font-assets PR (row 2a — the woff2 binaries alone exceed the ceiling; expected count recorded in that PR's plan) and flag PRs touching core test files (FeatureFlagsAdminTest scan roots, Blueprint key-count assertions — Ruling 4). No other ad-hoc overrides.
- **Frozen identifiers:** the three flag names in Ruling 4 lock at birth. Chrome/cart/checkout/account/email flag names are **not minted** until their surfaces are ruled at §11-B. Settings refactors assert byte-identical option output.
- **Loader spec:** priority-9999 `template_include` stays a single shared loader with one filter per request; logs debug-level when a template file fails to resolve (falling back to the current render) and warning-level when a template flag is on with `fonts_selfhost` off; per-surface context value replaces the `$is_takeover` static pattern; no per-surface priority arms race, no site-wide theme-support mutations. The PLP claim condition excludes `is_search()` (Ruling 2).
- **Shared-JS detector changes are append-only** with a mandatory flag-OFF pass (Ruling 7.2).
- Out of scope for §11: first-party measurement (§4.8), Wishlist, standalone-theme conversion, the theme-mod Blueprint extension, kit-slot UI, dynamic tags / Theme-Builder loop-grid (**DEFER**, per roadmap).

## §11.4 Sequencing

| # | Item | Effort | Flag (default off) | Flag-on value delta | Rollback | DONE-bar |
|---|---|---|---|---|---|---|
| 0 | This addendum — owner ruling on 1–10 | — | — | — | — (no code) | rulings recorded verbatim; proposal deleted |
| 1 | Docs-only PR: header/doc rewrites (shopos-core.php:5, style.css:4, README, Template_Loader.php:28, Guardrails note) | S | — | — (bookkeeping) | revert commit | docs match the recorded rulings; no code |
| 2 | CSS-chain robustness fix (class-shopos-theme.php:76 parent-handle dependency) | S | — (bugfix) | ShopOS CSS survives parent-handle absence on all stores | revert commit | wp-env assert: CSS loads with parent handle present AND absent |
| 3 | Typography: (a) self-host Heebo/Assistant/Rubik @font-face assets; (b) fonts flag + kit-slot filterable Core-option default (no UI) + FeatureFlagsAdminTest scan-root extension | M (two PRs, 3a ceiling override pre-authorized) | `shopos_core_theme_fonts_selfhost_enabled` | fonts identical on kit and non-kit pages; font files no longer depend on the Elementor kit | flag off → Style-Kit vars + hardcoded fallback stacks, as today | render-diff flag-off identity on staging; flag-on font parity across an Elementor page and a kit-less page; core CI green with extended scan roots |
| 4 | PDP: theme template at `templates/woo/single-product.php` + one-line flag-gated resolution in Core's `template_file()` | M | `shopos_core_theme_template_pdp_enabled` | parity; value = theme template layer proven on an already-PHP surface | flag off → Core Template_Loader module copy renders as today (permanent; module toggle remains the outer kill-switch) | full R7 bar: hook checklist via mu-plugin, staging render-diff flag-off, perf vs existing `product` budget, owner screenshot + RTL pass |
| 5 | PLP: `templates/woo/archive-product.php` + the shared theme loader (built now — the second template demands it) | L | `shopos_core_theme_template_plp_enabled` | parity; value = repeatability (first Elementor→PHP conversion) | flag off → Elementor archive template untouched, byte-identical | full R7 bar + search carve-out asserted (`is_search()` never claimed) + InfiniteScroll append-only selectors with flag-OFF pass, perf vs existing `shop` budget |
| 6 | **§11-B checkpoint:** header/footer chrome, cart, checkout, account, search-results template, transactional emails | gated | ruled at §11-B (names not minted) | ruled at §11-B | ruled at §11-B | gate: PDP+PLP flag-on ≥30 days spanning ≥1 subsequent theme release, zero rollbacks (flip dates recorded in the decisions state line) + theme PHPUnit/CI lane exists + store #2 committed or owner re-affirms architectural-only value. Carries: block-checkout content-migration note (Ruling 9), chrome require-parent passthrough (Ruling 6) |
| — | Dynamic tags / loop-grid | DEFER | — | — | — | — |

Every step: pre-flight → plan → owner approval → execute → verify (Ruling 7 criteria) → rollback section with the exact `wp option update` command (pr-template.md contract).

## §11.5 Backward compat

Flag-off behavior is the current production render on every surface — **including on the day a theme zip installs** (no auto-located paths, Ruling 5/§11.3) — verified via `tools/render-diff.sh` against staging-with-Elementor-Pro before merge; wp-env (Elementor free) covers the parent-fallback path only. No existing hook/filter/option/shortcode/route removed; Core's Template_Loader is permanent. No schema changes. No major version bump. Theme remains installable and functional without Core (pinned read path ⇒ all flags hard-false). CLAUDE.md infra line, baseline files, and `.pot` files move in the same PRs that touch them; the Ruling 5 doc rewrites are their own docs-only PR.

---
**Owner's decisions (2026-07-16): rulings 1-10 all approved as recommended.** (1) §11 = the Line only; (2) Option C — v1 = typography prerequisite → PDP → PLP, chrome cut from v1, §11-B gate as worded incl. the search carve-out; (3) §4.3 re-scoping approved as worded; (4) Option B — plugin-side Feature_Flags under virtual module id `theme`, the three flag names minted and frozen, pinned read path binding, sub-ruling 4a — permanent kill-switches, Template_Loader sunset withdrawn; (5) survive-theme-switch promise redefined as worded, ownership map as tabled, emails stay Core-side; (6) Option B — stay a Hello Elementor child, both chrome pre-rulings recorded; (7) verification bar 7.1-7.8 as worded; (8) registry-derived Blueprint coverage, Core options over theme_mods, theme-mod Blueprint extension deferred, kit-slot UI cut; (9) Option B — checkout technology pin deferred to §11-B with the load-bearing note carried; (10) Elementor retirement = direction not commitment, rollback fine print + fonts-before-templates ordering enforcement as worded.

---

**What the owner is approving:** a narrow, reversible first slice of the ShopOS Line — self-hosted typography, then a theme-owned PDP (an M-sized generalization of the existing PHP takeover, flag-gated by a one-line Core change), then a theme-owned PLP with the shared loader — each behind a permanent plugin-side kill-switch whose off-state is verified byte-identical to today's render on staging with real Elementor Pro, with templates deliberately kept out of every path WordPress or WooCommerce discovers by file presence. Chrome, cart, checkout, account, search results, and emails are all deferred behind a measurable §11-B gate (30+ days flag-on across a theme release, a theme CI lane, and either a committed store #2 or an explicit owner re-affirmation that the value is architectural), the checkout technology pin is recommended deferred to that same gate, and the Elementor-retirement end-state is recorded as direction rather than commitment — so approving §11 authorizes roughly three months of parity-preserving groundwork whose honest payoff is repeatability, not a shopper-visible change, and nothing that touches revenue-critical surfaces.
