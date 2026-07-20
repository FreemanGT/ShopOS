# ShopOS Theme — Changelog

## [1.19.0] — 2026-07-20

- **ShopOS Line search results** (§11-B surface 4), behind `theme.template_search` (Core flag, default OFF). The shared `template_include` loader (`inc/class-shopos-template-loader.php`) gains a second claim arm: `should_claim_search()` claims exactly the product-archive main query that carries a search term (native product search `is_shop() && is_search()`, or the Elementor search page where `is_search()` is false and the term is in the request URL) — the mirror-positive of the PLP arm's §11 Ruling 2 refusal, and provably disjoint from it (a generic non-product search is never claimed). The shared fonts-warning + missing-file fallback logic is factored into a `resolve()` helper (each arm keeps its own literal flag read for the FeatureFlagsAdminTest scan); `context()` extends to `'' | 'plp' | 'search'`. New `templates/woo/search-results.php` — a fork of `archive-product.php` with a "Search results for X" heading (term `esc_html`'d, read from the request) and WooCommerce's own no-results state, every archive hook verbatim; the always-on `Results_Query` (Core) owns the query, so the template is render-only. New `assets/css/shopos-search.css` (skin-light `--shopos-ui-*` tokens, RTL-safe logical properties), enqueued only when the loader claims the search surface. Flag off ⇒ the current search render, byte-identical (§11 Ruling 6). Requires shopos-core 1.51.0+ for the flag.

## [1.18.0] — 2026-07-20

- **ShopOS Line My Account** (§11-B surface 3), behind `theme.template_account` (Core flag, default OFF). The cart's `woocommerce_locate_template` filter is **generalized** from `locate_cart_template` to a shared `locate_woo_template` (one callback, a claim arm per surface via `woo_surface_enabled()`) that now also redirects `myaccount/*` templates to `templates/woo/myaccount/` when the account flag is on — never by file presence (§11.3). Two structural templates forked (`my-account.php` shell → adds the `.shopos-account` sidebar-grid wrapper; `navigation.php` → the account nav rail), WC hooks/classes verbatim. The account **content** (dashboard, orders, view-order, downloads, payment-methods, addresses) and the **auth/payment forms** (login, edit-account, add-payment-method, password reset) are **not** forked — CSS-skinned via WooCommerce's stable classes under the `.shopos-account` scope, so WC keeps ownership of every nonce + gateway field. Flag off ⇒ WooCommerce's own account render, byte-identical (§11 Ruling 6). New `assets/css/shopos-account.css` (skin-light `--shopos-ui-*` tokens with on-palette fallbacks, RTL-safe logical properties, 768px canon breakpoint, reduced-motion), enqueued only on `is_account_page()` when the flag is on; no JS (WC owns the account form behaviour). Single pinned flag read via `ShopOS_Theme::account_enabled()` (FQCN). Only affects the `[woocommerce_my_account]` shortcode account. Requires shopos-core 1.50.0+ for the flag.

## [1.17.0] — 2026-07-20

