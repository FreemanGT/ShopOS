# ShopOS Digital — Changelog

Seeded from git history at 1.7.4. Per-release detail for 1.7.3 and earlier lives in `readme.txt`.

## [1.7.6] — 2026-07-16

- **Fix: "Clean Revisions" and "Clean Trashed Posts" never deleted anything** — both used a multi-table `DELETE a,b,c … LEFT JOIN … LIMIT {BATCH}`, and MySQL forbids `LIMIT` on a multi-table DELETE, so every run errored (swallowed as `false` by `$wpdb->query`) and `chunked_delete` reported 0. The batch is now bounded by a derived-table ID subquery (`WHERE a.ID IN (SELECT ID FROM (SELECT ID … LIMIT {BATCH}) batch)`), which MySQL accepts, preserving the chunked/lock-friendly semantics. Caught by the newly **un-masked ShopOS Digital CI** (the suite had never actually gated: the workflow file was in an unregistered location AND its phpunit step ended in `|| true`) — this repo-level CI move ships in the same monorepo PR (shopos-core 1.38.0)
- Test fix: the months-cache invalidation tests seeded a stale transient key (`shopos_digital_months_post_posts`) — the code has used `shopos_digital_months_{$post_type}` (no suffix) for both read and invalidation since the v1.7.0 transient move; the tests now seed the real key

## [1.7.5] — 2026-07-05

- Frontend cdnjs preconnect hint is now gated behind a new `fe_preconnect_cdnjs` option (default on, so existing installs still emit it — zero behavior change), letting sites that don't use cdnjs switch it off
- ShopOS_Digital_Indexes DDL hardening: allowlist-validate the table identifier, index name and column-list token before backtick-interpolating them into ALTER/DROP/CREATE INDEX (defense in depth — callers already gate to static definitions behind nonce + manage_options)

## [1.7.4] — 2026-07-05

- `apply_deep` now calls `ShopOS_Digital_Core::opts()` directly instead of a `function_exists('ShopOS_Digital_Core::opts')` ternary that never resolved, so the `wp_parse_args` defaults merge runs — deep reindex no longer silently ran without maintenance mode on stores that never saved FD settings
