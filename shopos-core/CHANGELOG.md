# ShopOS Core — Changelog

## [1.43.0] — 2026-07-16

- ShopOS Line §11.4 row 4 — theme/template_pdp permanent kill-switch in Feature_Flags::registry() + flag-gated theme-copy rung in ProductPage Template_Loader::template_file() (theme copy at templates/woo/single-product.php; public override still wins; missing-copy fallback logs once; Ruling-10 fonts_selfhost warning)

## [1.42.2] — 2026-07-16

- Theme fonts_selfhost flag + kit-slot de-hardcode (§11.4 row 3b): new theme/fonts_selfhost entry in Feature_Flags::registry() (the §11 Ruling-4 frozen name shopos_core_theme_fonts_selfhost_enabled; permanent kill-switch; Blueprint coverage auto-derives from the registry) + Design::kit_slots() — the sk_type_12/sk_type_2 Style-Kit slot mapping de-hardcoded behind the shopos_core_theme_kit_slots Core option with the shopos_core/theme/kit_slots filter, no UI, defaults byte-identical (Ruling 8). FeatureFlagsAdminTest scan roots extended to shopos-theme/ (bidirectional assertions intact)

## [1.42.1] — 2026-07-16

- Updater: manifest cache 6h → 5min for near-instant dashboard updates

## [1.42.0] — 2026-07-16

- Dashboard self-updates via ShopOS release channel (Updater service)

## [1.41.0] — 2026-07-16

- Phase-1 leftover, part 2 of 2 — **ProductPage `Labels_Base` adoption (resolver only)**: ProductPage drops its hand-rolled `Labels::get()` for the shared `Core\Labels_Base` resolver — the last of the four modules, closing the Phase-1 DRY item. Resolver semantics byte-identical (same `shopos_core_product_page_label_<key>` options, non-empty-trim override rule, English-default fallback, empty-default trust lines stay hidden). Deliberately **not** adopting `Module_Base::label_fields()`: the module's settings label loop routes each key to a per-prefix section, words descriptions differently ("Leave blank to use the default: %s") and special-cases the empty-default trust lines — none reproducible by the helper; the custom loop stays, pinned untouched by `LabelsAdoptionTest`. No hook/option/string changes: baselines + `.pot` unchanged, Blueprint keys unaffected (935 tests / 2669 assertions green)

## [1.40.0] — 2026-07-16

- Phase-1 leftover — **`Labels_Base` + `label_fields()` adoption (QuickView + ShopFilters + Search)**: the three modules drop their hand-rolled `Labels::get()` for the shared `Core\Labels_Base` resolver (1.29.0, until now caller-free; late static binding on each subclass's `OPTION_PREFIX` + `defaults()`) and replace their `settings_schema()` label loops with `Module_Base::label_fields()` + the module's intro sentence. A pure refactor — same option names, same non-empty-trim override rule, same English-default fallback, same schema arrays — pinned by the new `LabelsAdoptionTest` (each schema label block `===` a verbatim replica of the pre-adoption loop; resolver override / blank-falls-back / default parity per module prefix; `count_text()` still rides the inherited resolver). ProductPage adopts the resolver in its own follow-up PR (≤3-module gate; its sectioned label loop is not `label_fields()`-compatible). No hook, option, or string changes: baselines unchanged, `.pot` regenerated reference-only, Blueprint's 35 label keys unaffected (933 tests / 2639 assertions green)

## [1.39.0] — 2026-07-16

- Phase-3 (mechanisms) — **Store Blueprint (settings-as-code)** (decisions **§10**, owner-approved; the last Phase-3 item): new `Core\Blueprint` + a `wp shopos blueprint export|diff|import` subcommand on the 1.36.0 CLI. A Blueprint is a named, versioned JSON file capturing the suite's five behavioural option surfaces — the `shopos_core_modules` map, every `Feature_Flags::registry()` flag, the four modules' `label_*` overrides (QuickView/ShopFilters/Search/ProductPage), `shopos_core_shop_filters_facet_config`, and the §9 `shopos_core_design_*` tokens — **52 keys enumerated from code registries at export time** (never a DB scan, so runtime state can never leak into a preset; absent options export as semantic defaults for portability). The file is a valid Wave 0.3 envelope (version 1) plus a `blueprint {format, name, generator}` block `validate_envelope()` tolerates — so every Blueprint is also importable through ShopOS → Tools and shares the rolling-5 auto-backup/Restore machinery; `blueprint import` requires the block, so a full-store envelope never accidentally gets Blueprint semantics, and a newer `generator` warns. **Apply owns its write loop** (deliberately not `Settings_Tools::import()`, whose halt-on-`update_option()===false` also fires on *unchanged* values — fatal for idempotent re-apply — and which writes raw values with no CLI-side sanitisers): strict validation rejects typos (`unexpected_key`/`invalid_value`) with zero writes; every value is normalised to the exact admin-page shape (flags `'1'`/`'0'` registry-validated, labels text-sanitised, facet rows through `Facet_Config::sanitize()` with type always recomputed, design tokens through the §9 pure seams); the modules map **merges by id** so a stale preset can never disable modules shipped after the snapshot (unknown ids drop with a warning); facet taxonomies missing from the store's filter index are **kept with a warning** (§10.4 implementation note — the index is empty on exactly the fresh store #2 this exists for; rows activate after `wp shopos reindex shop-filters`); unchanged values are skipped with `written`/`skipped` counts (idempotent re-apply is first-class); a pre-apply `backup_current('blueprint')` lands in the same rolling-5 store the Tools page restores from. `wp shopos blueprint diff <file>` is the dry-run: an option/action/current/blueprint table + would-change summary, zero writes. Purely additive → no flag (WP_CLI-guarded, operator-invoked — the 1.36.0 CLI + `flags set` precedent); CLI strings untranslated per convention. New actions `shopos_core/blueprint/before_apply` + `after_apply` (mirroring the Tools import pair) → `baseline-hooks.txt` regenerated (+2, plus a position-only `add_command` line shift in `baseline-cli.txt`); no new options; `.pot` unchanged. Added `BlueprintTest` (registry-composed key set, runtime-state exclusion, Tools-envelope interop round-trip, strict validator + loose-flag acceptance, per-surface normalisers, merge-by-id, keep-with-warning facets, idempotent re-apply, zero-write rejection, forced-flag warning) + `CliTest` blueprint dispatch/IO-error coverage. 928 tests / 2621 assertions green

## [1.38.0] — 2026-07-16

- Phase-3 (mechanisms) — **Perf-budget tooling + un-masked shopos-digital CI**, two halves: **(A)** shopos-digital's CI was doubly dead — its workflow lived at `shopos-digital/.github/workflows/` (GitHub only registers root workflows, so it never ran at all) AND its phpunit step ended in `|| true`; replaced by a root `.github/workflows/shopos-digital-ci.yml` that actually gates: path-filtered to `shopos-digital/**`, MySQL 8 service + WP test suite + PHPUnit ^9 + Yoast polyfills on PHP 8.1/8.3 (7.4 lane dropped with the rest of the repo, 1.11.22), **no mask on the phpunit step**, plus a `php -l` sweep the root CI never covered (its find scoped `shopos-core shopos-theme tests tools` only). Dead nested workflow deleted. **(B)** new gated `Core\Perf` probe + `tools/perf-budget.php` per-template budget check: with `shopos_core_perf_probe_enabled` ON (**default false**, new `Feature_Flags::registry()` entry `perf`/`probe`) a storefront request carrying `?shopos_perf=1` responds with `X-ShopOS-Queries` / `X-ShopOS-Render-Ms` / `X-ShopOS-Mem-MB` (numbers only — no query text, nothing sensitive), emitted at `shutdown:0` behind an output buffer opened at `template_redirect:0` so headers stay sendable (WP core flushes at shutdown:1); flag off **or** param missing ⇒ `boot()` registers zero listeners ⇒ byte-identical. The per-flag `shopos_core/feature_flag/perf/probe` filter built into `is_enabled()` is the code-level kill-switch (no new filter declared). `tools/perf-budget.php <base-url> [budgets.json] [--seed]` probes each template in `tools/perf-budgets.json`, compares queries/render-ms/mem/body-bytes against budget, exits non-zero on breach; `--seed` writes measured×1.25 headroom. **Deliberately NOT a CI gate** (repo CI has no WordPress) — a local/staging gate, seeded against the wp-env reference store. Added `PerfProbeTest` (zero-listener contract without the param, arm/emit pair at prio 0, emit-without-arm no-op, header map shape/clamps, `$timestart` source). `baseline-hooks.txt` position-only `booted` shift; `baseline-options-declared.txt` +1 intended additive `shopos_perf` identifier; `.pot` regenerated (+flag label/description). 897 tests / 2495 assertions green

## [1.37.0] — 2026-07-16

- Phase-3 (settings) — **Dashboard overview search + jump-to-setting**: the ShopOS dashboard gains one search box that does two jobs client-side, with zero network calls (no AJAX/REST — §4.4 respected). Typing live-filters the module cards (label + description match via a new `data-shopos-card` attribute on each card); beneath the input, matching settings surface as **jump-to-setting** deep links built from the new pure `Dashboard::settings_index()` — one row per `settings_schema()` entry across `registry->all()`, each linking to the module's settings page with the option name as the URL fragment (every field already renders with `id="<option_name>"` via `Settings_Hub::render_field`, so the browser scrolls straight to the field and a new `admin.css` `:target` rule highlights it). The index is embedded as JSON at render time (`JSON_HEX_TAG` so an embedded `</script>` can't break out); the inline filter script builds result DOM via `textContent` only and lowercases both haystack and query client-side so Hebrew/non-ASCII labels fold the same way the query does. Matching covers setting label, key, section, and module label; results cap at 10. Admin-only, purely additive → no flag (inert until someone types; existing cards/pages/options/hooks untouched). Added `DashboardSearchTest` (row-per-entry + schema-less-module skip, deep-link URL + fragment shape, label→key fallback, haystack coverage, JSON-safety of rows). No new hooks/options → baselines unchanged; `.pot` regenerated (+2 UI strings). 890 tests / 2457 assertions green

## [1.36.0] — 2026-07-16

