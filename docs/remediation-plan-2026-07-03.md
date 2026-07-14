# Remediation Plan — 2026-07-03 Audit

Companion to `docs/audit-2026-07-03.md`. Sequenced as small, individually-shippable PRs, each on its own branch, each respecting the Hard Rules (one concern per PR, ≤12 files / ≤3 modules, no legacy edits without approval, flags unless bugfix/additive). Every PR runs `tools/release.sh` on completion and updates the CLAUDE.md infra-state lines when tests or versions move.

**Standing mechanics checklist per PR** (from CLAUDE.md §2 — listed once here, implied everywhere below):
- Version bump = `shopos-core.php` + `Plugin.php` (core) / `style.css` + `functions.php` (theme, after PR-1) / header + `SHOPOS_DIGITAL_VERSION` (digital).
- Touching a file containing `apply_filters`/`do_action` → regenerate `tests/baseline-hooks.txt`; new option literals → `baseline-options-declared.txt`; new user-facing strings → regenerate `.pot`.
- New tests → update the reported `<tests> / <assertions>` totals in CLAUDE.md verbatim from `vendor/bin/phpunit`.
- CSS/JS-only changes on the storefront → live QA on arba4 (check device Reduce Motion before judging animations).

---

## Phase 0 — Same-day fixes (ship first, independently)

