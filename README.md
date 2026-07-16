# ShopOS

**ShopOS** is a modular WordPress + WooCommerce product suite for Elementor stores, localized for the Israeli market (Hebrew/RTL UI, shekel pricing).

## Packages

- **`shopos-theme/`** — child theme of [Hello Elementor](https://wordpress.org/themes/hello-elementor/). Design tokens, typography, RTL, WooCommerce presentation refinements — plus, behind permanent default-off ShopOS Core feature flags, the buy-path templates at `shopos-theme/templates/woo/` (the ShopOS Line, decisions §11).
- **`shopos-core/`** — single plugin containing all storefront features as independently togglable modules. New modules are auto-discovered from `src/Modules/`.
- **`shopos-digital/`** — standalone WordPress/WooCommerce performance plugin: database indexes, query tuning, autoload optimizer, security hardening, speed tuning, transient management, bloat removal.

The theme requires the plugin. The plugin works without the theme, so data (subscribers, settings, feed, indexes) is never orphaned if the theme is changed.

## Modules

ShopOS Core ships **15 built modules** across storefront discovery, product/conversion, customer account, merchandising, and experience — with more planned to reach a complete storefront OS (~20).

👉 **See [`docs/Modules.md`](docs/Modules.md) for the full, categorized module catalog** (what's built, what's planned, and the roadmap).

## Getting started (development)

```bash
composer install          # installs PHPCS + WordPress-Coding-Standards
npm install               # installs esbuild + postcss
bash tools/build.sh       # produces shopos-theme.zip + shopos-core.zip in dist/
```

Run the test suite (PHP 8.3 required for PHPUnit 10):

```bash
PATH="/opt/homebrew/opt/php@8.3/bin:$PATH" composer test
```

## Interaction protocol

When asking the agent to make changes, use `<scope>: <request>`. Scopes:

- `Theme: …` — only `shopos-theme/`
- `Core: …` — Core infrastructure (Registry, Settings Hub, Security, Dashboard)
- `<Module>: …` — a specific module (e.g. `Search:`, `ShopFilters:`, `QuickView:`)
- `Digital: …` — the `shopos-digital/` performance plugin
- `New module <Name>: …` — scaffold a brand-new module

Hooks are namespaced `shopos_core/<module>/…`; options prefixed `shopos_core_<module>_…`. See each module's `HOOKS.md` for public extension points, and [`docs/CLAUDE.md`](docs/CLAUDE.md) for the working rules.

## License

GPL-2.0-or-later.
