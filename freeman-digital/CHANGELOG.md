# Freeman Digital — Changelog

Seeded from git history at 1.7.4. Per-release detail for 1.7.3 and earlier lives in `readme.txt`.

## [1.7.5] — 2026-07-05

- Frontend cdnjs preconnect hint is now gated behind a new `fe_preconnect_cdnjs` option (default on, so existing installs still emit it — zero behavior change), letting sites that don't use cdnjs switch it off
- FD_Indexes DDL hardening: allowlist-validate the table identifier, index name and column-list token before backtick-interpolating them into ALTER/DROP/CREATE INDEX (defense in depth — callers already gate to static definitions behind nonce + manage_options)

## [1.7.4] — 2026-07-05

- `apply_deep` now calls `FD_Core::opts()` directly instead of a `function_exists('FD_Core::opts')` ternary that never resolved, so the `wp_parse_args` defaults merge runs — deep reindex no longer silently ran without maintenance mode on stores that never saved FD settings
