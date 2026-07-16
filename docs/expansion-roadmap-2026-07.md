# Expansion Roadmap — 2026 H2

**Status: PROPOSAL — nothing committed.** This document sequences the *next phase* of
work toward the owner's stated goal: "a more complete theme with templates, enhanced
settings, modules with Elementor widgets, and advanced mechanisms." Like
[optional-future-features.md](optional-future-features.md), it is a forward plan, not a
commitment — each phase still enters the normal wave/PR process (pre-flight + plan +
owner approval) before any code. It complements, and does not replace,
[roadmap.md](roadmap.md) (the committed execution log).

**Prepared**: 2026-07-15, from a four-dimension codebase analysis (theme, settings,
widgets, mechanisms).

---

## The reframe

The committed execution roadmap is **nearly drained** — [roadmap.md](roadmap.md) "Active
items (2)" lists only #11 (ProductSlider `--ps-*` controls) and #3 (ProductFeed
multi-channel, P2/"only if a client asks"). So this is not backlog burn-down; it is a
**deliberate expansion**. The organizing thesis:

> **Extract shared foundations → fan out modules → then own the buy path.**

The load-bearing sequencing insight: **four foundations must land before the per-module
work that depends on them**, or every fan-out multiplies duplication. Today a third
Elementor widget would fork ~300 lines because there is no shared `Widget_Base`; the
Design panel needs settings control-types that do not exist yet; the token work needs a
`theme.json ↔ --shopos-ui-*` bridge or it deepens the hand-synced CSS drift.

## Where the four asks stand

| Dimension | Today | Target |
|---|---|---|
| **Theme + templates** | Thin Hello-Elementor child; **zero** Woo template overrides, no header/footer, no `woocommerce/` dir. Only PHP template in the suite is plugin-side ([ProductPage/Template_Loader.php](../shopos-core/src/Modules/ProductPage/Template_Loader.php)). | Theme *owns* the commerce path as **classic PHP/Woo overrides** (PLP → chrome → PDP), each flag-gated with an untouched-Elementor rollback. |
| **Enhanced settings** | Good declarative field API (6 control types) but the **color picker is unwired**, the `choices`-vs-`options` bug recurred 3×, Labels are copy-pasted across ~5 modules. | Hardened + richer field API (range/media/typography/color), a global **Design panel**, Labels DRYed into a shared base. |
| **Modules with Elementor widgets** | Only **2 of 15** modules ship widgets (both sliders); no shared base, no ShopOS widget category. | Shared `Widget_Base` + `shopos` category → new widgets are thin shells over existing tested render code. |
| **Advanced mechanisms** | Mature: Migrations, Importers, twin indexers, facet cache, PageTransitions. | Repeatability/ops layer: object-cache abstraction, `wp shopos` CLI, a named **Store Blueprint** so store #2 starts configured. |

---

## Sequenced phases

Effort key: **S** ≤1 PR / a day · **M** a focused wave · **L** multi-PR wave · **XL**
epic (multiple waves, split per surface). Dimension tags: `theme` · `settings` ·
`widgets` · `mechanisms`.

### Phase 0 — Quick wins (ship now, in parallel with Phase 1)

