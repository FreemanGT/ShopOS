# ShopOS Theme

Child theme of [Hello Elementor](https://wordpress.org/themes/hello-elementor/). Presentation layer only — all business logic lives in the **ShopOS Core** plugin.

## What the theme provides

- Design tokens (colors, type, spacing, radii, motion) in `assets/css/shopos-tokens.css`. These are the API every module consumes.
- Typography + RTL defaults tuned for Hebrew and Latin.
- Plugin-dependency bootstrap: shows an admin notice / install button if ShopOS Core is missing.
- HPOS + Cart/Checkout Blocks compatibility declaration.
- Small theme-level hooks into Core.

## What the theme does **not** do

- Register post types, custom tables, AJAX endpoints, crons, or admin pages. All of that is in ShopOS Core.
- Override `template-parts/` from Hello Elementor unless explicitly needed.

## File layout

```
shopos-theme/
├── style.css                  theme header
├── functions.php              minimal bootstrap
├── theme.json                 palette + typography + spacing
├── assets/
│   ├── css/
│   │   ├── shopos-tokens.css the design API
│   │   ├── shopos.css        theme components
│   │   └── shopos-rtl.css
│   └── js/shopos.js
├── inc/
│   ├── class-shopos-theme.php
│   ├── plugin-dependencies.php (TGMPA-lite)
│   ├── hooks.php
│   └── woocommerce.php
└── CHANGELOG.md
```

## Release

See `tools/release.sh` in the root workspace.
