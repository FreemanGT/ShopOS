# ShopOS Digital — Changelog

Seeded from git history at 1.7.4. Per-release detail for 1.7.3 and earlier lives in `readme.txt`.

## [1.7.9] — 2026-07-22

- **FIX (uninstall):** the transient-cleanup `DELETE`s targeted the pre-rebrand `_transient_fd_*` prefix, but live code writes every transient family under `shopos_digital_` (`months_*`, `user_counts_*`, `author_count_*`, `cat_dropdown_*`, `can_ddl`, `has_products`, `pc_*`) — so no live transient was ever matched on uninstall (B-5 audit prefix-drift finding). Repointed to a single `_transient_shopos_digital_%` + `_transient_timeout_shopos_digital_%` pair (and the multisite `_site_transient_*` equivalents) that sweeps all seven families; dropped the dead `fd_*` patterns. Impact was bounded — all these transients self-expire ≤24h — but uninstall now actually clears them. Uninstall-only; live-QA (delete plugin, confirm no `_transient_shopos_digital_*` rows remain).

## [1.7.8] — 2026-07-17

- FIX: escape the profiler table's three raw echoes in the admin renderer (`$type_badge` / `$color` / `$r->recommendation`) — the PR-18 remediation bullet recorded as shipped in 1.7.5 but silently dropped. Values are internally generated (audit risk: Low); escaping closes the ledger gap.

## [1.7.7] — 2026-07-17

- **Fix: the Query Optimizer's `no_found_rows` forcing (default on) now exempts WooCommerce product-archive main queries** — the shop page (post-type archive) and every product taxonomy archive. Classic archive renders read the main query's `found_posts` for the result count, pagination (`max_num_pages`), and `wc_get_loop_prop( 'total' )` — the loop guard inside `archive-product.php` — so forcing `no_found_rows` there rendered an **empty product grid with no pagination** whenever anything classic served the archive (WooCommerce's own template fallback today; the ShopOS theme's flag-gated PLP template, decisions §11.4 row 5, tomorrow). Caught live by the row-5 wp-env QA window. New regression test + a `shopos_digital/qo_no_found_rows_exempt` filter so stores can exempt other classically rendered paginated archives without disabling the optimization site-wide.
- **Disclosed side effect on Elementor-rendered archives**: the ShopOS Product Slider's current-query grid builds its archive pagination from the main query's `max_num_pages` (Widget.php:1384) — with the optimizer forcing counts off, that pagination silently rendered NOTHING on multi-page archives. Restoring the counts makes the grid's pagination appear where it was being suppressed: a repair of optimizer-caused breakage, but a visible flag-off change on affected stores (multi-page Elementor archives with a current-query ShopOS grid). Cost elsewhere: one count query per product-archive page. Admin toggle description updated; `readme.txt` Stable tag bumped to 1.7.7 (had gone stale again at 1.7.5).

## [1.7.6] — 2026-07-16

- **Fix: "Clean Revisions" and "Clean Trashed Posts" never deleted anything** — both used a multi-table `DELETE a,b,c … LEFT JOIN … LIMIT {BATCH}`, and MySQL forbids `LIMIT` on a multi-table DELETE, so every run errored (swallowed as `false` by `$wpdb->query`) and `chunked_delete` reported 0. The batch is now bounded by a derived-table ID subquery (`WHERE a.ID IN (SELECT ID FROM (SELECT ID … LIMIT {BATCH}) batch)`), which MySQL accepts, preserving the chunked/lock-friendly semantics. Caught by the newly **un-masked ShopOS Digital CI** (the suite had never actually gated: the workflow file was in an unregistered location AND its phpunit step ended in `|| true`) — this repo-level CI move ships in the same monorepo PR (shopos-core 1.38.0)
- Test fix: the months-cache invalidation tests seeded a stale transient key (`shopos_digital_months_post_posts`) — the code has used `shopos_digital_months_{$post_type}` (no suffix) for both read and invalidation since the v1.7.0 transient move; the tests now seed the real key

## [1.7.5] — 2026-07-05

- Frontend cdnjs preconnect hint is now gated behind a new `fe_preconnect_cdnjs` option (default on, so existing installs still emit it — zero behavior change), letting sites that don't use cdnjs switch it off
- ShopOS_Digital_Indexes DDL hardening: allowlist-validate the table identifier, index name and column-list token before backtick-interpolating them into ALTER/DROP/CREATE INDEX (defense in depth — callers already gate to static definitions behind nonce + manage_options)

## [1.7.4] — 2026-07-05

- `apply_deep` now calls `ShopOS_Digital_Core::opts()` directly instead of a `function_exists('ShopOS_Digital_Core::opts')` ternary that never resolved, so the `wp_parse_args` defaults merge runs — deep reindex no longer silently ran without maintenance mode on stores that never saved FD settings