No dependencies; additive or already committed. Delivers visible momentum before the
refactors land.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| **[#11] ProductSlider `--ps-*` design tokens as Elementor controls** | widgets | S | COMMITTED. Mirror the shipped CategorySlider `--cs-*` (Wave 4.2); empty default → byte-identical render, no flag. |
| ✅ **Wire the Settings_Hub color picker** — **shipped 1.28.0 (2026-07-15)**, bundled with the field-API extension | settings | S | Enqueued `wp-color-picker` in `Settings_Hub::enqueue_admin_assets()` (legacy VariationSwatches pattern). Fixes ProductPage `button_color` + InfiniteScroll `shimmer_*`, previously bare hex. |
| **Harden the `choices`-vs-`options` bug class** | settings | S | Registry-drive the invariant test (`Module_Registry->all()`, not 4 hardcoded classes) + loud notice on empty select choices. |
| **Wire the dead accent presets to a Customizer control** | theme | S | Sanitized select stamping `.is-accent-ink/gold/forest` on `<body>`; interim until the Design panel. |
| **Loose ends** — theme `he_IL.po`/`.pot` (PR-21), owner-gated B-1 (legacy CSV twin), B-3 (InfiniteScroll stub choices) | mechanisms | S | Each independently shippable. |

### Phase 1 — Foundations (the critical path)

Extract the shared abstractions the fan-out depends on. Doing these first makes Phases
2–3 thin.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| ✅ **Extract `src/Core/Elementor/Widget_Base`** — **shipped 1.26.0 (2026-07-15)** | widgets | M | Pulled five shared helpers (`slider_int`/`slider_float`/`is_elementor_edit_mode`/`resolve_direction`/`get_term_options`) + the default `get_categories()` onto a new abstract base; both sliders now extend it. `ids_array` left per-widget (the two copies differ — ProductSlider splits strings). Behaviour-identical, `baseline-hooks.txt` regenerated (position-only), 806 tests green. See the kickoff plan below. |
| 🟡 **Register the `shopos` Elementor category** — **shipped 1.27.0 (2026-07-15)**; `Widget_Module` harness **deferred to Phase 2** | widgets | M | Category shipped: new `Core\Elementor\Category` registered once from `Plugin::boot()`, added additively to `Widget_Base::get_categories()` (`shopos` first; WooCommerce/General kept). The boot-wiring harness was deferred — only `register_widget()` is byte-identical between the two modules (asset registration differs), so it is built alongside the Search widget in Phase 2, the 3rd consumer that justifies + shapes it. |
| ✅ **Extend the settings field API additively** — **shipped 1.28.0 (2026-07-15)** | settings | M | Added four control types to `render_field()`/`sanitizer_for()`: `range` (slider + value readout, numeric coerce + clamp), `media` (media-library picker → attachment ID; `wp.media()` enqueued only when a `media` field exists), `typography-select` (font-family dropdown with in-list previews, whitelisted sanitize) and `multiselect` (array, whitelisted). New keys ignored by the existing 6 types → all current fields byte-identical; `SettingsHubFieldApiTest` (9 tests) added with an additivity guard. Bundled the Phase-0 `wp-color-picker` wiring (below). |
| ✅ **`theme.json ↔ --shopos-ui-*` token bridge** — **shipped shopos-theme 1.11.28 (2026-07-15)** | theme | M | New `shopos-theme/inc/design-tokens.php` reads presets via `wp_get_global_settings()` (theme.json + any user Global-Styles override) and emits 29 palette/spacing/radius/motion `--shopos-ui-*` custom properties inline after `shopos-tokens.css` (on `wp_enqueue_scripts`:21 via `wp_add_inline_style`); that file stays the semantic + fallback layer. All 29 values equal today's literals → byte-identical render on a live store, now sourced from theme.json (single source of truth). The 3 motion tokens also re-emit the `prefers-reduced-motion: reduce` → `0ms` collapse inside the inline block (it prints after `shopos-tokens.css`, so without it the base durations would override that file's reduced-motion reset — caught in adversarial review). Additive, no flag (var fallbacks; kill switch = `shopos_theme_design_tokens_enabled` filter). NOT bridged: typography (hand-tuned `clamp()` + `sk_type_*` bridge own it), the semantic `--shopos-ui-color-*` layer + `.is-accent-*` presets (flow through the palette), sourceless tokens (`palette-black`/`-sand`, `radius-round`, `motion-instant`, eases). **§4.3 reconciliation** (owner-confirmed 2026-07-15): this is the theme.json → CSS direction (front-end render) and is the *kept* CSS lane per §4.3's carve-out; it is **not** ex-Roadmap #7 (the dropped direction was tokens → block-editor pickers, killed for being Gutenberg-only). Theme has no PHPUnit → verified by lint + live-QA (computed styles unchanged; override a theme.json value and confirm it flows). |
| ✅ **DRY the Labels resolver + label-field loop** — **shipped shopos-core 1.29.0 (2026-07-15)** | settings | M | New abstract `Core\Labels_Base` holds the byte-identical option-backed `get()` shared by QuickView/ShopFilters/Search/ProductPage (LSB on subclass `OPTION_PREFIX`+`defaults()`); new `Module_Base::label_fields($defaults,$intro)` reproduces their `label_<key>` settings loop exactly. **Landed caller-free/additive** — no module adopts yet (adoption = per-module follow-up PRs, >3-module gate); VariationSwatches' locale-switch Labels stays separate. `LabelsBaseTest`+`ModuleBaseLabelFieldsTest` (byte-identity parity); no baselines/.pot change. **Phase 1 complete.** |

