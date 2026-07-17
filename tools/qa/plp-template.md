# PLP template QA — hook checklist + wp-env script (§11 Ruling 7.1 / 7.3)

The committed per-template acceptance artifact for §11.4 row 5 (`shopos-theme/templates/woo/archive-product.php` behind `shopos_core_theme_template_plp_enabled`, resolved by the shared theme loader `shopos-theme/inc/class-shopos-template-loader.php`). The harness (`tools/qa/hook-listener.php`) is shared with the PDP file; it scopes its census per surface (`surface: "plp"` in the report).

## Hook-firing checklist

The theme PLP template must render the standard WooCommerce product-archive hook stacks so QuickView, HoverSwap, InfiniteScroll's wrapper, ShopFilters' query bridge, and WC's own breadcrumb / result count / catalog ordering / pagination light up unaided (the §7.2/§6.2 precedent). Flag-on and flag-off callback censuses must be **identical** (the loader registers unconditionally; flag-off behavior lives inside the callback).

Counts below assume the reference wp-env catalog (6 published products, one page, no paging).

| Hook | Kind | Must fire | Notes |
|---|---|---|---|
| `woocommerce_before_main_content` | action | 1× | wrapper 10 / breadcrumb 20 / WC_Structured_Data website data |
| `woocommerce_shop_loop_header` | action | 1× | taxonomy archive header 10 |
| `woocommerce_before_shop_loop` | action | 1× | notices 10 / result_count 20 / catalog_ordering 30; InfiniteScroll's `.shopos-is-wrapper` opens here |
| `woocommerce_shop_loop` | action | 6× | once per product |
| `woocommerce_before_shop_loop_item` | action | 6× | loop link open |
| `woocommerce_before_shop_loop_item_title` | action | 6× | sale flash 10 + thumbnail 10 (HoverSwap rides here) |
| `woocommerce_shop_loop_item_title` | action | 6× | |
| `woocommerce_after_shop_loop_item_title` | action | 6× | rating 5 + price 10 |
| `woocommerce_after_shop_loop_item` | action | 6× | link close 5 + add-to-cart 10 (QuickView trigger 15) |
| `woocommerce_after_shop_loop` | action | 1× | pagination 10 |
| `woocommerce_no_products_found` | action | 0× | fires instead of the loop on empty results only |
| `woocommerce_after_main_content` | action | 1× | |
| `woocommerce_sidebar` | action | 1× | kept for hook parity (owner ask 8, 2026-07-17) — judge the visual at R7.5; claim-scoped `remove_action` is the recorded fallback |

## QA state pins (record with every run)

- All **flag-on** runs pin the only Ruling-10-legal production configuration: `theme.fonts_selfhost` ON as well.
- The shared harness's PDP determinism pins stay active (they are inert on archives — no related/upsell loops, no quantity inputs in the loop). **No archive-specific pin is committed**: WooCommerce's default catalog ordering (`menu_order title`) is deterministic. If a render-diff pair flakes anyway, the first candidates to pin for the window are `woocommerce_default_catalog_orderby` and any `?orderby=` request arg — budget a discovery loop (every PDP pin was found the hard way) and add the pin to the harness, both sides of the diff.
- **Search carve-out (binding, Ruling 2):** the loader must never claim `is_search()` requests *or* any product main query with a request search term (`$_GET['s']`) — the live search page is an Elementor product archive where `is_search()` is FALSE and the term lives only in the URL (`Results_Query.php`), while native product search has `is_shop()` AND `is_search()` both true. Both shapes are asserted below.

## wp-env gotchas (carried from pdp-template.md + row-5 additions)