- **ShopOS Line cart page** (§11-B surface 2), behind `theme.template_cart` (Core flag, default OFF). The whole cart page is theme-owned: forks of WooCommerce's `cart/cart.php`, `cart-empty.php`, `cart-totals.php`, `cart-shipping.php`, `shipping-calculator.php`, `proceed-to-checkout-button.php`, `cross-sells.php` live at `templates/woo/cart/` and are reached ONLY via a flag-gated `woocommerce_locate_template` filter — never by file presence (§11.3). Flag off ⇒ WooCommerce's own templates render, byte-identical (§11 Ruling 6); every cart hook, filter, class/id and the `woocommerce-cart` / shipping-calculator nonces are preserved verbatim so WC's cart JS and every plugin still bind. New `assets/css/shopos-cart.css` (skin-light `--shopos-ui-*` tokens with on-palette fallbacks, RTL-safe logical properties, 768px canon breakpoint stacking the item table into labelled rows, reduced-motion aware) + defensive `assets/js/shopos-cart.js` (submit busy-guard that prevents a double-POST without touching WC's own handlers), both enqueued only on the cart page when the flag is on. Single pinned flag read via `ShopOS_Theme::cart_enabled()` (FQCN — the theme is unnamespaced); Core absent ⇒ hard false ⇒ WooCommerce default cart. Only affects the `[woocommerce_cart]` shortcode cart — Cart-block stores need a per-store block→shortcode content-migration (decisions §11 Ruling 9, Hard Rule #3). Requires shopos-core 1.48.0+ for the flag.

## [1.16.0] — 2026-07-20

- **ShopOS Line header/footer chrome** (§11-B surface 1), behind `theme.template_chrome` (Core flag, default OFF). New `header.php` / `footer.php`: when the flag is off they **require-parent passthrough** to the Hello Elementor parent chrome (byte-identical, decisions §11 Ruling 6, verified in wp-env); when on they render a skin-light, `--shopos-ui-*` token-driven classic header (custom-logo / site-name, `menu-1` primary nav, `[shopos_search]`, cart link + count, mobile toggle) and footer (`menu-2` nav, `shopos-footer` widget area, copyright). New `assets/css/shopos-chrome.css` (RTL-safe logical properties, 768px canon breakpoint, reduced-motion aware) + `assets/js/shopos-chrome.js` (progressive-enhancement mobile toggle, Escape-to-close), both enqueued only when the chrome flag is on. Single pinned flag read via `ShopOS_Theme::chrome_enabled()` (FQCN — the theme is unnamespaced); Core absent ⇒ hard false ⇒ parent chrome. Requires shopos-core 1.47.0+ for the flag.

## [1.15.1] — 2026-07-19

- Fix: compare manifest against WordPress's on-disk theme version instead of the opcached SHOPOS_THEME_VERSION constant, so an in-place update no longer re-offers the version already installed

## [1.15.0] — 2026-07-19

- Foundation design tokens — overlay/scrim, accent hover/active, motion-spin, card-hover-scale, drawer-width, measure, focus-outline, breakpoint canon (additive; no visual change)

## [1.14.0] — 2026-07-17

- Theme-owned PLP: templates/woo/archive-product.php + the shared theme loader (inc/class-shopos-template-loader.php) behind shopos_core_theme_template_plp_enabled — §11.4 row 5

## [1.13.1] — 2026-07-17

- Restore the inc/updater.php require dropped by the PR #22 merge resolution — the theme self-updater was dead code on 1.13.0

## [1.13.0] — 2026-07-16

- ShopOS Line §11.4 row 4 — ship the theme-owned PDP template at templates/woo/single-product.php (verbatim copy of the Core module template; resolved only by Core's flag-gated loader when shopos_core_theme_template_pdp_enabled is on; inert by file presence)

## [1.12.1] — 2026-07-16

- Updater: manifest cache 6h → 5min for near-instant dashboard updates

## [1.12.0] — 2026-07-16

- Dashboard self-updates via ShopOS release channel; add theme screenshot

## [1.11.31] — 2026-07-16

- Wire the fonts_selfhost flag (§11.4 row 3b): flag ON enqueues the 1.11.30 self-hosted shopos-fonts.css and suppresses the Elementor kit Google Fonts print; flag OFF/Core-absent byte-identical (pinned FQCN read path — the theme is unnamespaced). design-tokens bridge gains the kit-slot re-map: emits --shopos-ui-font-body/display only when Design::kit_slots() differs from the defaults

## [1.11.30] — 2026-07-16

- Self-hosted storefront webfonts (§11.4 row 3a): Heebo/Assistant/Rubik shipped as variable-weight woff2 (hebrew+latin subsets, Google Fonts' own subset splits and unicode-ranges, font-display swap) under assets/fonts/ + assets/css/shopos-fonts.css, with OFL licenses. INERT — nothing enqueues the file until the shopos_core_theme_fonts_selfhost_enabled flag ships (row 3b), so the render is unchanged

## [1.11.29] — 2026-07-16

- CSS-chain robustness (§11.4 row 2 / Ruling 6.2): shopos-tokens hard-depended on the parent hello-elementor-theme-style handle, so a store where the parent enqueue is absent (hello_elementor_enqueue_style filter / hide-theme-style setting / renamed handle after a parent update) silently lost the entire ShopOS CSS chain — tokens, theme, RTL and every inline block attached to the shopos-tokens handle (design-token bridge, Design panel). enqueue_assets() now depends on the parent handle only when it is registered at our wp_enqueue_scripts:20 slot (parent registers at 10) and falls back to a no-dependency enqueue otherwise; print order with the parent present is unchanged

## [1.11.28] — 2026-07-15

- theme.json → `--shopos-ui-*` token bridge: new `inc/design-tokens.php` reads the merged Global Settings (`wp_get_global_settings()` — theme.json presets plus any user Global-Styles override) and re-emits the palette / spacing / radius / motion values as `--shopos-ui-*` custom properties inline, right after `shopos-tokens.css` (on `wp_enqueue_scripts` priority 21, via `wp_add_inline_style('shopos-tokens', …)`). This makes theme.json the single source of truth for those token values instead of hand-syncing them into `shopos-tokens.css` a second time; that file stays the semantic + fallback layer, so with today's matching theme.json the render is byte-identical. The three bridged motion tokens additionally re-emit the `@media (prefers-reduced-motion: reduce)` → `0ms` collapse inside the inline block, because that block prints after `shopos-tokens.css` and would otherwise override its reduced-motion reset (equal specificity, later source order) — so the accessibility preference is preserved. Purely additive (no feature flag — new CSS variables with backward-compatible fallbacks); kill switch is the `shopos_theme_design_tokens_enabled` filter (return false → empties the block → the CSS fallbacks render). Deliberately not bridged: typography (the hand-tuned `clamp()` scale + the `sk_type_*` Elementor-global bridge stay), the semantic `--shopos-ui-color-*` layer and `.is-accent-*` presets (they reference the raw palette and flow through), and tokens with no theme.json source (`palette-black`/`palette-sand`, `radius-round`, `motion-instant`, the eases). This is the theme.json → CSS direction (front-end render), distinct from the block-editor-picker direction dropped in decisions §4.3 (ex-Roadmap #7)

## [1.11.27] — 2026-07-05

- Release tooling truth fixes: SHOPOS_THEME_VERSION now bumped in lockstep with style.css (cache-bust fix), core Stable tag stamped on release

## [1.11.26] — 2026-06-11

- Exclude ShopOS slider/grid widget containers (.cs-track/.cs-grid) from the mobile-columns Customizer override — the forced grid broke the slider track and overrode the widget's mobile column control on product archives (grid parity audit G2)

## [1.11.25] — 2026-05-13

- Customizer → WooCommerce → Product Catalog: new "Mobile columns" select (1/2/3/4, defaults to "Don't override"). When set, prints an inline `@media (max-width:767px)` rule on product archive pages (shop, product category/tag, product search) that pins `.woocommerce ul.products` `grid-template-columns` to the chosen count with `!important` and forces `display: grid` so the rule lands regardless of whether the underlying loop renders as grid/flex/block. Opt-in: with the sentinel "default" value (the shipped default), nothing is emitted and existing behaviour is preserved.

## [1.11.24] — 2026-05-12

- shopos-theme: defensive grid-template-columns floor (minmax(0,1fr)) on Elementor archive product grid so a wide grid item cannot inflate one column and push cards off-page

## [1.11.23] — 2026-05-11

- Typography: `--shopos-ui-font-body` and `--shopos-ui-font-display` now resolve from Elementor's global typography (`var(--e-global-typography-sk_type_12-font-family, …)` / `…sk_type_2…`, which the Style Kits for Elementor addon writes), with the previous hardcoded `'Heebo'` / `'Assistant'` stacks as fallback. The theme no longer overrides Style Kits' fonts; `--shopos-ui-font-mono` (used for `<code>` / `<pre>`) is unchanged. Since `shopos.css`'s `body` / heading rules read those tokens — and `shopos-core`'s My Account reads `--shopos-ui-font-body` — they all follow automatically.
- Bumped `SHOPOS_THEME_VERSION` 1.0.3 → 1.11.23 so it matches `style.css` and the theme's CSS asset URLs cache-bust on this change (`wp_enqueue_style` uses that constant, not `style.css`'s `Version:`, which `tools/release.sh` bumps separately).

## [1.11.22] — 2026-05-03

- Drop PHP 7.4 from CI matrix and bump min PHP to 8.0 (shopos-core + shopos-theme headers, composer.json require, .github/workflows/ci.yml). Aligns CI to reality after Wave 2.3a-c baked PHP 8.0+ idioms (str_starts_with, str_contains) into shipped code; PHP 7.4 PHPUnit lane was de-facto failing.

## [1.10.8] — 2026-04-27

- MyAccount mobile nav: add min-width:0 to grid items + ul to fix horizontal-scroll trap (the pill row's flex-nowrap min-content was wider than the phone, growing the grid track and making the whole page scroll sideways instead of just the nav). Theme: add overflow-x:clip on html/body as a defensive guard against any future rogue element creating page-level horizontal scroll.

## [1.0.3] — 2026-04-27

- Hide the WooCommerce-injected `a.added_to_cart` "View cart" link that appears next to the add-to-cart button on shop / archive cards after a successful AJAX add. The cart is reachable from the header utilities, so the inline link is duplicate noise.

## [1.0.2] — 2026-04-27

- Removed the global `.shopos-theme a:hover { opacity: 0.75 }` rule entirely. The 1.0.1 attempt to scope it via `:has(img|...)` was in the built CSS but the dim was still showing in QA — most likely a browser cache or downstream selector winning. Killing the rule outright eliminates the failure mode. Components that genuinely want a text-link dim can opt in via the new `.shopos-ui-link--dim` class.

## [1.0.1] — 2026-04-26

- Fix: hovering an image-wrapping anchor (product card, category card, gallery thumb, slider card, etc.) no longer dims the image to 75% opacity — the global `.shopos-theme a:hover { opacity: 0.75 }` rule was matching every anchor, which read as a grey wash over the image. Now scoped via `:has(img|picture|svg|video|.cs-imgwrap|.attachment-woocommerce_thumbnail|[class*="image"])` so only text-link hovers dim. `:has()` is supported in all major browsers from late 2023 — no fallback needed for modern audiences.

## [1.0.0] — 2026-04-22

- Initial release as a child theme of Hello Elementor 3.4.x.
- Design tokens, typography, RTL stylesheet.
- Plugin-dependency bootstrap for ShopOS Core.
- WooCommerce theme support + gallery features.
- HPOS + Cart/Checkout Blocks compatibility declared.