### Phase 2 — Fan out widgets on the shared base

Thin control shells delegating to existing tested render code. Ordered by value.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| ✅ **ShopOS Search widget** — **shipped 1.30.0 (2026-07-15)** | widgets | M | New `Search\Widget` (extends the Phase-1 `Widget_Base`) delegating to the pure `Frontend::render_form()`; `get_name()='shopos_search'` frozen at birth. Two optional placeholder/button controls fall through to the module Labels defaults when blank (the shortcode's own `shortcode_atts` fallback); no `get_style/script_depends()` — search.css/js are already head-enqueued on every front-end page. Wired via `elementor/widgets/register` inside the existing `Module::boot()` (that action only fires with Elementor active, so the Search module keeps booting Elementor-free — **no** module-level `elementor` dependency was added, which would have stopped the whole multi-surface module on an Elementor-less store). Shortcode + dropdown + results page unchanged. Purely additive → **no flag** (inert until placed; the slider-widget precedent + Hard Rule #1 additive exception, stated in the PR). `add_action` is a consumer → no hook-baseline drift; `.pot` regenerated. `SearchWidgetTest` (frozen id, inherited `shopos` category, pure settings→atts mapping). **`Widget_Module` boot-harness DECISION: stays deferred.** Phase 1 earmarked Search as the 3rd consumer that would "justify + shape" it, but on inspection Search shares only the one-line widget registration — not the sliders' asset-registration / editor-preview machinery — and can't extend a sliders' base class anyway (it already extends `Module_Base` and boots without Elementor). YAGNI holds; the harness is recorded as deferred, not built. |
| ✅ **ProductPage conversion-block widgets** — **shipped 1.31.0 (2026-07-15)** | widgets | M | Two thin widgets — `Stock_Urgency_Widget` (`shopos_stock_urgency`) + `Coupon_Notice_Widget` (`shopos_discounted_price`) — delegating to the existing `Stock_Urgency::shortcode()` / `Coupon_Notice::shortcode()`; ids frozen at the shortcode tags. **Correction to this row's original note:** the product_page feature flags were removed in the 1.23.0 graduation sweep, so there are no per-feature flags to gate on — the module-enable toggle is the kill-switch and the widgets ship flagless (additive, inert until placed). No per-instance controls (config is the module's global settings; a `RAW_HTML` panel note points there); no asset deps (each block's `enqueue()` already fires on single-product pages); an editor-mode placeholder shows when the block has nothing to render. Wired via `elementor/widgets/register` in the existing `Module::boot()` (Elementor-active-only; no module `elementor` dependency — that would stop the PDP takeover + shortcodes on an Elementor-less store). `ProductPageWidgetsTest`; `.pot` +8; the Elementor test stub gained an additive `RAW_HTML` const. |
| ✅ **RestockNotify widget** — **shipped 1.32.0 (2026-07-15)** | widgets | S | New `RestockNotify\Widget` (`shopos_restock_notify`) — thin shell over the module's already-shipped `[restock_notify]` shortcode; an explicit slot for the back-in-stock subscribe form on hook-bypassing Elementor/Theme-Builder PDPs where the `auto_inject` summary/variation hooks never fire. One optional `product_id` control (blank → the shortcode's own `detect_product_id()`) + a `RAW_HTML` note (form wording is global, in the Restock Notify admin); no asset deps (the module already enqueues its front-end assets). **Recipe divergence:** delegates via `do_shortcode()` rather than a throwaway `new Frontend()` — the RestockNotify `Frontend` constructor registers deferred `wp_footer` + `woocommerce_get_stock_html` hooks when `auto_inject` is on, and a second instance (a distinct object WP's unique-callback-id can't dedupe) would double-inject the form; `do_shortcode()` reuses the booted instance's callback + shares its `$rendered` per-product dedup. Editor placeholder via `is_elementor_edit_mode()`. Wired via `elementor/widgets/register` in the existing `Module::boot()` after the Woo + legacy-conflict guards (Elementor-active-only → module still boots Elementor-free; no module `elementor` dep). `get_name()` frozen. Purely additive → no flag. `RestockNotifyWidgetTest`; the widget id adds one `shopos_restock_notify` line to the option identifier baselines; `.pot` +4. |
| ✅ **ShopFilters widget** — **shipped 1.33.0 (2026-07-15)** | widgets | M | **Governance cleared** — §5.4's "no Elementor widget in v1" reversed, owner-approved 2026-07-15 (recorded as decisions §5.9); the reversal is narrow + additive (shortcode unchanged per Hard Rule #2; §5.5/§5.6/§5.8 untouched). New `ShopFilters\Widget` (`shopos_shop_filters`, frozen) — a thin shell over the module's `[shopos_shop_filters]` shortcode via a throwaway `new Shortcode()` calling the pure `render()` (the Search/ProductPage recipe, **not** RestockNotify's `do_shortcode()`: `Shortcode::render()` registers no deferred hooks, only builds + enqueues handle-deduped, so a second instance can't double-inject). No per-instance controls — the panel adapts to page context (shop → all facets, category → scoped, search → results) and all config lives in the module's global settings; one `RAW_HTML` note points there. No asset deps (render enqueues its own; the panel CSS is head-enqueued every front-end page). Context resolution is inherited verbatim from the shortcode's main-query conditional tags, so the widget adds no new `$wp_query`-swap risk over the archive-template shortcode placement already in production. Wired via `elementor/widgets/register` in the existing `Module::boot()` (Elementor-active-only; no module `elementor` dependency — that would stop the index / SEO policy / shortcode on an Elementor-less store). Purely additive → no flag (Hard Rule #1 additive exception; items-1–3 precedent). `add_action` is a consumer → no hook-baseline drift; the widget id already exists as the shortcode tag so the identifier baselines are unchanged; `.pot` regenerated. `ShopFiltersWidgetTest` (frozen id, inherited `shopos` category, single info-note control). Live-QA'd in wp-env (registers with Elementor + renders the panel identically to the shortcode on a shop/category context). |

### Phase 3 — Design panel + repeatability (store #2 becomes real)

Depends on Phase 1's field-API types + token bridge.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| **ShopOS → Design control panel** | settings | L | ✅ shipped 1.35.0 (2026-07-15) — owner-approved as decisions **§9**. Accent-preset picker + curated ~8-token allow-list; overrides emit via inline `wp_add_inline_style` after tokens.css (prio 30); `--e-global-*` Style-Kit values stay the fallback. Gated by `shopos_core_design_panel_enabled` (default off) + `shopos_core/design/tokens_css_enabled` kill-switch. Store Blueprint (still gated) would later snapshot these options. |
| **`Core/Cache` object-cache abstraction** | mechanisms | M | ✅ shipped 1.34.0 (2026-07-15) — `get/set/delete` + group + TTL; prefers `wp_cache_*` when `wp_using_ext_object_cache()`, degrades to today's transients (byte-identical with no backend); kill-switch `shopos_core/cache/use_object_cache` filter. Repointed the ShopFilters facet cache + rebuild lock; the "search memo" was a no-op (Search's per-request id cache is a static-var memo, not a transient). |
| **Scoped `wp shopos` CLI** | mechanisms | M | ✅ shipped 1.36.0 (2026-07-16) — new `Core\CLI` registered from `Plugin::boot()` behind the `WP_CLI` constant (inert on web requests). `reindex search\|shop-filters` drives the module's own `Indexer::reindex_batch()` in the admin tools' 50-product steps (byte-identical incl. the final watermark-parking call); `flags list` tables the registry with effective + forced-by-filter state; `flags set <module.feature> <on\|off>` writes the admin page's exact option shape, registry-validated. `blueprint export\|diff\|import` landed 1.39.0 (§10). `baseline-cli.txt` +1. Distinct from the dropped broad-REST non-goal (§4.4). |
| **Store Blueprint (settings-as-code)** | mechanisms | L | ✅ shipped 1.39.0 (2026-07-16) — owner-approved as decisions **§10**. `Core\Blueprint`: a named, versioned JSON preset of the five behavioural surfaces (modules map + all registry flags + the four modules' labels + facet config + §9 design tokens; 52 code-enumerated keys, never a DB scan). File = a valid Wave 0.3 envelope + a `blueprint {format, name, generator}` block → also importable via ShopOS → Tools; apply reuses `backup_current()` (same rolling-5 restore) but owns its write loop: strict validate (typo ⇒ zero writes), unchanged-skip idempotence, modules merge-by-id, not-yet-indexed facet taxonomies kept with a warning. `wp shopos blueprint export\|diff\|import` on the 1.36.0 CLI (no new `add_command`). Flagless-additive per the CLI precedent. |
| **Perf-budget / CWV tooling + un-mask shopos-digital CI** | mechanisms | M | ✅ shipped 1.38.0 (2026-07-16) — the mask was double (nested workflow never registered + `\|\| true` on phpunit): replaced by a root path-filtered `shopos-digital-ci.yml` (MySQL + WP test suite + PHPUnit ^9, PHP 8.1/8.3, unmasked). Budget half: gated `Core\Perf` probe (`shopos_core_perf_probe_enabled`, default off) emits `X-ShopOS-*` query/render/mem headers on `?shopos_perf=1`; `tools/perf-budget.php` checks per-template budgets in `tools/perf-budgets.json` (`--seed` = measured×1.25) — local/staging gate, not CI (no WP in CI). |
| **Real Dashboard overview + settings search** | settings | M | ✅ shipped 1.37.0 (2026-07-16) — one dashboard search box: live-filters the module cards + surfaces jump-to-setting deep links from the pure `Dashboard::settings_index()` (one row per `registry->all()` schema entry; url = settings page + `#<option_name>` fragment, `:target`-highlighted). Client-side over embedded JSON — no AJAX/REST (§4.4). Admin-only, additive, no flag. |

### Phase 4 — "The ShopOS Line": theme owns the buy path

Largest surface, highest backward-compat risk. Classic PHP only (§4.3 forbids FSE/block
patterns permanently). Strictly flag-gated, template-by-template, with the Elementor
render as the byte-identical rollback. Split per surface to respect the ≤12-file/≤3-module
ceilings.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| **"ShopOS Line" decisions addendum** | theme | S | ✅ **owner-approved 2026-07-16 as decisions §11** (rulings 1–10, all as recommended). Re-sequenced by §11 Ruling 2: v1 = typography prerequisite → **PDP** → **PLP** only; header/footer chrome is CUT from v1 and joins cart/checkout/account/search-results/emails behind the measurable **§11-B checkpoint** (PDP+PLP flag-on ≥30 days across ≥1 theme release, zero rollbacks + theme PHPUnit/CI lane + store #2 committed or owner re-affirms). Flags live plugin-side in `Feature_Flags::registry()` under virtual module id `theme` as PERMANENT kill-switches; templates only at `shopos-theme/templates/woo/` via a flag-gated loader — never `{theme}/woocommerce/`. Rows below re-read through §11.4's sequencing (docs-only rewrites PR → CSS-chain fix → typography 2 PRs → PDP → PLP → §11-B). |
| **De-hardcode Style-Kit typography + self-host webfonts** | theme | M | Move the `sk_type_*` → `--shopos-ui-font-*` mapping into the theme + a Customizer control for the kit slot IDs (default `sk_type_12/sk_type_2`, currently hardcoded to fixed slot IDs). `@font-face` for the Heebo/Assistant/Rubik Hebrew-first stack. |
| **First theme-owned Woo template: PLP/archive** | theme | L | `shopos-theme/woocommerce/` (or a theme template loader modeled on `ProductPage/Template_Loader.php`); flag-off renders the current Elementor path untouched. |
| **Own header/footer chrome** | theme | XL | `header.php` + `footer.php` (RTL/bilingual nav, cart utility, Search entry) so `get_header('shop')`/`get_footer('shop')` resolve to theme chrome. Theme-mod opt-in. Multiple PRs. |
| **Generalize PDP + reusable theme template layer** | theme | XL | Lift the ProductPage takeover into the shared theme loader so PDP joins PLP/chrome under one opt-in, per-template flag mechanism — the platform for later cart/checkout/account/email ownership. |
| **Dynamic tags / Theme-Builder loop-grid** | widgets | L | **DEFER** — zero consumers today; building speculatively violates Simplicity First. Revisit when a concrete data-binding need appears. |

---

## Guardrails

- **"Complete theme with templates" = classic PHP + Woo overrides, never `templates/*.html`.**
  Per decisions §4.3, FSE/block patterns are a permanent non-goal.
- **Three items needed explicit owner sign-off before any code** (STOP-and-ask): the ShopOS
  Line (reverses "theme = skin", **cleared as §11, 2026-07-16**), the ShopFilters widget
  (reverses §5.4, cleared as §5.9), and the Design panel + Store Blueprint (cleared as
  §9 / §10). **All standing gates are now cleared**; §11-B is the next gate (defined in §11
  Ruling 2), and every Phase-4 step still runs pre-flight → plan → owner approval.
- **Frozen identifiers**: every new widget locks `get_name()` at birth; every settings
  refactor asserts byte-identical option names/output before and after.
- **Theme has no PHPUnit** — theme/template changes are verified by live-QA on a live store +
  `.pot` regen; theme version bumps run through `tools/release.sh`, which stamps
  `style.css` + `SHOPOS_THEME_VERSION` in lockstep (since 1.11.27; §11 Ruling 7.6).
- **Permanent non-goals** — record, don't re-propose: broad REST (§4.4) and Gutenberg/FSE
  (§4.3).

---

## Kickoff plan — Phase 1: extract `Core/Elementor/Widget_Base`

The recommended first move (unblocks all widget fan-out; strictly behavior-identical).
**✅ Shipped 1.26.0 (2026-07-15)** — extracted behaviour-identical; full suite green (806 tests / 2236 assertions).

**Extraction surface** (verified in-tree):

- **Identical helpers duplicated in both widgets**: `ids_array()`
  ([ProductSlider:665](../shopos-core/src/Modules/ProductSlider/Widget.php#L665) /
  [CategorySlider:640](../shopos-core/src/Modules/CategorySlider/Widget.php#L640)),
  `slider_int()`, `is_elementor_edit_mode()`, `resolve_direction()`.
- **ProductSlider-only helper, safe to host on the base**: `slider_float()`.
- **Near-identical, differing signature** (needs care): `get_term_options()` — ProductSlider
  takes `$taxonomy`, CategorySlider takes none. Host a parameterized version; keep each
  caller's usage identical.
- **Identical identity defaults**: `get_categories()` returns
  `['woocommerce-elements','general']` in both → becomes the base default (extended, not
  replaced, when the `shopos` category lands). `get_icon()` likewise defaultable.
- **Per-widget, DO NOT move**: `get_name()` (frozen: `shopos_product_slider`,
  `shopos_category_slider`), `get_title()`, `register_controls()`, `render()`.
- **Module registration boilerplate** (identical `boot()` + `register_widget()` +
  `register_styles/scripts` + `enqueue_front_style(s)` + `enqueue_editor_style` across both
  `Module.php` files) → folds into the `Widget_Module` harness in the next Phase-1 item.

**Constraints**: no feature flag (behavior-identical refactor — Hard Rule #1 additive/refactor
exception, state it in the PR); `render()` output byte-identical; full PHPUnit suite green
before and after; regenerate `tests/baseline-hooks.txt` only for position-only line shifts;
version bump touches both `shopos-core.php` and `Plugin.php` + the CLAUDE.md infra line.

**Verification**: existing `ProductSliderArchiveQueryTest` / `CategorySliderModuleTest` +
the widget tests stay green; add a `WidgetBaseTest` covering the extracted pure helpers
(`ids_array`, `slider_int`, `slider_float`, `resolve_direction`); live-QA both widgets in
the Elementor editor + on a live store (drag, snap, RTL) to confirm identical render.
