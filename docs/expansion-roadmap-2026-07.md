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
| **ProductPage conversion-block widget(s)** | widgets | M | Stock-urgency / coupon-notice widgets delegating to the existing shortcode handlers; gated by their existing feature flags. |
| **RestockNotify button widget** | widgets | S | Delegates to `Frontend::shortcode()`; explicit slot on hook-bypassing Elementor PDPs. |
| **ShopFilters widget** | widgets | M | **GOVERNANCE FIRST** — reverses recorded decision §5.4 (shortcode-only in v1). CEO/eng plan review before code; verify inside Theme-Builder archive templates where `$wp_query` is swapped. |

### Phase 3 — Design panel + repeatability (store #2 becomes real)

Depends on Phase 1's field-API types + token bridge.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| **ShopOS → Design control panel** | settings | L | **PROPOSAL** — needs decisions addendum + `shopos_core_design_panel_enabled` (default off). Accent-preset picker + curated token overrides via `wp_add_inline_style` after tokens.css; `--e-global-*` Style-Kit bridge stays as fallback. |
| **`Core/Cache` object-cache abstraction** | mechanisms | M | `get/set/delete` + group + TTL; prefers `wp_cache_*` when `wp_using_ext_object_cache()`, degrades to today's transients (byte-identical with no backend). Repoint the facet cache + search memo. |
| **Scoped `wp shopos` CLI** | mechanisms | M | `reindex search\|shop-filters`, `flags list\|set`, later `blueprint export\|import`. Regenerate `baseline-cli.txt`. Distinct from the dropped broad-REST non-goal (§4.4). |
| **Store Blueprint (settings-as-code)** | mechanisms | L | **PROPOSAL** — needs decisions addendum. `Core/Blueprint` over the Wave 0.3 envelope: modules + flags + labels + facet_config + token overrides as a named, versioned preset; import reuses auto-backup + prefix-allowlist. |
| **Perf-budget / CWV tooling + un-mask shopos-digital CI** | mechanisms | M | Drop the `\|\| true` mask; add a per-template query-count/CWV-proxy budget seeded by the `/benchmark` browse daemon. |
| **Real Dashboard overview + settings search** | settings | M | Client-side filter over module cards + a "jump to setting" index built from `registry->all()` schemas. Admin-only, additive. |

### Phase 4 — "The ShopOS Line": theme owns the buy path

Largest surface, highest backward-compat risk. Classic PHP only (§4.3 forbids FSE/block
patterns permanently). Strictly flag-gated, template-by-template, with the Elementor
render as the byte-identical rollback. Split per surface to respect the ≤12-file/≤3-module
ceilings.

| Item | Dim | Effort | Notes |
|---|---|---|---|
| **"ShopOS Line" decisions addendum** | theme | S | **GATE** — owner approval required (STOP-and-ask). Theme owns PLP/PDP/cart/checkout/account/search/email as classic PHP/Woo overrides; reaffirm no-FSE; Elementor = storytelling pages only. No code. Blocks everything below. |
| **De-hardcode Style-Kit typography + self-host webfonts** | theme | M | Move the `sk_type_*` → `--shopos-ui-font-*` mapping into the theme + a Customizer control for the kit slot IDs (default `sk_type_12/sk_type_2`, currently hardcoded to fixed slot IDs). `@font-face` for the Heebo/Assistant/Rubik Hebrew-first stack. |
| **First theme-owned Woo template: PLP/archive** | theme | L | `shopos-theme/woocommerce/` (or a theme template loader modeled on `ProductPage/Template_Loader.php`); flag-off renders the current Elementor path untouched. |
| **Own header/footer chrome** | theme | XL | `header.php` + `footer.php` (RTL/bilingual nav, cart utility, Search entry) so `get_header('shop')`/`get_footer('shop')` resolve to theme chrome. Theme-mod opt-in. Multiple PRs. |
| **Generalize PDP + reusable theme template layer** | theme | XL | Lift the ProductPage takeover into the shared theme loader so PDP joins PLP/chrome under one opt-in, per-template flag mechanism — the platform for later cart/checkout/account/email ownership. |
| **Dynamic tags / Theme-Builder loop-grid** | widgets | L | **DEFER** — zero consumers today; building speculatively violates Simplicity First. Revisit when a concrete data-binding need appears. |

---

## Guardrails

- **"Complete theme with templates" = classic PHP + Woo overrides, never `templates/*.html`.**
  Per decisions §4.3, FSE/block patterns are a permanent non-goal.
- **Three items need explicit owner sign-off before any code** (STOP-and-ask): the ShopOS
  Line (reverses "theme = skin"), the ShopFilters widget (reverses §5.4), and the Design
  panel + Store Blueprint (uncommitted).
- **Frozen identifiers**: every new widget locks `get_name()` at birth; every settings
  refactor asserts byte-identical option names/output before and after.
- **Theme has no PHPUnit** — theme/template changes are verified by live-QA on a live store +
  `.pot` regen, and `SHOPOS_THEME_VERSION` must be bumped **manually**
  (`tools/release.sh` historically bumped only `style.css`).
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