- Phase-3 (mechanisms) — scoped **`wp shopos` CLI**: a new `Core\CLI` command class registered from `Plugin::boot()` behind the `WP_CLI` constant, so it is inert on every web request by construction (deliberately narrow per decisions §4.4's dropped broad-REST non-goal — no new web surface). Three sub-commands: **`wp shopos reindex <search|shop-filters>`** drives the target module's already-tested `Indexer::reindex_batch()` in the same 50-product steps as the admin reindex tools — byte-identical in effect, including the terminating zero-row call that parks the reconcile watermark + last-run at "now" (and, for shop-filters, the same per-batch facet-cache flush + rev bump the method already performs); it refuses cleanly when the target module is disabled or its WooCommerce dependency is missing. **`wp shopos flags list`** renders one table row per `Feature_Flags::registry()` entry (module.feature / effective state via the same `is_enabled()` the runtime uses / forced-by-filter column / since). **`wp shopos flags set <module.feature> <on|off>`** writes the exact `shopos_core_<module>_<feature>_enabled` = `'1'`/`'0'` option shape the Feature Flags admin page writes, validated against the canonical registry so a typo can never mint a stray option; when a code-level `shopos_core/feature_flag/*` filter forces the flag it still saves but warns that the filter wins, and the success line always reports the *effective* state. CLI output strings are untranslated on purpose (shopos-digital `wp fd` precedent). Command name `shopos` frozen at birth. Purely additive → no feature flag (WP-CLI-only surface; nothing to gate on a web request). Added `CliTest` (register() no-op without WP-CLI, target→module mapping, strict flag/state parsing against the registry, flags-list rows incl. filter-forced effective state, set/reject/warn behaviour) + recording `WP_CLI` stubs in the test bootstrap. `baseline-cli.txt` gains the one new `add_command` site; `baseline-hooks.txt` position-only `booted` shift; identifier baselines + `.pot` unchanged. 885 tests / 2440 assertions green

## [1.35.0] — 2026-07-15

- Phase-3 (settings) — **ShopOS Design panel** (decisions §9, owner-approved): a new `Core\Design` feature adding a **ShopOS → Design** admin page where the owner picks an accent preset and, optionally, overrides a small curated allow-list of design tokens — accent/primary, text, text-soft, surface, surface-alt, borders (all `--shopos-ui-palette-*` colours) + one corner-radius (`--shopos-ui-radius-md`). Choices emit as an inline `:root{ --shopos-ui-*: … }` block on `wp_enqueue_scripts` at priority 30 — **after** the theme's Phase-1 token bridge — via an inline-only `wp_add_inline_style` that depends on the `shopos-tokens` handle when present, so overrides win suite-wide (ShopFilters / Search / QuickView / ProductPage / sliders) on a shopos-theme site and a non-theme site alike. The `--e-global-*` Elementor Style-Kit values stay the fallback in the existing `var()` chains, never the target; §4.3 (Elementor-only) is untouched — this sets semantic tokens, not page layout, and never pushes into the block editor. Gated by `shopos_core_design_panel_enabled` (**default false**): off ⇒ no admin page, no `wp_enqueue_scripts` listener, no CSS ⇒ byte-identical to pre-panel output. Even flag-on is inert until the owner changes something — the default accent contributes nothing and blank colours inherit, so `resolve_values()` returns an empty map and nothing is enqueued. Only what the owner set is emitted, through the same value-shape sanitiser the theme bridge uses (`build_css()` re-validates, defence in depth, and rejects any non-`--shopos-ui-*` var name). Additive emit kill-switch filter `shopos_core/design/tokens_css_enabled` (default true), mirroring the theme bridge's. The admin colour fields reuse the `shopos-color-field` class the ShopOS admin already wires to `wp-color-picker`. New flag registered in `Feature_Flags::registry()` (module `design` / feature `panel`). Added `DesignPanelTest` (preset base + individual-colour-override + radius resolution, invalid-value drops, `:root` build + unsafe-value/var-name rejection). `baseline-hooks.txt` regenerated (new `tokens_css_enabled` filter + position-only `booted` shift); `baseline-options-declared.txt` regenerated (+`shopos_core_design_` prefix + group literals); `.pot` regenerated (panel + preset strings). 869 tests / 2384 assertions green

## [1.34.0] — 2026-07-15

- Phase-3 (mechanisms) — new `Core\Cache` object-cache abstraction: one `get()` / `set()` / `delete()` facade (key + optional TTL + object-cache group) that routes through the `wp_cache_*` API when a persistent external object cache is installed (`wp_using_ext_object_cache()`) and otherwise degrades to the same `*_transient()` calls used before — **byte-identical on a store with no object cache**: the transient name is the caller's already-namespaced key verbatim (the group is a first-class argument for the `wp_cache_*` backend only, since transients have no native group concept), the TTL passes through unchanged, and a miss returns `false` exactly like `get_transient()`. A `shopos_core/cache/use_object_cache` filter (default `wp_using_ext_object_cache()`) is the kill-switch back to transients. Repointed the ShopFilters facet-response cache + its rebuild lock ([Query_Builder.php](src/Modules/ShopFilters/Query_Builder.php), the 1.21.24/1.21.30 transient cache) onto the facade under a `shopos_shop_filters` group, so a store with Redis/Memcached now keeps the hot facet payload in RAM instead of the options table; a store without one behaves identically. Scope note: the roadmap named a "search memo" repoint too, but the Search engine's per-request id memo is a static-var cache (1.21.19), not a transient — nothing to repoint there; durable state (importer backups, rate-limit counters) deliberately stays off the abstraction (object caches are volatile). Purely additive → no feature flag (new helper class + additive filter). Added `CacheTest` (both backends' get/set/delete contract, transient-key-verbatim byte-identity, kill-switch, group isolation). `baseline-hooks.txt` regenerated (new `use_object_cache` filter entry + position-only `Query_Builder.php` shifts). 856 tests / 2342 assertions green

## [1.33.0] — 2026-07-15

- Phase-2 fan-out (item 4): new **ShopOS Shop Filters** Elementor widget (`shopos_shop_filters`) — a thin shell over the Shop Filters module's already-shipped `[shopos_shop_filters]` shortcode, so dragging it onto a shop / category / product-archive template is equivalent to placing the shortcode in an Elementor HTML/shortcode element, now as a first-class draggable widget in the ShopOS panel category. **Reverses decision §5.4** ("no Elementor widget in v1") — owner-approved 2026-07-15; the reversal is narrow and additive: the `[shopos_shop_filters]` shortcode is unchanged (Hard Rule #2), the widget is a *second* placement surface, and §5.5 (AJAX transport) / §5.6 / §5.8 are untouched. No per-instance controls — the panel adapts to the current page (shop → all facets, category → scoped to that category, search → scoped to the results) and all configuration (facet matrix, panel wording, panel style) lives in the module's global settings; a `RAW_HTML` note points there. No style/script deps — `Shortcode::render()` enqueues its own assets and the panel stylesheet is head-enqueued on every front-end page. **Delegation follows the Search / ProductPage recipe** (a throwaway `new Shortcode()` calling the pure `render()`), *not* RestockNotify's `do_shortcode()` divergence: `Shortcode::render()` registers no deferred hooks — it only builds markup, resolves its own context from the main-query conditional tags (exactly as the in-production shortcode placement does), and enqueues handle-deduped assets — so a second instance can't double-inject. Context resolution is inherited verbatim from the shortcode, so the widget adds no new query-context handling and no new `$wp_query`-swap risk over the shortcode placement already live in archive templates. Registered via `elementor/widgets/register` inside the existing `Module::boot()` — that action only fires with Elementor active, so the module keeps booting Elementor-free (no module-level `elementor` dependency, which would otherwise stop the index / SEO policy / shortcode on an Elementor-less store). `get_name()` frozen at `shopos_shop_filters`. Purely additive → no flag (inert until placed; slider-widget precedent, Hard Rule #1 additive exception). `add_action` is a consumer so no hook-baseline drift; the widget id `shopos_shop_filters` already exists as the shortcode tag so the identifier baselines are unchanged; `.pot` regenerated. `ShopFiltersWidgetTest` added (849 tests / 2324 assertions green)

## [1.32.0] — 2026-07-15

- Phase-2 fan-out (item 3): new **ShopOS Restock Notify** Elementor widget (`shopos_restock_notify`) — a thin control shell over the RestockNotify module's already-shipped `[restock_notify]` shortcode, so dropping it onto a hook-bypassing Elementor-built or Theme-Builder product page places the back-in-stock subscribe form exactly where the module's `auto_inject` summary/variation hooks never fire. One optional **Product ID** control (blank → the shortcode's own `detect_product_id()` auto-detection) and a `RAW_HTML` note pointing at the Restock Notify admin (form heading/button/wording is global); no style/script deps since the module already enqueues its ~3 KB front-end assets on product/shop/cart/checkout pages. **Delegation differs from the Search / ProductPage widgets on purpose:** those construct the delegate class directly, but RestockNotify's `Frontend::__construct()` registers deferred hooks — `wp_footer` (pri 50) + the `woocommerce_get_stock_html` filter when `auto_inject === 'yes'` — and a throwaway `new Frontend()` is a distinct object that WordPress' unique-callback-id would not dedupe, so it would inject the form a second time. Instead `render()` calls `do_shortcode( '[restock_notify …]' )`, invoking the booted instance's already-registered callback: zero new hooks, and it shares the class-level `$rendered` per-product dedup guard so it never doubles a form the auto-inject path already placed this request. In the Elementor editor (no product context) a placeholder hint shows instead of the shortcode's invisible `<!-- RSN: … -->` no-product comment. Registered via `elementor/widgets/register` inside the existing `Module::boot()`, after the WooCommerce + legacy-conflict guards — the action only fires with Elementor active, so the module keeps booting (shortcode + auto-inject) Elementor-free (no module-level `elementor` dependency). `get_name()` frozen at `shopos_restock_notify`. Purely additive → no flag (inert until placed; slider-widget precedent). `add_action` is a consumer so no hook-baseline drift; the new widget id adds one `shopos_restock_notify` line to the identifier baselines; `.pot` regenerated (+4 strings). `RestockNotifyWidgetTest` added (845 tests / 2317 assertions green)

## [1.31.0] — 2026-07-15

- Phase-2 fan-out (item 2): two new ProductPage conversion-block Elementor widgets — **ShopOS Stock Urgency** (`shopos_stock_urgency`) and **ShopOS Coupon Price** (`shopos_discounted_price`) — thin shells over the module's already-shipped `Stock_Urgency::shortcode()` / `Coupon_Notice::shortcode()`, so dragging one onto a pre-takeover Elementor-built or Theme-Builder product page places the badge/notice where the `woocommerce_single_product_summary` hook never fires (the designed PDP still auto-places both). No per-instance controls — both are driven by the module's global settings (an info note in the panel points there); no style/script deps since each block's `enqueue()` already fires on single-product pages. When the block has nothing to show, the Elementor editor renders a small placeholder hint. Registered via `elementor/widgets/register` inside the existing `Module::boot()` — that action only fires with Elementor active, so the module keeps booting Elementor-free (no module-level `elementor` dependency, which would otherwise stop the PDP takeover + shortcodes on an Elementor-less store). `get_name()` frozen at the shortcode tags. Purely additive → no flag (inert until placed; slider-widget precedent). `add_action` is a consumer so no hook-baseline drift; the Elementor test stub gained the additive `RAW_HTML` control const; `.pot` regenerated. `ProductPageWidgetsTest` added (839 tests / 2304 assertions green)

## [1.30.0] — 2026-07-15

- Phase-2 fan-out (item 1): new **ShopOS Search** Elementor widget — a thin control shell over the Search module's already-shipped `Frontend::render_form()`, so dragging it onto a page is equivalent to placing the `[shopos_search]` shortcode. Two optional text controls (placeholder / button) fall through to the module's Labels defaults when blank; the globally head-enqueued search assets enhance it into the live dropdown, so the widget declares no style/script dependencies. Registered via `elementor/widgets/register` inside the module's existing `boot()` — that action only fires when Elementor is active, so the Search module keeps booting Elementor-free (no module-level `elementor` dependency, which would otherwise stop the whole module on an Elementor-less store). `get_name()` frozen at `shopos_search`. Purely additive (new widget, inert until placed — no flag, Hard Rule #1 additive exception; the slider-widget precedent). `SearchWidgetTest` added (832 tests / 2291 assertions green)

## [1.29.0] — 2026-07-15

- DRY the Labels resolver + label-field loop (Phase-1 foundation, landed caller-free / additive): new abstract `ShopOS\Core\Core\Labels_Base` holds the byte-identical option-backed `get()` (saved `<OPTION_PREFIX><key>` override when non-empty, else the English default) that QuickView / ShopFilters / Search / ProductPage each hand-roll, resolved via late static binding on the subclass's `OPTION_PREFIX` const + `defaults()` map; and new `Module_Base::label_fields( $defaults, $intro )` reproduces those modules' `label_<key>` text-field settings loop exactly. No module adopts either yet (adoption is per-module follow-up PRs, to respect the >3-module change gate), so behaviour is unchanged. VariationSwatches' locale-switch `Labels` (he()/en(), no options) is intentionally not a subclass. Added `LabelsBaseTest` + `ModuleBaseLabelFieldsTest` (incl. a byte-identity parity assertion vs the shipped loop)

## [1.28.0] — 2026-07-15

- Settings field API extended additively with four new control types — `range` (slider with min/max/step and a value readout), `media` (WordPress media-library picker storing an attachment ID), `typography-select` (font-family dropdown that previews each stack in-list), and `multiselect` — so the upcoming Design panel can declare richer fields. The existing six control types render and sanitize byte-identically; the media picker's `wp.media()` script is only enqueued when a module actually declares a `media` field. Separately, `wp-color-picker` is now wired onto every `color` field — previously the picker script was never enqueued, so colours showed as bare hex text (ProductPage buy-button colour, Infinite Scroll shimmer colours)

## [1.27.0] — 2026-07-15

- New "ShopOS" Elementor panel category: the Category Slider and Product Slider widgets now also appear under a dedicated **ShopOS** group in the Elementor editor's widget panel (their existing WooCommerce/General placements are kept), so ShopOS widgets are easier to find. Purely additive — nothing moves or disappears

## [1.26.0] — 2026-07-15

- Internal: extracted a shared `ShopOS\Core\Core\Elementor\Widget_Base` that the Category Slider and Product Slider widgets now extend, so future ShopOS Elementor widgets inherit the common setting-coercion (`slider_int`/`slider_float`), direction and term-option helpers instead of re-implementing them. Behaviour is unchanged (byte-identical render)

## [1.25.1] — 2026-07-14

- Page Transitions live-QA round: the cross-fade now waits for the new page content (render-blocking expect marker) so it no longer lands on a white frame, Shop Filters category links trigger the loading overlay too, and the fade is skipped on back/forward so it cannot fight the infinite-scroll position restore

## [1.25.0] — 2026-07-14

- New Page Transitions module (default off): a loading overlay with spinner on shop-filter, search and pagination interactions plus a cross-document fade between pages in supporting browsers; enable it under ShopOS - Modules

## [1.24.12] — 2026-07-14

- InfiniteScroll skeletons now clone the real card width as well as height (WooCommerce float-layout width rules were shrinking them to a sliver in the ProductSlider grid) with a CSS stretch fallback for unmeasured grids

## [1.24.11] — 2026-07-14

- Fix storefront flash of unstyled Shop Filters panel and product-grid cards (stylesheets now head-enqueued before first paint) and make InfiniteScroll loading skeletons measure a real product card so they match the live card size

## [1.24.10] — 2026-07-14

- fix(shopos-core): Search + Shop Filters — filtered search rendered a blank grid (slice-before-intersect); widget now slices the composed id set after all constrainers, and search ordering got a deterministic tiebreak

## [1.24.9] — 2026-07-09

- InfiniteScroll: harden back-navigation scroll/grid restore - per-archive snapshot keys (last 5, was a single slot overwritten by each archive), anchor-based scroll restore keyed to the topmost visible card so late image layout cannot land the offset short, scroll-only retry when the grid HTML exceeds the sessionStorage quota, and a 3s no-grid fallback so a late-mounting Elementor grid never strands the visitor at the top after we take manual scroll control

## [1.24.8] — 2026-07-09

- InfiniteScroll: default URL update on page advance to Disabled - the 1.23.0 flag graduation silently resurrected pushState-by-default, undoing the 1.21.14 clean-URLs fix; /page/N/ back-button entries reload as a single server-rendered page, losing the visitor's scroll position and grid

## [1.24.7] — 2026-07-08

- Search: size the SKU-substring rank boost by term shape so a SKU tail beats FULLTEXT token matches (fixes variation-SKU tail ranking below unrelated products)

## [1.24.6] — 2026-07-08

- Search: rank end/middle-of-SKU matches (infix) near the top so staff can search by the SKU tail

## [1.24.5] — 2026-07-06

- Search mobile palette position: card is now top-anchored at 10 percent of the visible viewport instead of vertically centered, so the field sits at the same height before and after results render (no jump as the list grows)

## [1.24.4] — 2026-07-06

- Search mobile UX: the search palette now floats centered mid-screen on mobile (keyboard-aware via visualViewport) instead of a full-screen top-pinned takeover; scrim + rounded card kept, visible Search button kept

## [1.24.3] — 2026-07-06

- Wave 9.3 gallery - unify desktop with mobile (owner request): the desktop thumbnail row is removed; the gallery is now one swipeable image at a time with the same scroll-progress bar at every breakpoint. Desktop, which has no touch swipe, gets mouse click-drag-to-scroll (grab cursor). The lightbox stays disabled - clicking opens nothing.

## [1.24.2] — 2026-07-06

- Wave 9.3 gallery refinements (owner follow-up): desktop thumbnails now stretch to fill the row (auto-fit) instead of sitting small with empty gaps; clicking a gallery image reliably changes the main image with no lightbox - a capture-phase click handler blocks WC PhotoSwipe / theme / Elementor lightboxes and the raw-image link, and the dead expand trigger is hidden; the mobile swipe progress bar starts with a small resting fill so it reads as an indicator before scrolling.

## [1.24.1] — 2026-07-06

- Wave 9.3 gallery layout correction (owner follow-up): desktop keeps the full-width hero image and flows the remaining images as a thumbnail row (4-5 per row, wrapping) instead of two columns; clicking a thumbnail swaps it into the hero. Mobile is a single swipeable image (scroll-snap carousel) with a progress bar, restoring the strip the 1.24.0 build had replaced with a one-column stack.

## [1.24.0] — 2026-07-06

- Wave 9.3 - PDP live-QA round 2 + perf: gallery image-size root-cause fix (non-main images were dropping to 100px thumbs) + mobile gallery stacked full-width in order, desktop click-to-swap main image, related-grid 3+1/mobile fix, Additional information as a collapsed details tab, coupon notice moved under the buy box as a visible white card, buy-box full-width pin (kills the 50%-left layout + paint flash), title text-wrap fix, and a shared per-request variations memo so the coupon + urgency widgets enumerate variations once per product view.

## [1.23.2] — 2026-07-06

- VariationSwatches: new opt-in "Shop: show name & price only" setting (default off). When on, shop / archive cards hide the whole buy UI — no swatch picker, no add-to-cart, no "Choose options" link — leaving just the product name and price; customers click the card through to the product page to buy. Applies to variable and simple products.
- VariationSwatches: removed the retired WooCommerce → Settings → Products → "Shop swatches" section (it only showed a "settings have moved" notice). Settings live under ShopOS → Variation Swatches; no data affected.

## [1.23.1] — 2026-07-06

- Wave 9.2 — Product Page live-QA follow-up: gallery flexslider-blank fix + lightbox to in-image zoom, VariationSwatches buy box reverted to native with a button-colour setting, inline coupon price, trust + additional information under the buy box, subtle summary panel + 1rem image radii, VS price forced ink, mobile gallery progress bar + sticky-bar space.

## [1.23.0] — 2026-07-06

- Flag graduation sweep - 9 feature flags removed and their surfaces now always-on: Settings import, Sliders advanced controls, Cheapest Variation strategy, Infinite Scroll trigger modes, Restock Notify CSV export, Quick View storefront, and all three Product Page surfaces. Module enable toggles remain the kill-switches.

## [1.22.1] — 2026-07-06

- PDP critique follow-up: editorial gallery (stacked/2-up desktop, scroll-snap strip + dots mobile, no flexslider chrome), VariationSwatches buy-box reconciliation via its own CSS variables + sticky-bar defer guard, trust line under the CTA (settings-editable, empty = hidden), accordion + image-swap motion, sticky-bar price sync, related-grid image sharpness

## [1.22.0] — 2026-07-06

- Wave 9 - ProductPage module: designed single-product template takeover (flag layout), coupon-price notice with live-coupon validation and per-variation price map (flag coupon_notice), low-stock urgency badge with editable strings and inherited font (flag stock_urgency); all three flags default off

## [1.21.40] — 2026-07-05

- Additive tunability filters: facet-cache TTL, search max-results cap, rate-limit defaults, ProductSlider archive thumbnail size, and InfiniteScroll observer rootMargin are now filterable (all additive, no flags)

## [1.21.39] — 2026-07-05

- Comment-only sweep: corrected the stale flag-gated docblocks in Search, ShopFilters and HoverSwap to note the modules are always-on since their graduation versions, and annotated the sanctioned uninstall.php error_log exception

## [1.21.38] — 2026-07-05

- VariationSwatches, ShopFilters and QuickView high z-indexes route through the theme --shopos-ui-z-* tokens with the current literals kept as fallbacks, so theme sites share one z-index ladder while non-theme sites stay byte-identical

## [1.21.37] — 2026-07-05

- VariationSwatches CSS routes swatch typography through the --shopos-ui-font-body design token instead of referencing the Elementor Style Kits sk_type_12 variable directly (keeps the inherit fallback for non-theme installs)

## [1.21.36] — 2026-07-05

- RestockNotify email shell derives lang from get_locale and direction from is_rtl instead of hard-coded Hebrew RTL, and the script-not-loaded JS error routes through the t() locale helper

## [1.21.35] — 2026-07-05

- VariationSwatches swatch-tooltip out-of-stock and unavailable strings resolve through the locale-aware ShopOSVS i18n Labels payload instead of hard-coded Hebrew literals

## [1.21.34] — 2026-07-05

- ShopFilters indexer lighter variation reads (remediation PR-11, audit B2): reindex_product reads per-variation stock via get_available_variations('objects') instead of building the full frontend payload per variation. Parity-preserved (same WC availability filtering, same three fields), no flag.

## [1.21.33] — 2026-07-05

- ShopFilters grid per-page source of truth (remediation PR-12): the filtered-grid page slice and the advisory panel count now follow the WooCommerce shop grid size (loop_shop_per_page) instead of the blog posts_per_page, so stores where the two differ paginate correctly. Byte-identical where they agree; no flag.

## [1.21.32] — 2026-07-05

- Indexer background-churn reduction (remediation PR-10, audit B1+B3): gate ensure_scheduled to admin/cron so the Action Scheduler existence query no longer runs on every storefront pageview, and drop the idle-tick watermark re-park in both reconcile sweeps. Mirrored across the Search + ShopFilters indexers; index output identical, no flag.

## [1.21.31] — 2026-07-05

- ShopFilters facet-build efficiency (remediation PR-9, audit A5+A6+A7): prime term/term-meta caches in one get_terms per taxonomy, merge the price + flag meta_lookup reads into one SELECT, memoize instock resolution per request and swap COUNT(*) for a SELECT 1 LIMIT 1 existence probe. Output byte-identical, no flag.

## [1.21.30] — 2026-07-05

- ShopFilters facet-cache hardening: validate cache-key taxonomies and skip junk states (A2), page-invariant key (A3), debounced rev bumps plus single-flight rebuild lock (A4)

## [1.21.29] — 2026-07-05

- ShopFilters skips the dead native product-search WP_Query on search-results facet builds via a new pre_search_product_ids pre-filter that the Search engine feeds when its index has data

## [1.21.28] — 2026-07-05

- ProductSlider popularity/rating/price orderby reads the sort column from wc_product_meta_lookup in one indexed query instead of priming whole-catalog postmeta every pageview (audit C1); price sorts on canonical min_price

## [1.21.27] — 2026-07-05

- ProductFeed: gate feed success on the tmp-to-final rename via promote_feed(); result-check the size-sidecar and dir-guard writes; fix latent Logger::info/error miscalls to Logger::log on the failure path

## [1.21.26] — 2026-07-05

- CheapestDefaultVariation strategy select: schema key options renamed to choices so the dropdown renders (Settings_Hub reads choices); plus a repo-wide select-schema invariant test

## [1.21.25] — 2026-07-05

- RestockNotify CSV export: neutralize spreadsheet formula injection (leading = + - @ tab CR prefixed with apostrophe) in subscriber-supplied fields

## [1.21.24] — 2026-07-02

- Shop Filters perf: 5-minute facet-response cache keyed by browse state + index revision, invalidated on every index write

## [1.21.23] — 2026-07-02

- ProductSlider grid images: correct sizes attribute so cards download the right srcset candidate instead of the viewport-wide large source

## [1.21.22] — 2026-07-02

- Shop Filters: raw-UTF-8 Hebrew slugs canonicalise to percent-encoded form + decoded-form term-lookup fallback

## [1.21.21] — 2026-07-02

- Shop Filters accuracy fix: constrain Elementor-rendered grids - broadened listing gate + ProductSlider query_args listener with Search composition

## [1.21.20] — 2026-07-02

- Search results grid pagination: page slices instead of one giant grid + engine-driven page count via new grid_max_pages widget hook

## [1.21.19] — 2026-07-02

- Search crash fix: cap engine reads at 500, memoize per-request, lazy has_data gate + server-side dropdown min-chars

## [1.21.18] — 2026-06-30

- ProductSlider grid images: fixed cut-off (object-fit cover to contain so the whole product shows) and blur (loop thumbnail now requests the larger "large" source — up to 1024px — instead of the ~324px woocommerce_thumbnail that upscaled on hi-DPI). Scoped to the widget render; image-height control unchanged.
- Shop Filters (refined style only): the attribute facets (e.g. Size) no longer get pushed out of view when the category tree is expanded — the tree now has its own bounded height + internal scroll inside the height-contained desktop panel. Classic style unchanged.
- Shop Filters (refined style only): the collapsible-facet chevron is smaller and subtler (0.32em, 1.5px stroke, lower opacity).

## [1.21.17] — 2026-06-30

- Refined Shop Filters style: readable ink/paper palette (fixes selected-pill black-on-black on hover) and instant render with no entry fades or flashing leftover shells on select/reload.

## [1.21.16] — 2026-06-30

- Search: every storefront string (field placeholder, search button, no-results / see-all / searching messages, toggle + close accessible names) is now editable in ShopOS → Search settings via the Labels system; blank fields keep the English default.
- Shop Filters: new "Filter panel style" setting. Classic (default) keeps the current checkbox layout unchanged; Refined renders attribute values as pill buttons (selected = filled), collapses each facet behind a chevron header, truncates long lists behind a show-more, uses filled chips with circular remove badges + a circular drawer-close button, and contains the panel height with its own scroll.

## [1.21.15] — 2026-06-30

- QuickView drawer now renders in the site locale (switch_to_locale around the admin-ajax render) so the WooCommerce summary + VariationSwatches buy-box strings (Add to cart / Buy now / Categories / SKU / In stock) match the storefront language instead of falling back to English.
- Card-image gallery arrows scroll the gallery's own viewport instead of via scrollIntoView, so clicking an arrow on a card inside a ProductSlider (e.g. related products in slider mode) no longer scrolls the whole carousel row.

## [1.21.14] — 2026-06-30

- Shared /page/N/ infinite-scroll links reset to page 1 on a fresh load so recipients see the full result set; pages no longer push /page/N/ into the address bar as pages auto-load.

## [1.21.13] — 2026-06-23

- Search: eliminate the flash of the native search bar before JS runs. render_form now emits the icon trigger server-side with the form hidden by a wrapper-scoped rule (search.css loads render-blocking in head), so only the icon ever paints; search.js adopts that trigger instead of creating a duplicate, and a noscript block restores the native bar when JS is off.

## [1.21.12] — 2026-06-23

- Search palette RTL + live-store polish: prices now sit in the result body so they right-align under the title in RTL (no longer float to the opposite edge); the header search field is a rounded white box with a subtle ink-soft focus border (kills the theme grey fill, square corners, and heavy black focus outline that leaked through on the live store); the trigger icon is forced to ink so it stays visible on tinted headers; removed the redundant in-modal search icon; hid the dated native search clear button; and hardened the close + submit button resets with !important so Elementor cannot re-skin them. CSS/JS only.

## [1.21.11] — 2026-06-23

- Search redesign: replace the full-width header bar with a centered command palette (dimmed backdrop, full-screen on mobile with a visible Search button). The header search trigger is now a clean icon-only button with transparent-background resets that survive Elementor theme styling. Result rows redesigned with right-aligned price, ink-muted SKU, hover and keyboard-selected states, and a proper see-all footer; fixes the flexbox min-width overflow that pushed prices off-screen.

## [1.21.10] — 2026-06-23

- Search bar polish: align the live-search panel, overlay and close icon to the design-token layer (colors, shadows, motion, z-scale, radii, type). Fix the cool-gray result-row hover to the warm paper-soft token, replace opacity-based text muting with the ink-muted ramp, unify the close glyph to the search-icon stroke family, and add the missing close-button focus-visible ring.

## [1.21.9] — 2026-06-23

- Fix: the search dropdown image, price and SKU stopped showing after saving the Search settings page. Settings_Hub stores checkboxes as 1/0, but the dropdown payload compared against the 'yes' string, so saved toggles read as off. It now reads them as booleans (matching how the settings UI renders the checked state).

## [1.21.8] — 2026-06-23

- Search dropdown display controls: admin toggles for product image, price and SKU on each result, plus a max-results count (1-20). SKU search fix: a variable product's variation SKUs are now folded into the search index, so searching by a variation SKU finds the product (a reindex is required for existing products).

## [1.21.7] — 2026-06-23

- Search: hide out-of-stock products everywhere (live dropdown, results page, and facet feed). Redesign the search UI to a single icon that expands a full-width bar below the header with live results beneath it; full-screen takeover with a visible Search button on mobile.

## [1.21.6] — 2026-06-23

- Search results: fix the ProductSlider grid showing the whole catalog on an Elementor search-archive template. A product search is no longer treated as a plain archive, so the grid routes through the query path the search-results filter constrains (limit -1, single page). Removes the temporary 1.21.5 diagnostic logging.

## [1.21.5] — 2026-06-22

- TEMP diagnostic build: logs SEARCHDIAG lines (ShopOS to Tools) to trace why the search-results ProductSlider grid is not constrained to engine matches - no behavior change, to be reverted in the fix release

## [1.21.4] — 2026-06-22

- Search: constrain the search-results grid when the Elementor archive ProductSlider uses the All-products source (grid mode), not only Current query - fixes the storefront search showing the whole catalog

## [1.21.3] — 2026-06-22

- Search 8.3c: read the search term from the request, not the main query. On an Elementor product-archive search page the main query carries post_type=product but not the s query var, so the engine never constrained the grid (all products + pagination) while ShopFilters facets were correct. apply() now triggers on a product main query with a request search term and constrains it, fixing grid content and pagination.

## [1.21.2] — 2026-06-22

- Search 8.3b fix: constrain the ShopOS ProductSlider search grid via its query_args filter (the previous the_posts net regressed to zero products because the widget renders its own query); read the term from wp_the_query to survive Elementor swap; inject engine ids via wc_get_products include. Also lower the index debounce 30s to 10s for near-live freshness.

## [1.21.1] — 2026-06-22

- Search 8.3b: results-grid enforcement via the_posts net. Fixes the grid showing more products than the engine matched when the search results grid is rendered by a query that bypasses the main query (Elementor query-swap) — order_posts_by_ids filters + reorders the final product list to the engine ids, mirroring Shop Filters' AWS enforcement.

## [1.21.0] — 2026-06-22

- Search Wave 8.4 graduation: module is now always-on (three search feature flags removed) since Advanced Woo Search was deactivated and the engine is live. The originally planned parity-tooling/cutover was obsoleted. Module-enable toggle is the single kill-switch; an unbuilt index degrades safely. Wave 8 epic complete.

## [1.20.1] — 2026-06-22

- Search 8.3a live-QA fixes: clamp search(term, -1) so the results page + facet feed no longer build invalid LIMIT -1 (was a blank grid + empty facets once AWS was deactivated); remove the field_selector setting (shortcode-only) in favour of a hardcoded selector the [shopos_search] field matches.

## [1.20.0] — 2026-06-22

- Search Wave 8.3: engine-driven product search results page. Results_Query takes over the native search grid (pre_get_posts: relevance-ordered post__in + posts_search neutralisation), falls back to native WP search when the index is empty, and coexists with Shop Filters facets (intersect-and-preserve-order) via the additive shopos_core/shop_filters/search_product_ids filter. Behind shopos_core_search_results_enabled (default off).

## [1.19.1] — 2026-06-22

- Search 8.2a: add [shopos_search] shortcode — a standalone product search box (native GET form, auto-enhanced by the dropdown JS via the default selector; JS-off runs a normal product search). Gated by the existing dropdown flag.

## [1.19.0] — 2026-06-22

- Search Wave 8.2: live AJAX search dropdown — ranked MATCH...AGAINST read query (FULLTEXT + title boost + exact/prefix-SKU + LIKE fallback for short/non-Latin tokens), public shopos_core_search_query endpoint, debounced combobox dropdown (image/title/price/link) with combobox a11y + progressive enhancement, behind shopos_core_search_dropdown_enabled (default off).

## [1.18.0] — 2026-06-22

- Search Wave 8.1 foundation: in-house full-text product search index (shopos_search_index FULLTEXT table) + dirty-queue indexer + Reindex all admin tool, behind shopos_core_search_indexer_enabled (default off). Staged to replace Advanced Woo Search.

## [1.17.5] — 2026-06-21

- Mobile: variation swatches now select on the first tap. The buy-box and shop-grid swatches revealed a tooltip / scaled on `:hover`, which made iOS treat the first tap as a hover (selection needed a second tap). Those hover-reveal rules are now gated behind `@media (hover: hover)`, matching the HoverSwap / ProductSlider pattern. Mouse users are unchanged; the swatch name stays available to assistive tech via `aria-label` / screen-reader text.

## [1.17.4] — 2026-06-19

- Mobile: tapping a product card now opens it on the first tap. A touch handler navigates on a clean tap of a card link, bypassing the iOS "first tap = hover" delay that an external (page-builder) hover style was causing. Swatches, quick-view, and add-to-cart taps are unaffected.
- Back button: returning to a shop/category/archive now lands at the exact spot you left, with all infinite-scroll-loaded products restored. The grid and scroll position are snapshotted on leave and replayed on back/forward navigation (degrades silently if storage is unavailable).
- Mobile filters: the filter drawer now finishes sliding closed before the filtered page loads, removing the brief "buggy" half-state on Apply/Clear. Desktop and Reduce-Motion behaviour is unchanged.

## [1.17.3] — 2026-06-19

- Card slider (product cards + Quick View drawer): the loop wrap is now smooth. The first/last slides are cloned onto the opposite ends, so stepping past an edge animates one slide like any other transition and then silently snaps to the real twin once scrolling settles (covers arrows and native swipe). RTL-correct and page-safe (never scrolls an off-screen card into view on load).

## [1.17.2] — 2026-06-19

- QuickView drawer gallery now reuses the HoverSwap card-slider component verbatim (identical arrows, CSS, and scroll-snap + drag + loop logic) instead of a parallel implementation, so the in-drawer arrows look and behave exactly like the ones on product cards. Card-slider navigation loops again: a normal step animates smoothly and the wrap-around step (last to first / first to last) jumps instantly, so it no longer scrolls the whole strip back.

## [1.17.1] — 2026-06-19

- QuickView gallery arrows restyled to match the ProductSlider card-slider arrows (small white circles, hover-reveal, RTL-correct chevron). Both galleries now clamp at the ends instead of wrapping, removing the jarring scroll-back-to-start. Card-slider product images now get a 1rem border-radius (applied to the .shopos-card-slider container).

## [1.17.0] — 2026-06-19

- ProductSlider: price typography control, 1rem card image radius, subtle resting card border. QuickView: wider drawer, smaller locale-aware price, fade/scale open-close animation, gallery prev/next arrows. VariationSwatches: buy-box strings (add to cart, buy now, out of stock, etc.) now follow the site locale (Hebrew/English) via a new Labels resolver.

## [1.16.2] — 2026-06-19

- Gallery slider: remove the buggy autoplay and remove position dots entirely. The card slider now keeps swipe/drag plus optional hover arrows only; the slider_autoplay and slider_dots settings are gone.

## [1.16.1] — 2026-06-18

- Fix the Card image mode dropdown rendering empty (Settings_Hub renders select options from the choices key, not options) and simplify activation: delete the two hover_swap feature flags, route the module entirely on the Card image mode setting (default none). Activation is now just enable the module plus pick the mode.

## [1.16.0] — 2026-06-18

- Add gallery-slider mode to the Card Image Effects module (formerly Hover Image Swap): a new Card image mode setting picks None, Hover swap, or Gallery slider. Gallery slider replaces the card image with a small swipeable scroll-snap slider of all product images, with independently toggleable arrows, dots and autoplay. Gated by the default-off shopos_core_hover_swap_gallery_slider_enabled flag.

## [1.15.0] — 2026-06-18

- Add Hover Image Swap module: on shop/archive cards, hovering a product cross-fades the main image to its second gallery image (pure CSS, no-op without a gallery image, mobile shows primary only). Default-off feature flag shopos_core_hover_swap_frontend_enabled.

## [1.14.4] — 2026-06-18

- ProductSlider grid - pin InfiniteScroll skeleton cards to the full card height (image plus the title/price/cart stack) so loading placeholders match real cards instead of coming up short

## [1.14.3] — 2026-06-18

- ProductSlider grid fixes - remove WooCommerce ul.products clearfix pseudo-elements that became grid items (first row showed one product short), omit the empty header so its border line no longer shows without a heading, and match InfiniteScroll skeleton-card height to the card so loading placeholders are not tiny

## [1.14.2] — 2026-06-18

- ProductSlider - fix grid mode capping at 24 with no pagination on Elementor archive templates; read the canonical main query (wp_the_query) instead of the swapped global wp_query so archive detection, posts and pagination survive Elementor swapping the query

## [1.14.1] — 2026-06-18

- Quick View - fix half-width drawer, restyle trigger as a magnifying glass and the close button, and raise CSS specificity to override Elementor and WooCommerce styles on archive cards

## [1.14.0] — 2026-06-18

- ProductSlider grid mode acts as archive products grid (current-query source renders full page + pagination, cap raised to 48); Quick View drawer summary now full-width; Quick View icon restyled (black glyph on white circle, right corner, hover pop only)

## [1.13.0] — 2026-06-11

- New QuickView module (Wave 7.2): per-card quick-view icon opening an inline-end slide-in drawer (image, price, short description, add-to-cart with VariationSwatches buy-box, meta, product-page link) behind shopos_core_quick_view_frontend_enabled (default off); additive quick-view re-init listener in VariationSwatches JS

## [1.12.31] — 2026-06-11

- ProductSlider grid mode: round fractional mobile cards-per-view to a whole column count — repeat() rejects fractions, collapsing the mobile grid to one column (grid parity audit G1)

## [1.12.30] — 2026-06-09

- Shop Filters + VariationSwatches frontend fixes (consolidated onto the 1.12.26 graduation): mobile Apply closes the drawer instantly (no frozen-panel jank); app-feel motion — drawer-content cascade, checkbox-tick feedback, amplified press and chip entrance, all reduced-motion aware; and WPC Product Image Swap compatibility so tapping a swatch on the shop grid no longer makes the card image jump. Replaces local 1.12.27-1.12.29 QA builds.

## [1.12.26] — 2026-06-08

- **Shop Filters graduated to always-on.** The module (background index, storefront filter panel, filtered-URL SEO policy, and the facet-configuration "filter builder") now runs by default whenever the module is enabled. All four `shop_filters` feature flags — `indexer`, `frontend`, `seo_policy`, `admin_config` — and their entries on ShopOS → Feature Flags have been removed, along with the redundant **Background indexing** toggle on the ShopOS → Shop Filters page.
- Removed the **Index diagnostic** table from the ShopOS → Shop Filters admin page.
- **No option-based rollback:** there is no flag to switch off; disable the whole module from the modules registry, or revert the release. (Pre-existing `shopos_core_shop_filters_*_enabled` option rows become inert and are left in place — not deleted.)

## [1.12.25] — 2026-06-08

- Shop Filters: preserve percent-encoded non-Latin (Hebrew) attribute term slugs in URL parsing so faceted filtering no longer returns a blank product grid

## [1.12.24] — 2026-05-28

- RestockNotify privacy fix: register the WP privacy exporter/eraser at core boot for every module (new Module_Base::register_persistent_hooks seam) so persisted subscriber PII is still covered by GDPR export/erase when the module is disabled or WooCommerce is absent. Previously registered only inside Module::boot (enabled-only). No flag (platform-contract bugfix), no schema change.

## [1.12.23] — 2026-05-20

- Shop Filters 6.5c on-sale/in-stock facets: On sale (and In stock when the store shows out-of-stock items) checkboxes in the panel, read from wc_product_meta_lookup, filtering the grid via onsale/in_stock URL params. Reuses the storefront flag.

## [1.12.22] — 2026-05-20

- Shop Filters 6.5a SEO policy: filtered shop/category/search URLs get noindex,follow plus a canonical to the clean archive, routed through RankMath/SEOPress/Yoast or core. New flag shopos_core_shop_filters_seo_policy_enabled (default off).

## [1.12.21] — 2026-05-20

- Shop Filters 6.4 admin facet config: per-attribute matrix (show/order/hide-on-categories) on ShopOS to Shop Filters, gated by the new shopos_core_shop_filters_admin_config_enabled flag (default off; off reverts to auto-derived defaults).

## [1.12.20] — 2026-05-20

- Shop Filters: run the search-results filter enforcement last (priority 99999) so it wins against Advanced Woo Search re-asserting its own result list after us

## [1.12.19] — 2026-05-20

- Shop Filters fix: the mobile Filter toggle button no longer appears on desktop (the theme .shopos-ui-btn display was overriding the hide rule)

## [1.12.18] — 2026-05-20

- Shop Filters: enforce the active filters on search-results grids supplied by a search plugin (Advanced Woo Search) via a the_posts safety net, so a product whose only in-stock size is outside the selection no longer slips through on search

## [1.12.17] — 2026-05-20

- Shop Filters now scope to search results: on a product-search page the panel facets, counts, category tree and price bands reflect the products matching the query (was showing whole-catalogue facets); resolves the 6.3a.1 search-facet deferral

## [1.12.16] — 2026-05-20

- Shop Filters buttons now match the theme: the panel toggle, Apply, Clear and sort controls carry the themes own .shopos-ui-btn / .shopos-ui-select primitives so they render identically to the sites buttons (the 1.12.15 core styles were being overridden by the theme/Elementor); core styles remain as the no-theme fallback

## [1.12.15] — 2026-05-20

- Shop Filters UI polish: filter panel buttons (mobile toggle, chips, clear, apply, sort) are now more minimal and rounded, pulling the themes --shopos-ui-* radius/weight/tracking design tokens with fallbacks

## [1.12.14] — 2026-05-20

- Shop Filters bug-fix: filtering an attribute (e.g. a size) now returns only products that have that value IN STOCK, matching the index per-variation truth; previously the grid matched WooCommerces parent-assigned terms and showed products whose selected size was sold out

## [1.12.13] — 2026-05-20

- Shop Filters 6.5b price + sort (built ahead of 6.4 / 6.5a, so it takes the next sequential version): the panel gains a price facet and a sort dropdown, both reusing the frontend_enabled flag. Price renders as OR-checkbox bands at the top of the panel; bands come from a new price_bands setting (comma-separated upper bounds) or, when blank, are auto-derived (rounded to 1/2/5 decades) from the catalogue's price range in context. Band membership + counts read min/max from wc_product_meta_lookup (decisions §5.2 — price is never duplicated in our index); the storefront grid applies the selected bands via a posts_clauses join (price range overlap, OR within the facet). Bands carry a dedicated filter_price=min-max,min- URL param kept out of the taxonomy machinery. A Sort by dropdown sets ?orderby (WooCommerce honours it on reload); a new default_sort setting applies a site-wide default ordering via woocommerce_default_catalog_orderby until the shopper picks a sort. Known v1 approximation: term-facet counts don't subtract an active price selection (price lives outside the term engine); the grid count and band counts stay correct. Price heading + Sort heading labels are editable via the 6.3c labels editor.

## [1.12.12] — 2026-05-20

- Shop Filters 6.3c: storefront string labels are now editable from the Shop Filters settings page (blank = English default), so the panel wording can be set to Hebrew without code

## [1.12.11] — 2026-05-20

- Shop Filters 6.3b Facet UI: the deferred storefront UI from 6.3a. The product_cat facet now renders on top of the panel as a pruned, count-rolled-up parent-to-child category hierarchy (Category_Tree wired into Query_Builder); each node links to that category's own archive (navigation, not a filter param). Attribute facets whose terms carry VariationSwatches term meta render as colour/image swatches (read directly via get_term_meta with the shopos_swatch_color / shopos_core_variation_swatches_term_image_id keys — no call into the VariationSwatches module, per decisions 5.7); the underlying checkbox is kept so the reload transport and selection logic are unchanged. New stylesheet (enqueued with SHOPOS_CORE_VERSION) styles the panel, removes the default list bullets, and adds a mobile drawer: a full-width toggle opens a bottom-sheet (focus-trapped, ESC/overlay to close, prefers-reduced-motion respected) where ticking defers — selection is collected and only Apply navigates once, with a Clear button. New render hooks shopos_core/shop_filters/before_render + after_render (actions) and panel_html (filter; the grid_html the original plan pencilled in has no grid to filter under the 6.3a.1 reload transport). Reuses the shopos_core_shop_filters_frontend_enabled flag — no new flag.

## [1.12.10] — 2026-05-20

- Shop Filters 6.3a.4 hyphen + pagination fix: filtering by a hyphenated attribute (pa_shoe-size, pa_clothing-size) returned no products. Url_State sanitize_taxonomy stripped hyphens, mangling the taxonomy (pa_shoe-size to pa_shoesize) so the tax_query matched nothing — every size filter came back blank. Hyphens are now allowed in attribute taxonomies. Also: applying a filter from a /page/N/ URL kept the pretty-pagination path and could 404 when the filtered result set had fewer pages; the front-end controller now resets the /page/N/ path segment to page 1, alongside the existing paged query-param reset.

## [1.12.9] — 2026-05-20

- Shop Filters 6.3a.3 index diagnostic: a read-only table on the ShopOS Shop Filters admin page (gated by the existing indexer flag, alongside the reindex tool) listing, per attribute term, the slug, name and indexed product / in-stock counts straight from the index. Built to debug storefront-data problems where a facet value shows a count but the filtered URL returns nothing, or picking one size returns another — surfacing scrambled term name/slug pairs, terms present in the index that no longer resolve to a live term, and values whose only products are out of stock. Filters match by slug, so the slug column is highlighted as the source of truth. Read-only, no writes, no storefront effect.

## [1.12.8] — 2026-05-20

- Shop Filters 6.3a.2 stock-visibility parity: facet counts now mirror the storefront grid. When the store hides out-of-stock items (woocommerce_hide_out_of_stock_items), the facet base universe and counts exclude out-of-stock products, so a value backed only by a hidden out-of-stock product no longer shows a phantom count while the filtered grid is empty. Attribute values are counted by product-level presence within the in-stock base, matching how WooCommerce matches a product to a term.

## [1.12.7] — 2026-05-20

- Shop Filters 6.3a.1 read-path fix: storefront filters now actually filter the grid. New Query bridge applies the URL filter selection (filter_pa_*) to the main WooCommerce product query via woocommerce_product_query_tax_query (shop / category / attribute archives, preserving product_visibility) plus a scoped pre_get_posts for product search. The front-end controller now navigates (full reload) instead of swapping via AJAX, so the selection persists in the URL, sort keeps the filters, and product-search pages work; Infinite Scroll runs normally on the reloaded page. Active-filter chips are server-rendered. The public AJAX endpoint is retained but no longer called by the bundled JS. Checkbox facets only; reuses the shopos_core_shop_filters_frontend_enabled flag.

## [1.12.6] — 2026-05-20

- Shop Filters 6.3a: storefront read path. Query_Builder glues the index to the facet engine and shapes the AJAX response; the shopos_shop_filters shortcode server-renders the initial facet tree and enqueues the front-end controller; a public admin-AJAX endpoint (shopos_core_shop_filters_query, nonce + rate-limited) recomputes facets and counts. JS swaps the product grid by fetching the filtered front-end URL so Elementor card markup and Infinite Scroll coexistence are preserved. Checkbox facets only; gated by the new default-off shopos_core_shop_filters_frontend_enabled flag.

## [1.12.5] — 2026-05-20

- Shop Filters QA polish: status line shows actual last-refresh time (not the internal watermark); full reindex parks the watermark so the sweep does not re-churn; catch-up chain via Action Scheduler; DISABLE_WP_CRON note on the page.

## [1.12.4] — 2026-05-20

- Shop Filters bug-fix found in QA: the recurring reconcile sweep now schedules correctly. ensure_scheduled was running on plugins_loaded before Action Scheduler is ready (silent no-op); deferred to init. Event-driven on-save indexing was unaffected.

## [1.12.3] — 2026-05-20

- Shop Filters admin control surface (by request): ShopOS -> Shop Filters page now has the background-indexing toggle, live index status (products/rows/last sweep/scheduled) and the reindex tool, all manageable from wp-admin without WP-CLI. Toggle writes the same option the feature flag reads.

## [1.12.2] — 2026-05-20

- Shop Filters 6.1 indexer bug-fix: non-variation global attributes on variable products now follow overall stock (were wrongly variation-gated, hiding them under in-stock-only filtering). Extracted Term_Helpers::resolve_in_stock + unit test; hardened ensure_scheduled Action Scheduler check.

## [1.12.1] — 2026-05-20

- Shop Filters facet engine - Wave 6 Phase 6.2: pure Facet_Engine, Category_Tree, Url_State, Facet_Config (AND/OR with self-exclusion, hide-zero, category hierarchy, URL state). No flag, no storefront output yet.

## [1.12.0] — 2026-05-20

- Shop Filters new module - Wave 6 Phase 6.1 foundation: index table, background indexer, admin reindex tool. Behind shopos_core_shop_filters_indexer_enabled flag default off; module disabled by default. Storefront UI ships in later phases.

## [1.11.51] — 2026-05-13

- VariationSwatches: respect wp_terms.term_order column for swatch ordering (honors Custom Taxonomy Order plugin et al). Falls back to wc_get_product_terms when the column is absent.

## [1.11.50] — 2026-05-13

- VariationSwatches: reorder swatch options to match taxonomy term order (matches WC native dropdown). PDP buy-box + shop archive picker.

## [1.11.49] — 2026-05-11

- VariationSwatches: the buy box, the shop / archive picker, the PDP price line and the toast notifications now read the kit body font from Elementor's `--e-global-typography-sk_type_12-font-family` variable (with `inherit` as fallback) instead of bare `font-family: inherit`. Bare `inherit` picked up the *wrapping element's* font when the buy box / picker is AJAX-injected into a foreign-styled container — e.g. WooSQ (Woo Smart Quick View)'s `.woosq-sidebar { font-family: "Open Sans", … }` — so the quick-view buy box wasn't matching the site's Style Kits typeface. A CSS custom property cascades through such wrappers untouched, so the kit font reaches it regardless. No regression: without Style Kits the `inherit` fallback applies as before. CSS-only.

## [1.11.48] — 2026-05-11

- VariationSwatches: the shop / archive variation-picker now `font-family: inherit !important` instead of forcing the built-in `--eshop-font` ("Ploni"-first) stack — matches the 1.11.47 buy-box change, so the picker reads in the page typeface (whatever Style Kits / Elementor global typography or the theme sets). Also drops a stale `var(--shopos-font, inherit)` reference in the picker's toast. CSS-only; no behaviour change beyond the typeface.

## [1.11.47] — 2026-05-11

- VariationSwatches: the simple-product buy box now renders its own `.shopos-pdp-price` line (same markup as the variable buy box). Fixes the missing price in simple-product quick-view modals — the WooSQ duplicate-price suppression was hiding WooCommerce's separately-rendered `<p class="price">`, which the simple buy box previously had nothing to replace. `maybe_suppress_pdp_price()` now also unhooks `woocommerce_template_single_price` for simple products so a plain simple PDP isn't doubled; on Elementor-built simple PDPs the 1.11.46 `:has()` rule consequently hides the Elementor "Product Price" widget there too. On a default-template simple PDP the price moves to just above the add-to-cart button (where the buy box renders), matching the variable buy box.
- VariationSwatches: the buy box (and toast) now inherit the site/theme font instead of forcing a built-in "Ploni"-first stack — the buy box reads in the same typeface as the rest of the page.
- VariationSwatches: the shop / archive variation-picker swatch row is now centred within the card; the single-product buy box's swatch row is back to its original right-anchored layout (the 1.11.46 centring there was reverted).

## [1.11.46] — 2026-05-11

- VariationSwatches: hide the Elementor "Product Price" widget on variable product pages when the buy box's own (live, variation-aware) price line is present — fixes the duplicated price on Elementor-built PDPs. Simple products keep the Elementor widget's price untouched. CSS-only; falls back to current behaviour where :has() is unsupported.
- VariationSwatches: centre the variation swatch row (size pills / colour circles / image chips) instead of right-anchoring it; RTL order is unchanged. The attribute heading above stays right-aligned.

## [1.11.45] — 2026-05-11

- Wave 2.2/4g: VariationSwatches settings moved to ShopOS -> Variation Swatches page (sole editing surface); settings_hub flag retired; legacy WooCommerce Products section soft-deprecated to a moved-notice; re-sync migration for flag-off sites

## [1.11.44] — 2026-05-11

- Add ShopOS -> Feature Flags admin page (checkbox per flag, grouped by module, with descriptions); Feature_Flags::registry/option_name/is_forced_by_filter helpers

## [1.11.43] — 2026-05-11

- Persist onboarding-notice dismissal so the nudge stays gone after a page reload

## [1.11.42] — 2026-05-11

- ProductSlider — popularity / rating / price orderby now actually sorts and includes products without total_sales / _wc_average_rating / _price meta (bypasses WC's INNER JOIN on the sort meta key, which silently excluded products with no recorded sales / reviews / price). Other orderby values (date, title, menu_order, rand) and the manual / current_query / related sources unchanged. Defensive 5000-ID cap on the in-PHP sort.

## [1.11.41] — 2026-05-11

- Bug-fix bundle (shop archive picker): variation pill gap 6px to 8px and internal padding 12px to 16px; refreshOverflow sweeps rawChips past +N boundary (not just in-stock); render_loop_price_or_skip now skips for simple products too (was variable only, leaving duplicated WC + picker prices on archive); honeypot inputs in shop-simple-pick and shop-variation-pick swapped from left:-9999px hack to WCAG clip:rect pattern so absolute-positioned cards do not inflate slider scrollWidth past last product.

## [1.11.40] — 2026-05-11

- Wave 4.5: VariationSwatches WPC Bundles + FBT compatibility (default-off flag, JS field-forwarding to WC AJAX, single-line legacy template hook for plugin injection)

## [1.11.39] — 2026-05-11

- Wave 4.2 — CategorySlider design tokens exposed as Elementor controls. Adds 4 color controls (--cs-bg, --cs-ink, --cs-mute, --cs-line) and 3 arrow controls (size, radius, duration) on the CategorySlider widget Style tab. Colors default empty so Elementor omits the selector and the existing .cs block oklch() declarations remain. Arrow controls default to the prior hardcoded values (40px / 50% / 180ms); CSS file consumes them via var(--cs-arrow-X, fallback). No flag — purely additive, byte-identical out-of-the-box render.

## [1.11.38] — 2026-05-11

- Wave 4.1b — RestockNotify CSV export admin button. Adds an Export Subscribers submenu under the legacy restock-notify parent menu and an admin-post.php handler that streams the shopos_restock_subscribers table as a UTF-8 BOM CSV with all 9 columns and a date-stamped filename. Gated behind shopos_core_restock_notify_csv_export_enabled (default off). Defense-in-depth: flag-OFF means neither the submenu nor the admin_post listener attaches. Closes Wave 4.1.

## [1.11.37] — 2026-05-11

- Wave 4.1a — RestockNotify WP_Privacy exporter + eraser. Registers wp_privacy_personal_data_exporters and wp_privacy_personal_data_erasers under the shopos-core-restock-notify key so a privacy admin can export or erase a customer's restock-notify subscriptions through WP Tools. Eraser nulls customer_name/customer_email (empty string; columns are NOT NULL) and flips status to unsubscribed; the row stays as audit trail. Unconditional — privacy hooks are a platform contract, not flag-gated. Wave 4.1b will add the CSV admin button behind a flag.

## [1.11.36] — 2026-05-11

- Wave 4.3 — InfiniteScroll skeleton/fade tokens exposed as 5 settings (shimmer base/highlight color, shimmer duration ms, fade duration ms, fade transform px). Emitted at runtime as --shopos-ui-is-* CSS custom properties on :root via wp_add_inline_style. Defaults map byte-identically to the prior hardcoded CSS values; flag-OFF / no-settings-saved is back-compat. Additive — no flag.

## [1.11.35] — 2026-05-10

- Wave 3.1b — InfiniteScroll PHP wrapper render path + 4 deferred extension hooks (selector filter, before_render and after_render actions, should_render_wrapper filter) + container_selector setting + JS-side selector override. Flag-gated, default off.

## [1.11.33] — 2026-05-04

- Wave 3.1a - InfiniteScroll trigger_mode / history_mode / hybrid_threshold settings + JS dispatcher (gates at attachObserver entry + post-loadNext threshold check) + applyHistoryMode wrapper around the existing pushState call. No new hooks (those land in 3.1b). Behind shopos_core_infinite_scroll_trigger_modes_enabled (default off). Flag-OFF and flag-ON-with-defaults both byte-identical to pre-3.1a behavior.

## [1.11.32] — 2026-05-04

- Wave 3.3 - CheapestDefaultVariation strategy selector (cheapest / first_in_stock) with per-product _shopos_cheapest_variation_strategy meta override and shopos_core/cheapest_variation/strategy filter. Behind shopos_core_cheapest_variation_strategy_enabled (default off).

## [1.11.31] — 2026-05-04

- ProductSlider native-scroll over-shoot -- clamp touch swipe + trackpad pan at last card via scroll-event snap-back (scoped to ProductSlider via data-cs-clamp-children opt-in; supersedes #18 which only covered JS-driven scroll)

## [1.11.30] — 2026-05-04

- Wave 3.2b -- ProductSlider autoplay / loop / indicator (advanced controls behind shopos_core_sliders_advanced_controls_enabled, default off; reuses 3.2a flag; grid mode unchanged)

## [1.11.29] — 2026-05-03

- Wave 3.2a — CategorySlider autoplay / loop / indicator (Roadmap #6, sub-PR 1 of 2). New Elementor controls gated on `shopos_core_sliders_advanced_controls_enabled` (default off): Autoplay, Autoplay delay (clamped 1000–15000 ms), Loop (autoplay-wrap only), Indicator (`progress` / `dots` / `none`). Legacy `show_progress` switcher preserved as a back-compat alias — pre-existing widgets fall through to the legacy value when `indicator` is unset. Render path also gated on flag, so rollback is byte-identical. Roadmap's `loading="lazy"` line is a no-op for CategorySlider (CSS background-image, not `<img>`); real bg lazy is a separate future item. Wave 3.2b (ProductSlider) queued.

## [1.11.28] — 2026-05-03

- Wave 2.2 / 4e — auto-color render wiring (Color_Sampler::resolve_term_color wraps manual->sampled->fallback chain at archive.php and variation-buy-box.php render callsites; flag-OFF byte-identical; closes Wave 2.2)

## [1.11.27] — 2026-05-03

- Wave 2.2 / 4d — auto-color sampler pipeline (Color_Sampler with modal-with-edge-filter, GD/Imagick auto-upgrade) + Sampler_Scheduler (sample-on-save, pre-warm on flag-flip via batched WP-Cron, first-of-kind cron precedent, queue + filterable batch size). Cache invalidation on _thumbnail_id change, variation deletion, attachment deletion. Bootstrap.php cron stubs promoted (Wave 2.2 ceiling raised 12 to 14)

## [1.11.26] — 2026-05-03

- Bugfix: Settings_Hub storage-shape mismatch in 4a's read-shim — Settings_Reader returned 1/0/string verbatim from new keys, breaking ShopOS_VS_Settings::bool yes/no comparisons and excluded_category_ids array shape on flag-ON sites. Adds normalize_new_value_for_legacy_reader path and fixes Settings_Hub render_field checkbox checked() call to FILTER_VALIDATE_BOOLEAN

## [1.11.25] — 2026-05-03

- Wave 2.2 / 4c — Hover tooltip on swatches: pure CSS via data-tooltip attr + hover::after, per-term admin override via shopos_core_variation_swatches_term_tooltip_text term-meta, gated on shopos_core_variation_swatches_tooltip_enabled flag, flag folded into transient cache key

## [1.11.24] — 2026-05-03

- Wave 2.2 / 4b — Image swatches: per-term image upload (Iconic/WPC pattern) gated on shopos_core_variation_swatches_image_swatches_enabled, shopos_core_variation_swatches_term_image_id term-meta key under the canonical namespace (not extending legacy shopos_*), image wins over color precedence, term_image_url filter, smart test stubs promoted to bootstrap.php

## [1.11.23] — 2026-05-03

- Wave 2.2 / 4f — Variation-image-on-card swap on shop / archive listings: per-variation image payload (gated on shopos_core_variation_swatches_card_image_swap_enabled), refreshCardImage() in shopos-shop-swatches.js, two new additive filters (card_image_selector and card_image_payload), flag-state folded into transient signature for implicit cache-bust on flag flip

## [1.11.22] — 2026-05-03

- Drop PHP 7.4 from CI matrix and bump min PHP to 8.0 (shopos-core + shopos-theme headers, composer.json require, .github/workflows/ci.yml). Aligns CI to reality after Wave 2.3a-c baked PHP 8.0+ idioms (str_starts_with, str_contains) into shipped code; PHP 7.4 PHPUnit lane was de-facto failing.

## [1.11.21] — 2026-05-03

- Wave 2.2 / 4a — VariationSwatches settings migration to Settings_Hub: read-shim, 1.11.21 one-shot migration of 14 shopos_vs_* keys, new admin page gated behind shopos_core_variation_swatches_settings_hub_enabled flag (default off, P1 version-skew model)

## [1.11.20] — 2026-05-03

- Fix PHP 7.4/8.0 lint failure in SnapshotTestCaseTest: replace PHP 8.1+ octal literal 0o755 with legacy 0755

## [1.11.19] — 2026-04-30

- Tweak: narrow ProductSlider/CategorySlider edge-fade mask from 24px to 4px on mobile (<=640px) so cards do not lose visible content to the softener

## [1.11.18] — 2026-04-30

- Bugfix: ProductSlider drag overshoot — clamp drag bounds at last card edge instead of scrollWidth (RTL-safe via getBoundingClientRect)

## [1.11.6] — 2026-04-29

- Bug fix: cap shop variation-pill width so long option names cannot stretch the product card column and break the archive grid (RTL: pushed cards off-screen left).

## [1.11.5] — 2026-04-29

- Wave 2.3c: modern Frontend via class_alias swap; Hebrew JS strings + form placeholders moved to locales/; shopos_core/restock_notify/should_inject filter

## [1.11.4] — 2026-04-29

- Wave 2.3b: modern Email + Stock_Monitor classes via class_alias swap; bilingual email shell fix; shopos_core/restock_notify/email_args filter and before_send action

## [1.11.3] — 2026-04-29

- Wave 2.3a: add Subscribers repository - thin static wrapper around ShopOS_Restock_Database with 4 methods, no callers yet, groundwork for 2.3b/c

## [1.11.2] — 2026-04-29

- Wave 1.2: RestockNotify locale bootstrapper - English defaults plus Hebrew opt-in via locales/en_US.php and he_IL.php

## [1.11.1] — 2026-04-29

- Wave 1.1b: add category_slider/query_args, category_slider/render_card, product_slider/query_args, product_feed/query_args, product_feed/item, product_feed/before_serve, product_feed/after_generate

## [1.11.0] — 2026-04-29

- Wave 1.1a: add cheapest_variation/should_apply, cheapest_variation/chosen, variable_stock_fix/should_check filters

## [1.10.17] — 2026-04-29

- Add snapshot harness (Wave 0.5): SnapshotTestCase trait, Scrubber utility, and three example tests (HTML, XML, JSON) with committed goldens

## [1.10.16] — 2026-04-29

- Wave 0.4: regression baselines (hooks, REST, CLI, shopos_/shopos_ identifiers) + tools/capture-baselines.sh + BaselinesIntegrityTest

## [1.10.15] — 2026-04-29

- Wave 0.3 - Settings export/import tool with auto-backup, halt-on-error import, and last-5 rolling backups

## [1.10.14] — 2026-04-28

- Wave 0.2 - Feature_Flags helper with explicit boolean parsing and dynamic filter hook

## [1.10.13] — 2026-04-28

- Wave 0.1: add shopos_core/logger/entry filter and shopos_core/logger/written action inside Logger::log() (D8)

## [1.10.12] — 2026-04-27

- Variation +N badge: add .shopos-shop-pick__more[hidden] display:none !important rule to fix the root cause that made the badge stay visible whenever JS tried to hide it. The class rule .shopos-shop-pick__more had display:inline-flex !important which silently overrode the browser user-agent [hidden] display:none, so moreBtn.hidden=true from refreshOverflow was a no-op. Result: badge visible with zero chips marked .is-overflow so clicking did nothing. Mirrors the existing .shopos-shop-pick__opt[hidden] rule that already worked for chips.

## [1.10.11] — 2026-04-27

- Variation +N badge: hide out-of-stock and unavailable chips entirely on shop archive instead of greying them out, so the visible row matches the JS in-stock filter (the badge now counts only what the customer can actually pick, and is hidden completely when no in-stock chip is hidden). PHP-level OPT_SHOP_HIDE_OOS handles attribute-value-level pruning (default ON); this CSS handles the cross-attribute case where a chip becomes OOS only after the customer picks another swatch and refreshAvailability marks it.

## [1.10.10] — 2026-04-27

- MyAccount: align Addresses and View Order address columns under one shared grid rule, unify card chrome across both pages so widths and gutters match identically. Tokenize raw font-weight, line-height, letter-spacing, and font-size values: every weight goes through fma-weight-medium/semi, every line-height through fma-leading-snug/base/tight, every letter-spacing through fma-tracking-tight/wide, hero size through fma-text-xxl. Mobile breakpoint already covered both selectors so no behavior change there, just visual lockstep.

## [1.10.9] — 2026-04-27

- Variation +N badge: filter to in-stock chips only when measuring overflow, marking chips as overflow, and computing the count. Out-of-stock and unavailable chips (set by refreshAvailability via .is-out-of-stock and .is-unavailable classes) are excluded so the badge never points to a sold-out variant. If zero in-stock chips are hidden, the badge stays hidden completely. Belt-and-braces fallback: hiddenCount === 0 forces the badge hidden even under pathological measurement.

## [1.10.8] — 2026-04-27

- MyAccount mobile nav: add min-width:0 to grid items + ul to fix horizontal-scroll trap (the pill row's flex-nowrap min-content was wider than the phone, growing the grid track and making the whole page scroll sideways instead of just the nav). Theme: add overflow-x:clip on html/body as a defensive guard against any future rogue element creating page-level horizontal scroll.

## [1.10.7] — 2026-04-27

- Slider: drop scroll-snap-align from middle cards (only first/last snap) and add overscroll-behavior-x contain to fully eliminate overscroll past the last product. Variation +N: add explicit display rule for revealed overflow chips with high specificity so wrap-below works even when an unrelated rule with lower specificity declared display: inline-flex.

## [1.10.6] — 2026-04-27

- Fix shop-swatches +N badge always-visible bug, expand overflow chips below row instead of inline, match +N pill height to size pills (32px), clamp slider drag to scroll bounds and switch snap to proximity to stop overscroll past last product, anchor WooSQ quick-view button to image bottom on mobile, suppress duplicate prices in WooSQ quick-view modal, polish My Account RTL with logical properties and tighter sidebar spacing

## [1.10.5] — 2026-04-27

- Improve My Account spacing, theme typography, and mobile layout

## [1.10.4] — 2026-04-27

- Fix My Account billing and shipping address card alignment

## [1.10.3] — 2026-04-27

- Fix product slider end behavior, swatch overflow cap, and card height alignment

## [1.10.2] — 2026-04-27

- My Account: hide WC clearfix pseudos that were turning grid items into 4 rows so nav and content stack; override WC's float+30%/68% widths on the nav and content; force border-box. Product Slider: bump card-sizing specificity above WC columns-N width rules so flex-basis wins (was scrolling past the last product because cards picked up WC percentage widths); restore vertical track padding so the hover ring isn't clipped by overflow-y; add overscroll-behavior-inline contain.

## [1.10.1] — 2026-04-27

- Fixed Product Slider showing all products at once - WooCommerce default ul.products grid layout was beating the slider track flex layout on specificity. Fixed My Account page RTL layout - sidebar border, content padding, active accent bar, form row gutter, and notice accents now flip correctly when site is RTL.

## [1.10.0] — 2026-04-27

- Added My Account module - editorial restyle of the classic shortcode WooCommerce my-account page (sidebar nav, serif titles, mono eyebrows, hairline tables, pill status chips). Pure CSS layer, no markup or endpoint changes.

## [1.10.0] — 2026-04-27

- Added My Account module - editorial restyle of the classic shortcode WooCommerce my-account page (sidebar nav, serif titles, mono eyebrows, hairline tables, pill status chips). Pure CSS layer, no markup or endpoint changes.

## [1.9.9] — 2026-04-27

- ProductSlider: scope every product-card style under .cs.cs-products so each rule lands at 4+ classes specificity and consistently beats WooCommerce default stylesheet (woocommerce.css) which was forcing float-based 22.05% widths, height:auto images, centered text, and a giant green sale-flash circle into the slider since 1.9.8 added the .woocommerce wrapper class for plugin-CSS compatibility

## [1.9.8] — 2026-04-27

- ProductSlider: add `woocommerce` class on the slider's outer wrapper so plugin CSS scoped to `.woocommerce ul.products li.product` (e.g. WPC Quick View's `.quickvieww` overlay) matches inside the slider — Elementor's default WC Products widget wraps in `.woocommerce` for the same reason

## [1.9.7] — 2026-04-27

- ProductSlider: gate hover scale + ring on @media (hover: hover) so mobile taps no longer trigger a hover-scale jump that looked like an image swap; add max-width/min-width container clamps on the VariationSwatches archive picker inside slider cards and dispatch a window resize after slider layout commits so the picker's +N overflow scanner re-measures against the final card width

## [1.9.6] — 2026-04-27

- ProductSlider: refactor to emit standard WooCommerce shop-loop markup via wc_get_template_part('content','product') so wishlist / quick-view / sale-flash / image-swap plugins light up the slider with no extra wiring; new Manual / Current-query / Related sources + Hide-free + Hide-out-of-stock toggles for parity with Elementor Pro WC Products

## [1.9.5] — 2026-04-27

- i18n: regenerated shopos-core.pot from current source (395 strings); merged he_IL.po preserves existing translations; new en_US.po ships English translations for all 115 Hebrew strings wrapped in 1.9.4 — non-Hebrew sites now display English by default

## [1.9.4] — 2026-04-27

- Restock Notify i18n: wrapped all remaining hardcoded Hebrew strings in __() / esc_html_e() across module defaults, admin pages, email templates, frontend form template, and frontend JS (via wp_localize_script i18n payload) (H-03)

## [1.9.3] — 2026-04-27

- ProductFeed: cache uncompressed XML byte count in a sidecar size file at generation time and emit Content-Length on the decompressed-streaming branch (M-02)

## [1.9.2] — 2026-04-27

- renamed slider/infinite-scroll asset handles to canonical shopos-core-* prefix per audit (N-07); deprecated names registered as no-source aliases that resolve via dependency on the canonical handle, removed in 2.0.0

## [1.9.1] — 2026-04-27

- security: Restock Notify unsubscribe tokens now use random_bytes (H-01); honeypot on Variation Swatches shop AJAX (L-01); slider drag-scroll releases pointer capture on window blur (L-02); doc-only clarification on Cheapest Default Variation sale-price handling (L-03)

## [1.9.0] — 2026-04-27

- renamed ProductFeed/VariableStockFix hooks and VariationSwatches filter to canonical shopos_core_* prefix per audit (N-02/N-03/N-04); added one-release deprecation shims, version-gated migrations (option copy, cron reschedule, rewrite flush); fixed plugin description to list the actual 8 modules (N-05); release.sh stamping Plugin::VERSION resolves N-01

## [1.8.3] — 2026-04-27

- **ProductSlider: image area now uses the standard WooCommerce loop hook.** The bespoke `.cs-img` background-image, the gallery hover-swap layer, and the `.cs-card-actions` overlay introduced in 1.8.2 are all gone. `render_card()` now fires `do_action( 'woocommerce_before_shop_loop_item_title' )` inside `.cs-imgwrap` and lets WooCommerce + the site's existing plugins paint the image, sale flash, image-swap-on-hover, wishlist buttons, and quick-view buttons — exactly the same way they already do on a normal archive card. CSS sizes the rendered `<img>` (object-fit: cover) inside the slider's per-instance image height. Net effect: enabling YITH Wishlist / WPC Quick View / etc. once on the site lights them up on slider cards too, with no extra wiring. The widget's "Show sale badge" toggle now suppresses WC's `woocommerce_show_product_loop_sale_flash` callback for the card's render only, so the toggle still works without competing with the WC sale flash.

## [1.8.2] — 2026-04-27

- **ProductSlider: hover ring wraps the whole card.** The shared `.cs-ring` element used to live inside `.cs-imgwrap`, so hovering a product card outlined only the image. Moved to a direct child of `.cs-card-product` with its own positioning rules; CategorySlider's image-only ring is unchanged. Ring color still drives off the `--cs-ring-color` widget control.
- **VariationSwatches: shop picker swatches always render on a single line.** `.shopos-shop-pick__opts` switched from `flex-wrap: wrap` to `nowrap` + `overflow: hidden`. A new vanilla-JS `refreshOverflow()` measures each row's chip widths against the available container width, marks anything that doesn't fit as `is-overflow`, and updates the existing `+N` reveal button's `data-count` and label. Runs on init, after `refreshAll()`, on the MutationObserver hook for AJAX-inserted pickers, and on a debounced window resize. Expanding the `+N` drawer pauses the scanner so the user's reveal isn't fought on a resize tick.
- **VariationSwatches: quick-view price duplication fixed.** `maybe_suppress_pdp_price()` now hooks `woocommerce_single_product_summary` at priority 9 (right before WC's default `woocommerce_template_single_price` at priority 10) instead of `wp` with an `is_product()` gate. The `is_product()` check returns false inside AJAX-rendered quick-view modals, so WC's range price + the buy-box's "starting from" line both rendered and drifted apart on selection. The new placement fires during every summary render (real PDP, Elementor preview, quick-view modal, `[product_page]` shortcode), so de-duplication applies everywhere the buy-box renders.

## [1.8.1] — 2026-04-27

- **Slider runtime: fix touch on phones.** Both CategorySlider and ProductSlider were unresponsive to touch swipes — `touch-action: pan-y` blocked native horizontal pan while the JS pointer-drag waited for its 10px / 80ms / horizontal-dominance gates, so an early flick produced no scroll. The JS now skips its drag handler for non-mouse pointer types and the track is `touch-action: pan-x pan-y`, so the browser owns horizontal swipe scrolling on touch (with OS-level momentum). Desktop-mouse drag, the progress scrubber, click-suppression, and arrows are unchanged.
- **ProductSlider: drop the `.cs-price` line in the card meta.** It duplicated the price for variable products where VariationSwatches' picker injects a dynamic "starting from" line in the cart-wrap area. The `Show price` toggle and price typography/color controls have been removed accordingly. (Simple products no longer show a price on the card — open an issue if you need it back.)
- **ProductSlider: equal-height cards.** `.cs-name` now line-clamps to 2 lines and reserves 2 lines' worth of vertical space, so single-line and two-line titles produce cards of identical height — the cart row no longer jitters across the row.
- **ProductSlider: mobile defaults.** `Cards per view (mobile)` now accepts fractional values (step 0.1, default 1.4) so the next card peeks ~30% rather than showing two full cards side-by-side. The image area shrinks to 220px on phones (was 320px from the desktop default) so the card meta + cart fit comfortably on a narrow viewport.

## [1.8.0] — 2026-04-27

- **New module: ProductSlider.** Editorial Elementor widget that renders WooCommerce products as either a draggable horizontal slider — same drag/momentum/progress mechanics as the CategorySlider — or a static grid, controlled per-instance by a "Display as" toggle. Cards show image, name, price, sale badge, and an add-to-cart button (using WC's standard `woocommerce_template_loop_add_to_cart()` so VariationSwatches and other loop-link extensions integrate automatically). Query controls cover all/featured/on-sale/by-category/by-tag with multi-select term pickers; layout supports per-breakpoint cards-per-view, gap, and image height; full RTL support inherited from the shared slider runtime.
- CategorySlider runtime: card counter switched from the category-only `[data-cat="1"]` selector to the markup-agnostic `.cs-card`, and the Elementor `frontend/element_ready` action now subscribes both the category and product widget — same JS file drives both. CategorySlider rendering is unchanged. Asset registration in CategorySlider/Module.php is now idempotent (`wp_script_is` / `wp_style_is` guards) so the new ProductSlider module can defensively register the same shared handles regardless of load order or whether CategorySlider itself is enabled.

## [1.7.15] — 2026-04-27

- CategorySlider: drag now works **on the image area of a card**, not just the gaps between cards. The browser's native HTML5 drag (the "drag this link to bookmarks" gesture on `<a>` elements, and the image-drag-preview on `<img>`) was firing first on `mousedown`, swallowing our Pointer Events so the slider stopped following the cursor when the drag started over a card image. Fix: a delegated `dragstart` listener on `.cs-track` calls `preventDefault()` on every native drag attempt within the slider, plus declarative `-webkit-user-drag: none / user-drag: none` on `.cs-card` and `.cs-card img / .cs-card .cs-img` for browsers that respect the CSS hint. Cards stay clickable and the multi-gate drag detector still governs drag-vs-click.

## [1.7.14] — 2026-04-27

- CategorySlider: **mouse drag on cards is now ON by default** with a natural drag-vs-click detector that mirrors how Amazon / Asos / Swiper / Embla rails behave. Replaced the separate mouse + touch listeners with a unified Pointer Events implementation that engages drag only when ALL three gates pass: distance > 10px, elapsed > 80ms since pointerdown, and `|dx| > |dy| × 1.2` (horizontal-dominant). Click navigation always fires for sub-threshold presses; drag never fires for vertical swipes. `setPointerCapture` lets a drag continue even if the cursor leaves the slider's bounds. `touch-action: pan-y` on `.cs-track` means a vertical swipe over a card lets the browser claim the gesture for native page scroll — touch users keep their natural scrolling. The progress-bar scrubber (1.7.5) stays as an additional desktop affordance. Admins can opt out via the existing widget toggle (`Behavior → Enable mouse drag → off`); when disabled, the track falls back to the browser's native overflow-x scroll on touch and the cursor returns to default. Existing widget instances saved with the toggle untouched will pick up the new default automatically. The `data-cs-mouse-drag` attribute name is preserved for back-compat.

## [1.7.13] — 2026-04-27

- VariationSwatches: archive picker's prepared-product transient key now folds in `woocommerce_currency_pos`, decimal separator, thousand separator, and decimal count — was only keyed on currency *code*. Changing **WC → Settings → General → Currency Position** (or any of the other formatting options) now busts the cache on the next page load, so the picker's `from_price` and per-variation `price_html` re-render in the new format. Previously a position change would leave stale HTML sitting in transients for up to 6 hours, which is why archive cards weren't following the WC setting.

## [1.7.12] — 2026-04-27

- VariationSwatches: removed the 1.7.11 `woocommerce_price_format` override. Currency position now follows WooCommerce's own **Settings → General → Currency position** for every render path — left / right / left-with-space / right-with-space all work as configured. WC's `wc_price()` (used for the unselected "starting from" state) and the per-variation `price_html` (used after a variation is picked) both respect that single setting, so the two states render identically. Shops that want "₪149.90" set position to "Left"; shops that want "149.90 ₪" set position to "Right with space"; etc.

## [1.7.11] — 2026-04-27 — REVERTED in 1.7.12

- VariationSwatches: site-wide `woocommerce_price_format` filter forces currency-on-right (`%2$s&nbsp;%1$s`) on RTL sites. Hebrew/Arabic conventions universally place the symbol after the number (149.90 ₪, not ₪149.90), but installs with translation or multi-currency plugins can end up inconsistent — the QA showed "₪ 319.90" on one state and "549.90 ₪" on the next. Filter runs at priority 999 so it wins against most other format filters. LTR shops are unaffected — a USD shop with `woocommerce_currency_pos = 'left'` stays "$149.90".

## [1.7.10] — 2026-04-27

- VariationSwatches: every template that previously hardcoded `dir="rtl"` (legacy from the shopos-vs Hebrew-first port) now renders `dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>"` — covers the PDP `.shopos-pdp-price` line, the variation buy-box form, the simple buy-box form, the archive variation picker, and the archive simple picker. The PDP price line was rendering LTR on RTL sites because it sits *outside* the form and inherited from whatever wrapper Elementor put around the buy-box (often LTR). Now it follows site language.


- VariationSwatches: archive price base size bumped 13px → 18px (was too small for the editorial card design); `min-height` on the price line raised 16px → 22px to match.
- VariationSwatches: currency-symbol glyph was rendering differently between selected and unselected states — root cause was a downstream rule forcing a different `font-family` on `.woocommerce-Price-currencySymbol` for one of the two markup paths (server `wc_price()` vs JS-injected `variation.price_html`). Locked `font-family: inherit !important` on every descendant of `.shopos-shop-pick__price-value`, `.shopos-shop-pick__price-prefix`, and `.shopos-pdp-price__value` so both states use the same parent-inherited font, making the ₪ (or any currency symbol) glyph render identically.

## [1.7.9] — 2026-04-27

- VariationSwatches: PDP price was rendering huge — `1.4em` on the value compounded with whatever font-size the theme had set on `.price`. Fixed by anchoring the parent `.shopos-pdp-price` itself to an explicit `1.5rem` (~24px) and locking sub-elements to `1em`. Result is a predictable ~24px price regardless of theme.
- VariationSwatches: archive picker price size mismatch between selected / unselected states fixed via universal descendant selector (`.shopos-shop-pick__price-value *`) — the JS-injected `variation.price_html` was wrapping the price in an element my class-list didn't cover, so it was inheriting a different size from the WC default. Same trick applied to PDP.
- VariationSwatches: new **Hide selected-option text** setting (`OPT_SHOP_HIDE_SELECTED`, default ON) hides the "Choose an option" / "{selected value}" text row above the swatches on shop / archive cards. The swatches' active state already shows what's picked. When this is on AND `Hide attribute labels` is on, the entire head row collapses (no orphan whitespace). PDP buy-box is unaffected.

## [1.7.8] — 2026-04-27

- VariationSwatches: PDP price line stays right-aligned + bold (per QA — centring on the buy-box looked disconnected from the rest of the form which is right-aligned in RTL); typography lock now wraps every WC sub-element in a higher-specificity `[data-pdp-price]` selector with `!important`, fixing the leftover size mismatch where Hello-Elementor's `.product .price .amount` rule was beating our previous `inherit` chain. Sale-price `<del>` strikethrough restored explicitly.
- VariationSwatches: archive picker price line is now centred (was right-aligned) and value typography locked the same way as PDP, so selected vs unselected states render at identical size.
- VariationSwatches: new **Hide attribute labels** setting (`OPT_SHOP_HIDE_ATTR_LABELS`, default ON) under WC → Settings → Products → Shop-page variation picker. Hides the `.shopos-shop-pick__attr-label` row (e.g. "Size:" / "Colour:") on shop / archive cards — swatches are usually self-explanatory and the label adds visual noise. Selected-value text stays. PDP buy-box is unaffected. Implemented via a `.shopos-shop-pick--no-labels` wrapper class so the toggle is purely CSS-driven (no template branching).

## [1.7.7] — 2026-04-27

- VariationSwatches: PDP `.shopos-pdp-price` line now centres correctly under RTL parents (was right-aligned because `display: inline-flex` shrunk to natural inline placement) and renders the price at a consistent size whether a variation is picked or not. The previous CSS let theme `.price > .amount` rules size sale/regular sub-spans differently between WC's `variation.price_html` and our `wc_price($min)` output; v1.7.7 locks every WC price sub-element inside `.shopos-pdp-price__value` to `font-size: inherit; font-weight: inherit; color: inherit` so the typography is identical in both states. Sale-price strikethrough on `<del>` is restored explicitly.

  Currency unchanged — the rendered symbol comes from `wc_price()` which respects WooCommerce's currency, position, and decimal-separator settings (₪ in ILS shops, $ in USD shops, € in EUR shops, etc.). Reported as a concern but no code change needed there.

## [1.7.6] — 2026-04-27

- VariationSwatches: archive picker now **auto-selects when only one purchasable variation is available**, regardless of `OPT_SHOP_NO_PRESELECT`. The customer has no real choice in that case, so showing an empty picker is friction. PDP behavior is unchanged.
- VariationSwatches: **single, picker-driven price line** replaces WC's default "₪20 – ₪100" range on both archive cards and the PDP buy-box. Default state shows `החל מ: ₪{min}`; on variation pick the line swaps to that variation's exact `price_html` and the prefix hides. On reset the prefix returns and the value restores to min. Implemented by:
  - Defaulting `OPT_SHOW_PRICE` to ON (was OFF) so the archive picker's existing "starting from" line surfaces by default.
  - Replacing `woocommerce_template_loop_price` with a wrapper that skips rendering for variable products where the picker is active.
  - Removing `woocommerce_template_single_price` for variable PDPs and emitting `.shopos-pdp-price` at the top of `variation-buy-box.php` instead.
  - JS update on `found_variation` / `reset_data` events on the form.
  - CSS: hides the price portion of WC's `.single_variation` pane to avoid duplicate prices below the swatches when a variation is picked. Variation stock + description still render there.

## [1.7.5] — 2026-04-27

- CategorySlider: progress bar is now a real horizontal **scrubber**. Hovering it grows the track + thumb (1px → 3px and 3px → 5px); mousedown anywhere on it jumps to that position; mousedown + drag moves the thumb continuously like a native scrollbar. RTL is handled — visual ratio is converted to the correct sign for normalized scrollLeft. This is the desktop "middle ground" between draggable cards and click-only cards: cards stay click-only by default, and desktop users get a clear, always-available drag affordance on the progress bar.

## [1.7.4] — 2026-04-27

- CategorySlider: **mouse drag is now opt-in.** Previous threshold-based attempts (8 → 16px) still felt greedy in QA — desktop users kept catching drag instead of click. New `Enable mouse drag` switcher (default OFF) decides whether mousedown/mousemove handlers are even attached. Touch drag stays attached unconditionally because mobile/tablet users have no other way to scroll a horizontal track. The grab cursor now also only shows when mouse drag is on. Threshold widened to 24px for the opt-in case.
- VariationSwatches: new **No pre-selected variation on archive** setting (default ON) under WC → Settings → Products → Shop-page variation picker. When on, every shop / archive picker renders with nothing chosen — the customer must actively pick. This bypasses both manually-set defaults from the product editor AND the auto-cheapest pick from the CheapestDefaultVariation module, fixing the case where 1.7.3's PDP-only Cheapest setting wasn't enough on its own (the source of the pre-selection wasn't always Cheapest). PDP buy-box behavior is unchanged.

## [1.7.3] — 2026-04-26

- CategorySlider: drag threshold raised 8px → 16px so mouse drag no longer fights with click intent — clicks land reliably even with jittery trackpads, while an intentional 16px+ drag still scrolls the track. Added **Hover ring color** Elementor control (bound to a new `--cs-ring-color` CSS var; falls back to `--cs-ink` so existing instances are unchanged).
- CheapestDefaultVariation: new **Apply on product pages only** setting (default ON) — auto-selection is now suppressed on shop / archive / loop contexts so swatches there render with nothing pre-selected and the customer has to actively pick a variation. PDP behavior unchanged. Admin and AJAX/REST contexts still get the cheapest pick (variation logic relies on it).

## [1.7.2] — 2026-04-26

- CategorySlider QA fixes: progress bar now reaches the parent's far edge at end-of-scroll (switched from %-of-bar-width translate to pixel-based translate3d derived from `progress.clientWidth - bar.width`); cards are reliably clickable — drag threshold raised to 8px and the track only updates `scrollLeft` once a real drag is confirmed, so tap-with-jitter doesn't sneakily shift the scroll or block the underlying anchor's navigation; `cs-dragging` class is now only applied after the threshold (was applied on every mousedown, briefly disabling pointer-events on cards mid-tap); URL fallback hardened — `get_term_link()` returning false/empty no longer renders `href=""` (which reloaded the current page), now falls back to a `?product_cat=<slug>` query on the home URL; editor-mode access guarded against `\Elementor\Plugin::$instance->editor` being null on early hooks.

## [1.7.1] — 2026-04-26

- CategorySlider polish: fonts now inherit from theme/Elementor (no more forced Fraunces/Inter); added Typography group controls for eyebrow / headline / card name plus card-name color. Drag direction in RTL fixed (the formula `scrollLeft = startScroll - dx` is direction-agnostic in modern browsers — sign flip removed). Hover ring no longer clipped at top/bottom (track has vertical padding + matching negative margin). Image rounded corners fully visible — removed wrapper background that was poking through. Arrows hardened against Elementor + theme button styles (appearance reset, locked dimensions, !important-free higher-specificity selector).

## [1.7.0] — 2026-04-26

- New module **CategorySlider**: Elementor widget rendering WooCommerce `product_cat` terms as a horizontal drag-scroll slider (free-scroll with momentum, optional per-card / per-page CSS scroll-snap). Editorial design ported from the Claude Design "Category Slider" handoff bundle. Controls use Elementor SLIDER + CHOOSE so layout/shape/snap/show-count/direction feel like the design's Tweaks panel. Term query: include / exclude (SELECT2 multiple), child-of, top-level only, order, orderby, hide-empty, limit. Real `product_cat` thumbnails with deterministic striped placeholders. Full RTL support — Direction control (Auto / Force LTR / Force RTL) flips arrow visuals + button order, drag/momentum direction, and progress-bar fill direction; Auto follows `is_rtl()`.

## [1.6.0] — 2026-04-23

- Swatches picker now renders in Related/Upsells/Cross-sells on product pages and any non-archive product loop (home-page product widgets, shortcode grids). New OPT_APPLY_RELATED toggle (default on) lets shop owners disable just the PDP loops.

## [1.5.1] — 2026-04-22

- OOS/priceless simple products now render a single disabled ATC labeled אזל מהמלאי with no Buy Now; variable products hide Buy Now and swap the ATC label when an OOS variation is picked

## [1.5.0] — 2026-04-22

- Test harness + CI: added PHPUnit ^9.6||^10.5 to composer dev-requires; phpunit.xml.dist + tests/bootstrap.php with stubbed WP environment; 44 tests across Detection_Result, Base_Importer contract, Module_Registry, Security helpers, ProductFeed split regression; tests/README.md usage guide; GitHub Actions workflow at .github/workflows/ci.yml runs php-lint + smoke + activation-sim + phpunit across PHP 7.4-8.3 plus wpcs + build-zip jobs

## [1.4.0] — 2026-04-22

- Structure: ProductFeed Module.php (663 lines) split into Generator + Server + Module (324 lines). Generator owns XML writing + file locking + paths + OPT_LAST_GEN. Server owns rewrite + query var + serve_feed. Module remains the lifecycle coordinator (boot, cron, settings, admin panel). Module::generate_feed/feed_file/feed_url/feed_dir/lock_file retained as BC proxies so third-party callers keep working. BATCH/REWRITE_SLUG/QUERY_VAR/OPT_LAST_GEN kept as class constants on Module for the same reason.

## [1.3.0] — 2026-04-22

- Deferred items: RestockNotify assets now load only on product/shop/cart/checkout/shortcode pages (filter: shopos_restock_should_enqueue); Swatches legacy strings migrated from shopos-vs to shopos-core text-domain, .pot + he_IL.po updated; typed Detection_Result value object returned from every Base_Importer::detect() — Legacy_Importer::scan() now coerces + logs on shape mismatch instead of silently going blank

## [1.2.1] — 2026-04-22

- Fix: simple products stuck as OOS on PDP (wc_variation_form + reset_data triggered on simple forms); fall back to WC native template when !is_purchasable() so misconfigured products surface WC warnings; +/- quantity stepper on shop-archive simple picker (matches PDP)

## [1.2.0] — 2026-04-22

- Structure + UX: Base_Importer abstract extracted (6 importers collapsed from ~330 to ~150 lines); admin notice on module boot failure; Dashboard cross-links to legacy settings for Swatches/Restock; VariationSwatches on_deactivate clears stale picker transients; InfiniteScroll aria-live status announcements; :focus-visible outlines on Swatches buy-box + shop picker; per-module README.md documentation; smoke.php now tests Importer layer

## [1.1.0] — 2026-04-22

- Performance: batch-prime variation post caches in ProductFeed write_feed; paginate VariableStockFix daily audit with self-chaining cron; transient-cache Swatches shop-archive variation data and RestockNotify OOS lookups; debounce VariableStockFix variation-save hooks; esbuild minification pipeline in build.sh; SCRIPT_DEBUG-aware asset_min_url helper; MutationObserver replaces retry timeouts in InfiniteScroll

## [1.0.4] — 2026-04-22

- Security: harden email From header, class_exists guards for legacy plugin conflicts, honeypot + i18n on subscribe form, unified rate_limit, transient-backed Tools banners, swatches add-to-cart gate filter

## [1.0.3] — 2026-04-22

### Added
- **Swatches (VariationSwatches)** 1.1.0 — simple-product support.
  - PDP: ShopOS buy box (Add to Cart + Buy Now + quantity stepper + sticky mobile bar) now renders on simple products too, via `woocommerce_simple_add_to_cart`.
  - Shop / archive: compact Add-to-cart card with a quantity stepper replaces WC's default loop link for simple products. AJAX endpoint (`wc-ajax=shopos_shop_add_to_cart`) extended to accept requests without `variation_id`, with server-side `get_max_purchase_quantity()` clamping.
  - OOS / non-purchasable simple products render the ShopOS button disabled with "אזל מהמלאי" on both surfaces.
  - Grouped / external products intentionally keep the WooCommerce defaults.

## [1.0.2] — 2026-04-22

- Removed the ElevatedCards (Cards) module and all its code, assets, templates, translations, Importer and legacy wcec_* option handling.

## [1.0.1] — 2026-04-22

### Fixed
- Parse error in `src/Core/Module_Registry.php` docblock (unescaped `*/` in a path literal closed the comment early and caused a fatal error at plugin activation). The path is now written with backticks.

## [1.0.0] — 2026-04-22

### Added
- Core infrastructure: Plugin, Module_Registry, Module_Interface, Module_Base, Settings_Hub, Security, Logger, Migrations, Legacy_Importer.
- Unified `ShopOS` admin menu with module toggles, per-module settings pages, Tools page.
- PSR-4 autoloader (no Composer required at runtime).
- HPOS + Cart/Checkout Blocks compatibility.
- Seven ported modules (see per-module CHANGELOG.md files for detail).
- Legacy import wizard (detect, import, deactivate, delete).
