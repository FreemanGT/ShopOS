# Agent instructions — ShopOS

WordPress/WooCommerce multi-store suite: custom theme + custom plugins, PHP 8.3.

**Page building is Elementor only — no Gutenberg.** Do not write block-editor code (no custom blocks, block.json, theme.json workflows, or block-based cart/checkout).

## Skills

This repo ships agent skills in `.claude/skills/` — each `<name>/SKILL.md` (plus `references/`) contains vetted, current guidance. **Before working in an area below, read the matching SKILL.md and follow it.** Claude Code loads them automatically; other agents (Cursor, Codex, …) should open the files directly.

| When working on | Read |
|---|---|
| Classifying a WP task / which skill applies | `wordpress-router`, `wp-project-triage` |
| Plugin architecture, hooks, activation, Settings API, release packaging | `wp-plugin-development` |
| WooCommerce extensions: HPOS, order CRUD, gateways, template overrides | `wp-woocommerce-dev` |
| REST endpoints: `register_rest_route`, schema validation, permissions | `wp-rest-api` |
| PHPStan config, baselines, WP/Woo stubs, typing | `wp-phpstan` |
| WP-CLI operations: search-replace, db export/import, cron, automation | `wp-wpcli-and-ops` |
| Backend performance: profiling, queries, autoload, object cache, cron | `wp-performance` |
| Frontend performance: LCP/INP/CLS, loading, rendering | `core-web-vitals`, `performance` |
| Security review of PHP: XSS, SQLi, CSRF, nonces, capabilities | `wp-security-review` |
| Security review of diffs / config defaults / bug variants | `differential-review`, `insecure-defaults`, `variant-analysis` |
| DB migrations, upgrade routines, dbDelta, version guards | `wp-migration-upgrade-review` |
| Modern CSS/JS features + browser support (Baseline) | `modern-web-guidance` |
| Accessibility (WCAG 2.2) authoring | `accessibility` |
| SEO: meta, structured data, sitemaps | `seo` |
| General web hardening / modernization | `best-practices`, `web-quality-audit` |
| HTML email templates | `email-html-mjml` |
| Product UI / frontend design polish | `impeccable` |
