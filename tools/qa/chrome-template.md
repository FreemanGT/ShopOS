# Chrome template QA — header/footer (§11-B surface 1, §11 Ruling 7)

Per-template acceptance artifact for the theme-owned header/footer chrome behind
`theme.template_chrome` (`shopos-theme/header.php` + `footer.php`). Shares the
`tools/qa/hook-listener.php` harness with pdp/plp.

## Mechanism (differs from PDP/PLP)

Chrome does **not** use the `template_include` loader. WordPress loads the child
theme's `header.php`/`footer.php` via `get_header()`/`get_footer()` directly:

- **Flag OFF** → `header.php`/`footer.php` `require get_template_directory() . '/header.php'|'/footer.php'`
  (the Hello Elementor **parent** chrome — Elementor Pro `header`/`footer` location,
  falling back to Hello's default). Byte-identical to today (Ruling 6 require-parent passthrough).
- **Flag ON** → the ShopOS classic chrome. `header.php` opens `#content`; `footer.php` closes it.

Single pinned flag read: `ShopOS_Theme::chrome_enabled()` (FQCN — the theme is unnamespaced).

## Acceptance checklist

| Check | How |
|---|---|
| Flag-OFF passthrough byte-identical | `render-diff.sh compare` a 200 page vs a pre-chrome baseline (with the determinism harness) → exit 0 |
| Flag-ON renders chrome | header `.shopos-chrome` + brand + cart(+count); footer `.shopos-chrome--footer` + copyright; single `#content`, single `<!doctype>`/`</body>` |
| wp_head/wp_body_open fire | flag-ON page carries woo/elementor assets (modules light up) |
| Assets gated | `shopos-chrome.css`/`.js` present only flag-ON |
| Widget area gated | `shopos-footer` sidebar registered only flag-ON |
| Menu/toggle | render when a menu is assigned to `menu-1` (guarded by `has_nav_menu`) |
| RTL (he_IL) | logical properties — verify header/footer mirror; owner screenshot |

## wp-env script

```sh
ENV=~/shopos-wp-env; wpc() { "$ENV/shopos-env.sh" wp "$@"; }   # NOTE: define a fn — zsh won't word-split "$ENV/shopos-env.sh wp"
BASE=http://127.0.0.1:8888
cp tools/qa/hook-listener.php "$ENV/wp-content/mu-plugins/"     # determinism harness

# Flag-OFF passthrough identity (use a 200 page; ?page_id=5 shop 302-redirects — curl -L or use a product/home URL)
wpc shopos flags set theme.template_chrome off
bash tools/render-diff.sh compare "$BASE/?product=<slug>" /path/to/pre-chrome-baseline   # exit 0

# Flag-ON render
wpc shopos flags set theme.template_chrome on
curl -sL "$BASE/?product=<slug>" | grep -c 'class="shopos-chrome"'      # header
curl -sL "$BASE/?product=<slug>" | grep -c 'shopos-chrome--footer'      # footer

# Restore
wpc shopos flags set theme.template_chrome off
rm "$ENV/wp-content/mu-plugins/hook-listener.php"
```

## Results — surface-1 PR (run 2026-07-20, wp-env, Elementor Pro 4.1.3, coming_soon=no)

| Check | Result |
|---|---|
| Flag-OFF passthrough (shop `?page_id=5`) | **byte-identical** (render-diff exit 0) |
| Flag-OFF passthrough (product, harness on) | **byte-identical** (render-diff exit 0) |
| Flag-ON chrome (product, home — HTTP 200) | header `.shopos-chrome` ×1, brand ×1, cart ×3 (link/icon/count=0), footer `.shopos-chrome--footer` ×1, copyright present, `#content` ×1, `<!doctype>`/`</body>` ×1 each |
| Flag-ON wp_head fired | 124 woo/elementor asset hits |
| Flag-ON assets | `shopos-chrome.css` + `.js` enqueued |
| Menu/toggle | absent — wp-env has no menu on `menu-1` (correct `has_nav_menu` guard); assign a header menu to verify nav+toggle |
| PHPUnit | `ThemeChromeTest` (`@group theme`) green; suite 1020/3007 |
| Owner screenshots + RTL(he_IL) | **pending — pre-flip acceptance gate** (wp-env is en_US) |