1. **WooCommerce Coming Soon mode** intercepts logged-out storefront requests: the page 200s with the "coming soon" screen and zero archive hooks fire. Check `wp option get woocommerce_coming_soon` first; set `no` for the QA window and restore.
2. **Server identity**: pidfile's process must be the LISTENING pid's parent (`lsof -nP -iTCP:8888 -sTCP:LISTEN -t` → `ps -o ppid=`); kill squatters. No `?ver=` markers exist in this env.
3. **SQLite read flake**: assert from the RECORDED artifacts (census report `flags`/`surface` + saved snapshots), never an extra ad-hoc fetch.
4. **Assertion ordering**: a `wp eval` that constructs the loader and resolves the template writes its own log rows (fresh CLI process = fresh once-per-request guards). Clear `shopos_core_log` AFTER seam checks, BEFORE counting request-driven rows.
5. **`.shopos-is-wrapper` renders in BOTH flag states in wp-env** — WC's own `archive-product.php` (the Elementor-free fallback) fires `woocommerce_before_shop_loop` too. It is a flag-ON delta only against the staging-with-Pro Elementor archive render. Do not read it as a leak.
6. **Seed data**: the reference env ships one product_cat ('Uncategorized', 6 products) and zero tags. Taxonomy-archive QA needs `qa-cat` (3 products) and `qa-tag` seeded first; they stay seeded and are recorded here (shared env — declared QA window, restore-as-found otherwise).
7. **The `shop` perf path is `/?page_id=5`** (plain permalinks; valid while the env's shop page ID is 5). `shop`/`category` budgets were seeded at 1.38.0 and were NOT part of the chain-merge byte reseed (only `product`/`product_pdp_on` were) — pre-measure flags-off on the pre-PR checkout to separate seeded-bytes drift from a real PLP regression.
8. **Blueprint-import skew (recorded per owner ask 10b, 2026-07-17):** importing `template_plp = 1` onto a theme ≤ 1.13.x is a **silent no-op** — the loader itself ships with the template in 1.14.0, so nothing exists to log (Ruling 8's log-fallback wording holds only for PDP). The template then goes live the day the theme updates to ≥ 1.14.0 (fonts warning appears then if fonts are off). The flag remains an instant kill either way.
9. **FLIP PRECONDITION — ShopOS Digital ≥ 1.7.7 where Digital is active** (also in the flag's registry description): **shopos-digital's Query Optimizer force-set `no_found_rows` on ALL front main queries** (`qo_no_found_rows_front`, default ON, `pre_get_posts:100`) — `found_posts=0` ⇒ `wc_get_loop_prop('total')=0` ⇒ **every classic archive renders an empty grid** (item hooks 0×, no pagination), stock Woo template included, BOTH flag states. Cost a full debugging round (first suspect was the SQLite FOUND_ROWS emulation — disproven, it works). Fixed in shopos-digital **1.7.7** (product-archive main queries exempted; regression test). Any QA run against digital < 1.7.7 will reproduce the empty grid — check the digital version FIRST when the loop count is 0. `qo_remove_sort_order` (default OFF) would similarly distort classic catalog ordering on stores that enabled it — a flip-time checklist line.
10. **All three packages must be symlinked to the checkout under test** — this env symlinks core, theme, AND digital separately; a window that repoints only core+theme QAs stale digital code (how gotcha 9's fix initially appeared to not work).
11. **WP's canonical redirect drops `s` from `?page_id=5&s=test`** (Location: `/?post_type=product`) — the loader legitimately claims the redirect target. Test the stray-term case with the direct no-redirect URL `/?post_type=product&s=test` (refused, served by Woo's own template).
12. **The env's seeded products have no gallery images**, so HoverSwap's overlay legitimately renders nothing on the classic grid (its `woocommerce_before_shop_loop_item_title`:11 hook still fires — the R7.1 bar). WC's `product_visibility` base terms were also missing until this window re-ran `WC_Install::create_terms()` (unrelated to the empty-grid cause, but fixed while diagnosing).

## wp-env script

```sh
WP="wp --path=<wp-env docroot>"                     # the local env's WP-CLI invocation
ENV_URL="http://localhost:<port>"
SHOP_PATH="/?page_id=5"                             # canonical: 302s to /?post_type=product — snapshot the target
CAT_PATH="/?product_cat=qa-cat"

# 0. Declared QA window: point the wp-env symlinks at the row-5 checkout,
#    install the harness, disable coming-soon, seed taxonomy (gotcha 6),
#    verify server identity (gotcha 2).
cp tools/qa/hook-listener.php <wp-env docroot>/wp-content/mu-plugins/
$WP option update woocommerce_coming_soon no
$WP term create product_cat qa-cat; $WP term create product_tag qa-tag   # + assign 3 products

# 1. Flag-off baseline: census + snapshots on BOTH paths, on the pre-PR
#    checkout AND the PR checkout.
$WP shopos flags set theme.template_plp off
curl -sL "$ENV_URL$SHOP_PATH" > /dev/null
cp <wp-env docroot>/wp-content/shopos-qa-hook-report.json /tmp/plp-census-off-shop.json
bash tools/render-diff.sh snapshot "$ENV_URL$SHOP_PATH" /tmp/plp-off-shop.html
#    (repeat for CAT_PATH)

# 2. R7.3(a): flag-off render identity across the PR — pre vs post snapshots
#    byte-identical on both paths; zero Logger rows; callback census identical.
bash tools/render-diff.sh diff /tmp/plp-off-shop-pre.html /tmp/plp-off-shop-post.html

# 3. Flag-on: fonts first (Ruling 10), then the template flag.
$WP shopos flags set theme.fonts_selfhost on
$WP shopos flags set theme.template_plp on
curl -sL "$ENV_URL$SHOP_PATH" > /dev/null
cp <wp-env docroot>/wp-content/shopos-qa-hook-report.json /tmp/plp-census-on-shop.json
bash tools/render-diff.sh snapshot "$ENV_URL$SHOP_PATH" /tmp/plp-on-shop.html
#    (repeat for CAT_PATH)

# 4. Census: every checklist hook fires at its listed count (fired block);
#    callback census identical flag-on/off (loader registers unconditionally).
#    NOTE — unlike row 4, flag-on vs flag-off BYTE parity is NOT expected
#    (different render engines): assert hooks + census, record the flag-on
#    snapshot for the R7.5 screenshot review instead of diffing it.
diff <(jq -S '.census' /tmp/plp-census-off-shop.json) \
     <(jq -S '.census' /tmp/plp-census-on-shop.json)
jq '.fired' /tmp/plp-census-on-shop.json     # counts per the checklist table

# 5. Search carve-out (BINDING): with the flag still ON —
curl -sL "$ENV_URL/?s=test&post_type=product" > /dev/null
jq '.surface, .template' <wp-env docroot>/wp-content/shopos-qa-hook-report.json
#    → template must be WooCommerce's own archive-product.php (NOT the theme
#      copy). Also assert the pure seam over the full matrix (both search
#      shapes) via a fresh CLI process:
$WP eval 'var_dump(
  ShopOS_Theme_Template_Loader::should_claim_plp( false, true, true,  false, true,  "test" ), // native search: false
  ShopOS_Theme_Template_Loader::should_claim_plp( false, true, true,  false, false, "test" ), // Elementor-search shape: false
  ShopOS_Theme_Template_Loader::should_claim_plp( false, true, true,  false, false, "" ),     // shop: true
  ShopOS_Theme_Template_Loader::should_claim_plp( false, true, false, true,  false, "" )      // taxonomy: true
);'

# 6. Resolution seam via a fresh CLI process (gotcha 4 — run BEFORE log counts):
$WP eval '$l = new ShopOS_Theme_Template_Loader(); var_dump( $l->maybe_load_template( "/fallback.php" ) );'
#    flag on  → <theme>/templates/woo/archive-product.php   (context "plp")
#    flag off → "/fallback.php" untouched                    (context "")
#    flag on + theme file renamed away → "/fallback.php" + one 'info' row

# 7. R7.2 sibling asserts:
#    - shopos-shop-cols-mobile <style> present with the theme_mod set, flag ON and OFF
#    - QuickView trigger + HoverSwap overlay present in the flag-on HTML
#    - .shopos-is-wrapper present in BOTH states (gotcha 5)
#    - no shared-JS detector file in the PR diff; flag-OFF pass = the R7.3(a) run
#    - updater seam (PR #24 rider): $WP eval 'var_dump( has_filter( "pre_set_site_transient_update_themes", "shopos_theme_check_update" ) );'

# 8. Perf (R7.4): flag-on (fonts on) must pass shop_plp_on + category_plp_on
#    (byte-copies of the committed flag-off budgets — never --seed).
#    Flag-state semantics: the *_plp_on rows are the gate during flag-ON runs;
#    the base shop/category rows are the gate during flag-OFF runs — the
#    script probes every row against whichever state is live, so read each
#    row in its own state. When the owner-approved byte reseed happens
#    (R7.4), seed the *_plp_on rows from FLAG-ON measurements (and note
#    --seed overwrites the WHOLE file from the current state — hand-restore
#    the other state's rows afterward).
$WP shopos flags set perf.probe on
php tools/perf-budget.php "$ENV_URL" tools/perf-budgets.json

# 9. Ruling-10 warning path: template_plp on + fonts_selfhost off for one
#    request → exactly one 'warning' row naming fonts_selfhost; once per request.
$WP shopos flags set theme.fonts_selfhost off
curl -sL "$ENV_URL$SHOP_PATH" > /dev/null
$WP option get shopos_core_log --format=json | jq '.[0]'

# 10. Restore the env as found: flags off, coming-soon restored, harness removed,
#     symlinks back. qa-cat/qa-tag stay seeded (gotcha 6 records them).
$WP shopos flags set theme.template_plp off
$WP shopos flags set theme.fonts_selfhost off
$WP shopos flags set perf.probe off
rm <wp-env docroot>/wp-content/mu-plugins/hook-listener.php
```

Sibling checks recorded alongside (Ruling 7.2): InfiniteScroll's selector lists already match classic markup (`ul.products` / `li.product` / `nav.woocommerce-pagination a.next` are existing fallback entries) — the row-5 PR touches **no detector file**; if a container mis-detection ever surfaces flag-on, the recorded fix is the server-side `shopos_core/infinite_scroll/selector` filter gated on `ShopOS_Theme_Template_Loader::context() === 'plp'`, never a JS-list reorder (the 1.23.0→1.24.8 failure shape). The `Results_Query` search exclusion is asserted in step 5.

## Results — §11.4 row 5 PR (run 2026-07-17, wp-env, stack #24→#25→#26→row-5; core 1.43.0→1.44.0 / theme 1.13.0→1.14.0 / digital 1.7.6→1.7.7)

All runs against identity-verified fresh servers (gotcha 2), coming-soon disabled for the window and restored, all three packages symlinked to the checkout under test (gotcha 10), env restored as found (qa-cat 3 products / qa-tag seeded and kept, gotcha 6). Pre-PR side = the #26 tip (digital 1.7.7 — the fix the flag-off render needs to be measurable at all, gotcha 9).

| Check | Result |
|---|---|
| R7.3(a) flag-off render identity across the PR | **byte-identical** on both paths (`/?page_id=5` shop 57,154 normalized bytes; `/?product_cat=qa-cat`) |
| Flag-off census across the PR | identical (fired counts + full callback census, both paths) |
| Flag-off log rows | none |
| R7.1 hook checklist flag-on | all 13 checklist hooks at expected counts — shop: wrappers 1×, item hooks 6×; qa-cat: item hooks 3×; `no_products_found` 0×. Callback census **identical flag-on/off** (unconditional registration verified). Server-side flag reads in the report (`template_plp: true`, `fonts_selfhost: true`) |
| Flag-on markup | `.shopos-ui-plp` wrapper + `shopos-plp` CSS handle present flag-on, absent flag-off; ShopFilters panel rendered in the template slot (45 `shopos-sf` markers); QuickView triggers in the loop (11 markers); `.shopos-is-wrapper` present in BOTH states as predicted (gotcha 5); HoverSwap hook fires, overlay data-gated (gotcha 12) |
| Search carve-out (Ruling 2, BINDING) | flag ON: `/?s=test&post_type=product` AND `/?post_type=product&s=test` both served by **Woo's own** archive-product.php (report `template` field), never the theme copy; `context()` stays `''`. Redirect shape recorded (gotcha 11). Pure-seam matrix green in CI (`ThemeTemplateLoaderTest::test_should_claim_plp_matrix`, incl. the is_search()=false+term Elementor-search shape) and re-run live via fresh CLI (4/4) |
| Resolution seam (fresh CLI, archive query context) | flag on → theme `templates/woo/archive-product.php`, `context()==='plp'`; flag off → `$template` untouched, context `''`; template renamed away → untouched + exactly **1** info row across 2 calls (instance guard) |
| Ruling-10 warning (plp on / fonts off) | exactly 1 warning row per request (1 request → 1, 2 requests → 2), names `fonts_selfhost`, still resolves the theme template |
| R7.4 perf | queries / render-ms / mem **within budget in both flag states** on `shop`, `shop_plp_on`, `category`, `category_plp_on` (flag-on adds zero query overrun incl. the restored count query). **Bytes: pre-existing drift** — flag-OFF measures 57,169 vs the 50,605 budget on BOTH sides of the PR (1.38.0-seeded baselines vs drifted env content; the row-4 shape). Flag-on bytes 61,915 (+4,746 = panel + classic grid markup + css link — the honest structural delta). NOT reseeded (R7.4 — owner call) |
| R7.2 detectors + siblings | no shared-JS detector file in the stack diff (verbatim classic markup already matched by existing selector fallbacks); flag-OFF pass = the R7.3(a) run; mobile-cols `shopos-shop-cols-mobile` style present with the theme_mod set in BOTH flag states (theme_mod reset after); theme updater filter registered (PR #24 rider assert) |
| shopos-digital 1.7.7 (PR #26) | verified live: pre-fix `no_found_rows=true` at `pre_get_posts:100` → `found_posts=0`/`post_count=6` → empty grid; post-fix `SQL_CALC_FOUND_ROWS` restored, `found_posts=6`, grid renders — both flag states |
| R7.5 owner screenshots + RTL | **pending — pre-flip acceptance gate** (with fonts_selfhost ON), before any live flag-on; the flag-on content delta (Elementor archive content replaced — ProductSlider grid, CategorySlider, storytelling sections) is acknowledged as owner ask 9 and judged there |
| Staging-with-Pro render-diff | **deferred to the flip-time checklist** — owner ask 7 (re-affirming the row-4 decision-3a shape); wp-env covers the Elementor-free fallback path only |