### PR-1 · Release-tooling truth fixes — `fix(tooling)` — size XS
The theme cache-bust bug and the stale readme are both release.sh blind spots; fix the symptom and the tool together.
- `shopos-theme/functions.php`: `SHOPOS_THEME_VERSION` 1.11.25 → 1.11.26 (busts the stale 1.11.26 assets immediately).
- `tools/release.sh bump_theme()`: edit **both** `style.css` and `functions.php` (mirror `bump_core`'s two-file pattern).
- `tools/release.sh bump_core()`: also update `shopos-core/readme.txt` `Stable tag`; set it to the current version now (1.8.3 → current).
- Verify: grep both theme files agree; dry-run a bump on a scratch branch.
- No tests, no flags, no baselines. Retires the `project_theme_asset_version_constant` memory trap at the tooling level.

### PR-2 · CSV formula-injection fix (modern exporter) — `fix(shopos-core)` — size S
- `RestockNotify/CSV_Exporter.php`: escape any field whose first char is `= + - @ \t \r` (prefix `'`) before `fputcsv` — new pure seam (e.g. `escape_csv_field()`) so it's unit-testable.
- Tests: extend the RestockNotify suite — formula-prefixed name/email neutralized; benign values byte-identical.
- **Legacy twin (`legacy/includes/class-shopos-restock-admin.php:271`) is NOT touched** — see Blocked item B-1.
- No flag (security bugfix). No hooks/options/strings → baselines + `.pot` unchanged.

---

## Phase 1 — Confirmed one-line bugs

### PR-3 · CheapestDefaultVariation `options`→`choices` — `fix(shopos-core)` — size XS
- `CheapestDefaultVariation/Module.php:84`: rename schema key; mirror of the 1.16.1 HoverSwap fix.
- Tests: schema test asserting the select renders with both choices (mirror `HoverSwap` schema test); optionally a Hub-level guard test that every registered select schema uses `choices`.
- Live QA: strategy select on the settings page shows Cheapest / First in stock and persists.

### PR-4 · shopos-digital `function_exists` static-string bug — `fix(shopos-digital)` — size XS
- `class-shopos-digital-indexes.php:287`: `function_exists('ShopOS_Digital_Core::opts')` → `class_exists('ShopOS_Digital_Core')` (or call `ShopOS_Digital_Core::opts()` unconditionally — it's the same plugin, always loaded; prefer the direct call).
- Tests: shopos-digital has its own harness (`tests/`) — add coverage if the seam is reachable; otherwise verified by review (one-line).

### PR-5 · ProductFeed silent-failure fix — `fix(shopos-core)` — size S
- `ProductFeed/Generator.php`: check `file_put_contents()` (:109, :280) and `rename()` (:139, :143) results; on failure `Logger::error` and **skip** the `OPT_LAST_GEN` success-timestamp update.
- Tests: pure seam extraction if practical (e.g. `finalize_feed()` returning bool); the filesystem behavior itself is integration.
- Uses Logger (Hard Rule 8 compliant).

---

## Phase 2 — Performance (biggest wins first)

### PR-6 · ProductSlider meta-orderby via `wc_product_meta_lookup` — `perf(shopos-core)` — size M
Replaces the whole-catalog postmeta load (audit C1, the largest per-pageview cost).
- `ProductSlider/Widget.php` `fetch_products_by_meta_orderby()`: one indexed query against `wc_product_meta_lookup` (`total_sales` / `average_rating` / `min_price`) with `ORDER BY … LIMIT N`, honouring the existing include/exclude/source constraints; drop `update_meta_cache` priming.
- **Behavior delta to accept:** price sort keys on `min_price` (WC's canonical) instead of raw `_price` postmeta — for variable products this is the standard WC semantic but may reorder edge cases. Flagged here for explicit owner sign-off in review.
- Tests: the 1.11.42 orderby tests must keep passing; add tie-break parity cases against the old PHP-sort semantics where they matter.
- No flag (internal optimization, same outputs); rollback = git revert. Live QA: homepage best-seller sliders on arba4 + query-count spot check.

### PR-7 · Short-circuit the dead native search query — `perf(shopos-core)` — size S
- `ShopFilters/Query_Builder.php search_product_ids()`: add a `null`-default **pre-filter** (`shopos_core/shop_filters/pre_search_product_ids`) consulted before the `WP_Query`; `Search/Results_Query.php` supplies engine ids there (keeps the existing post-filter for back-compat — Hard Rule #2).
- New hook → additive, no flag needed; regenerate `baseline-hooks.txt` (one new entry + position shifts).
- Tests: pre-filter short-circuits (no WP_Query construction — assert via seam), fallback path unchanged when no listener.

### PR-8 · Facet-cache hardening (audit A2 + A3 + A4) — `perf(shopos-core)` — size M
One module, three related economics fixes to the 1.21.24 cache:
1. **Key validation (A2):** before keying/storing, drop `filter_<tax>` entries not present in the indexed-taxonomy/facet-config set and clamp price/paged to sane bounds; junk-only states bypass `set_transient` entirely.
2. **Page-invariant key (A3):** signature excludes `paged`; pagination block computed outside the cached payload.
3. **Stampede + churn (A4):** short (~30s) rebuild-lock transient with serve-stale-while-rebuilding; debounce `bump_rev()` (e.g. ≥60s between bumps) so steady orders don't retire the whole cache per sale.
- Tests: extend `cache_signature` stability/variance suite (unknown-tax dropped, paged-invariance, junk-state no-store); lock/debounce seams unit-tested pure.
- New option/transient literals if any → `baseline-options-declared.txt`. No flag (hardening of an existing transparent cache); rollback note: the cache degrades to per-request build if transients are cleared.

### PR-9 · Facet-build efficiency (audit A5 + A6 + A7) — `perf(shopos-core)` — size M
- `Query_Builder`: prime terms via one `get_terms(['include'=>…, 'update_term_meta_cache'=>true])` per taxonomy before `build_term_index`/`build_category_meta` (A5); merge `product_prices()` + `product_flags()` into one `meta_lookup` SELECT (A6).
- `Query`: memoize `instock_product_ids` per request; `index_has_data` → `SELECT 1 … LIMIT 1` (A7).
- Tests: merged-SQL seam shape; memoization via counting-$wpdb stub (pattern exists from 1.21.19).

### PR-10 · Background-churn reduction (audit B1 + B3 + B4) — `perf(shopos-core)` — size S/M
Mirrored change across the twin indexers (Search + ShopFilters — 2 modules, stated explicitly per Hard Rule #4 spirit):
- Gate `ensure_scheduled` off the per-request `init` path (admin/cron requests only, or a cheap option-backed "scheduled" memo) — kills 2 Action Scheduler SELECTs per request (B1).
- Reconcile ticks: skip `LAST_RUN`/`WATERMARK` writes when values are unchanged (B3).
- Optional (B4): `mark_dirty` batches within a request (collect ids, single option write on shutdown) — include only if it stays small.
- Tests: scheduling-gate unit tests (both modules' existing indexer suites); idle-tick no-write assertions.

### PR-11 · ShopFilters indexer lighter variation reads (audit B2) — `perf(shopos-core)` — size M — **higher risk**
- `ShopFilters/Indexer.php:329`: replace `get_available_variations()` (full frontend payload) with `get_available_variations('objects')` or direct variation-attribute + `meta_lookup.stock_status` reads.
- **Risk:** index correctness — per-variation in-stock truth is load-bearing (1.12.14). Verification: full-rebuild parity diff on a staging copy (index table byte-compare before/after), plus the existing indexer test suite.
- Sequenced after PR-8/9/10 so cache/index changes don't confound each other. The milder Search `variation_skus()` twin is a follow-up, not bundled.

### PR-12 · Grid per-page source of truth — `fix(shopos-core)` — size S
- `ShopFilters/Query.php:297` (grid slice — the consequential one) and `Query_Builder.php:955` (advisory): prefer `loop_shop_per_page` filter output / WC default over blog `posts_per_page`, fallback chain preserved.
- Tests: per-page resolution matrix. Behavior change on stores where blog ≠ shop page size — on arba4 verify the grid page size is unchanged.

---

## Phase 3 — Store decoupling (productization blockers)

### PR-13 · VariationSwatches JS strings through Labels — `fix(shopos-core)` — size S
- `shopos-swatches.js:219`: read `out_of_stock`/`unavailable` from the localized payload (keys already exist in `Labels.php`); extend the module's `wp_localize_script` payload accordingly.
- Hebrew *fallback* strings in the same file stay as-is (owner's He/En Labels design is documented); only the bypass is fixed.
- Tests: payload-shape test (localized labels present). Live QA: tooltips on arba4 still Hebrew.

### PR-14 · RestockNotify locale correctness — `fix(shopos-core)` — size S
- `Email.php:232-233`: derive `lang` from `get_locale()` and direction from `is_rtl()` (module's locale system already selects the strings).
- `assets/js/frontend.js:72`: route the script-config error through `t()` with the existing fallback pattern.
- Tests: email-shell seam (locale → lang/dir attrs) if extractable; JS is live-QA. `.pot` unchanged (locale files, not gettext). Legacy email twin untouched (see B-1).

### PR-15 · Font-token routing (kill `sk_type_*` in plugin CSS) — `fix(shopos-core)` + `fix(shopos-theme)` — size S
- `shopos-theme/assets/css/shopos-tokens.css`: `--shopos-ui-font-body`/`--shopos-ui-font-heading` wrap the kit variables (single mapping point — already partially true).
- `VariationSwatches/assets/css/shopos-*.css` (5 sites): `var(--e-global-typography-sk_type_12-…)` → `var(--shopos-ui-font-body, inherit)`.
- Theme touched → bump `SHOPOS_THEME_VERSION` (PR-1's fixed tooling handles it). Live QA: typography unchanged on arba4.

---

## Phase 4 — Consistency & hardening

### PR-16 · Z-index token adoption — `fix(shopos-core)` — size S — CSS-only
- Migrate VariationSwatches (`9999!important`, `10000`), ShopFilters (`99998/99999`), QuickView (`100000`) to `var(--shopos-ui-z-modal/--shopos-ui-z-max, <current-literal>)` fallback form (Search is the template). Fallback values = current literals, so non-theme sites are byte-equivalent.
- Live QA: QuickView drawer over ShopFilters drawer over sticky header on arba4, mobile + desktop.

### PR-17 · Guard & comment sweep — `chore(shopos-core)` — size XS — no behavior
- ABSPATH guards on the 6 bare files (`Sampler_Scheduler`, `Subscribers`, `CSV_Exporter`, `RestockNotify/Frontend`, `Search/Results_Query`, `HoverSwap/Module`).
- The 11 stale "flag-gated" docblocks in Search/ShopFilters/HoverSwap corrected to "always-on since <version>".
- `infinite-scroll.js:13`: drop the `shopos_debug` alias (debug-only surface, not a shipped hook — Hard Rule #2 not implicated).
- `uninstall.php:22`: comment annotating the sanctioned `error_log` exception.
- Comment/guard-only; baselines shift position-only if any guarded file carries hooks.

### PR-18 · shopos-digital hardening — `fix(shopos-digital)` — size S
- `class-shopos-digital-indexes.php`: allowlist-validate index name (`/^[a-z0-9_]+$/i`) and column tokens before DDL interpolation (:416, :231), mirroring `ajax_convert_myisam`'s table check.
- `class-shopos-digital-admin.php`: `esc_html()` on `:717`, `esc_attr()` on `:710/:714`.
- `class-shopos-digital-frontend.php:158`: cdnjs preconnect behind its own toggle (default off) or folded into the `fe_preconnect_domains` default — pick toggle (no silent behavior change for sites relying on it… default **on** to preserve behavior, off is the eventual owner call — decide in review).

### PR-19 · Additive tunability filters — `feat(shopos-core)` — size S
All additive (Hard Rule #1 exception applies — no flags):
- `shopos_core/shop_filters/facet_cache_ttl` (default 5 min).
- `shopos_core/search/max_results` (default 500).
- `Security::rate_limit` defaults filterable once (`shopos_core/rate_limit_defaults`) — replaces the thrice-repeated `30, 60`.
- ProductSlider archive thumbnail size filter (default `large`).
- InfiniteScroll `rootMargin` exposed through the existing localized settings payload.
- Regenerate `baseline-hooks.txt`; document each filter in `docs/hooks.md` (or the established hooks doc).

---

## Phase 5 — Hygiene & docs

### PR-20 · Repo hygiene batch — `chore(repo)` — size S — docs/config only
- Move `AUDIT-2026-04.md`, `Audit.md`, `REVIEW.md` → `docs/archive/`; move `my account page/` → `docs/prototypes/my-account/`.
- `.gitignore`: commit the pending `.gstack/` line, add `.impeccable/`, drop the stale `!shopos-theme.zip.example`.
- Root `CHANGELOG.md` wording ("both" → "three packages"); create a minimal `shopos-digital/CHANGELOG.md` seeded from git history going forward.
- `docs/roadmap.md`: 6.6 shipped-version correction (1.12.25 → 1.12.26) + refresh "Last updated".
- No version bumps (no shipped code touched).

### PR-21 · Theme translation sources — `chore(shopos-theme)` — size S
- Recover `shopos-theme-he_IL.po` (from backups, or `msgunfmt` the committed `.mo`) and commit it; generate `shopos-theme.pot` via WP-CLI; wire the theme into the existing `.pot`-regeneration habit.

---

## Blocked on owner decision — answer before the affected PRs

**B-1 · Legacy touches (Hard Rule #3 — needs written migration note + approval).** Three legacy findings share one gate:
  - CSV formula injection twin (`legacy/class-shopos-restock-admin.php:271`) — *security*, highest urgency of the three. Question: is the legacy exporter reachable on the live store, or is the modern module fully in charge? If reachable, I'll write the one-page migration note covering just this line and request approval.
  - VariationSwatches archive group-versioned transients (audit D1 — miss storms on a fast-moving store).
  - Legacy Hebrew-msgid strings (cosmetic until a non-Hebrew deployment).
  **Recommendation:** approve a narrow legacy PR for the CSV line now; fold D1 + msgids into the eventual legacy-retirement plan.

**B-2 · `shopos-*` rename strategy.** Options: (a) leave permanently as documented back-compat (zero risk, permanent naming debt), (b) dual-class emit (`shopos-* shopos-is-*`) for 2 minor versions then drop the old, (c) hard rename (breaks any store CSS targeting old classes). **Recommendation: (b).** Affects InfiniteScroll CSS+JS + ProductSlider CSS (3 files, 2 modules — fits one PR once decided).

**B-3 · InfiniteScroll Button/Hybrid trigger modes.** The settings page has advertised a deferred "button UI" since the feature shipped. Build the Load-more button, or remove the two stub choices from the select? **Recommendation: remove the stubs** (re-add when actually built; removing select *choices* that never functioned is not a Hard Rule #2 surface removal — the option key survives).

**B-4 · Duplicated-constants strategy** (768px ×5 files, PHP↔CSS card breakpoints, show-more 8/n+9, indexer trio). Options: document-only (cross-referencing comments + a breakpoint table in shopos-tokens.css) vs a real mechanism (localized values / CSS custom properties). **Recommendation: document-only now** — the mechanism isn't worth the churn until a breakpoint actually needs to move; fold the comments into whichever PRs touch those files anyway.

**B-5 · Follow-up audit passes** (the two incomplete sweeps): uninstall created-vs-cleaned matrix, and the dead-code cross-check (assets/options/CSS orphans). Run as audit-only sessions before or after the remediation phases? **Recommendation:** after Phase 2 — the uninstall matrix will also catch anything the perf PRs add.

---

## Suggested sequence & sizing

| Order | PRs | Theme | Size |
|-------|-----|-------|------|
| Now | 1, 2 | Live bug + only real vulnerability | XS + S |
| Next | 3, 4, 5 | Confirmed bugs | 3×XS/S |
| Then | 6 → 7 → 8 → 9 → 10 → 12 → 11 | Performance, biggest-win-first; risky indexer change last | mostly S/M |
| Then | 13, 14, 15 | Store decoupling | 3×S |
| Then | 16, 17, 18, 19 | Consistency & hardening | 4×XS/S |
| Anytime (parallel-safe) | 20, 21 | Hygiene (docs/config only) | 2×S |
| Gated | B-1…B-5 | Owner decisions | — |

Each PR: own branch (`fix/…`, `perf/…`, `chore/…`), Conventional Commit scope, PR template sections (Backward Compat / Feature Flag: none-bugfix-or-additive / Rollback), release.sh at the end. Current branch `perf/shop-filters-facet-cache` is 1.21.24's — Phase 0 starts from fresh branches off `main`, and per the in-flight-versions rule, check `git log --all` + `dist/` before each core bump.
