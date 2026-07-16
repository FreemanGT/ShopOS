# PDP template QA — hook checklist + wp-env script (§11 Ruling 7.1 / 7.3)

The committed per-template acceptance artifact for §11.4 row 4 (`shopos-theme/templates/woo/single-product.php` behind `shopos_core_theme_template_pdp_enabled`). The PLP (row 5) gets its own file; the harness (`tools/qa/hook-listener.php`) is shared.

## Hook-firing checklist

The theme PDP template must render the standard WooCommerce single-product hook stacks so QuickView, RestockNotify, VariationSwatches, structured data, and ProductPage's own coupon/urgency widgets light up unaided (the §7.2/§6.2 precedent). Flag-on and flag-off censuses must be **identical**.

| Hook | Kind | Must fire | Notes |
|---|---|---|---|
| `woocommerce_before_single_product` | action | 1× | notices + structured data register here |
| `woocommerce_before_single_product_summary` | action | 1× | sale flash (10) + gallery (20) |
| `woocommerce_single_product_summary` | action | 1× | title 5 / price 10 / excerpt 20 / add-to-cart 30 / urgency 35 / trust 36 / addl-info 38 / meta 40 |
| `woocommerce_after_single_product_summary` | action | 1× | WC tabs/upsells/related detached at takeover; third-party attachments still fire |
| `woocommerce_after_single_product` | action | 1× | |
| `woocommerce_product_tabs` | filter | ≥1× | drives the accordion; `additional_information` dropped there (surfaced under the buy box instead) |

## QA state pins (record with every run)

- All **flag-on** runs pin the only Ruling-10-legal production configuration: `theme.fonts_selfhost` ON as well.
- The harness pins render determinism — required for every render-diff pair; without ALL of these the zero-diff bar is unmeetable by design (found the hard way): (a) related-product selection shuffle OFF, (b) related/upsell **display** orderby pinned (`orderby=rand` on the output args is independent of the selection shuffle), (c) the `uniqid()` quantity input id pinned per product. The same pinning applies to the staging run if the Elementor PDP renders related products.

## wp-env gotchas (each cost a debugging round — check BEFORE trusting any run)

1. **WooCommerce Coming Soon mode** intercepts logged-out storefront requests (store-pages-only mode included): the page 200s with the "coming soon" screen, zero product hooks fire, and the census looks plausible while testing nothing. Check `wp option get woocommerce_coming_soon` first; set `no` for the QA window and restore.
2. **Server identity**: `shopos-env.sh start` can silently lose the port to a stale php process from an earlier session while your curls "work" against old code. Before every run set: pidfile's process must be the LISTENING pid's parent (`lsof -nP -iTCP:8888 -sTCP:LISTEN -t` → `ps -o ppid=`), and kill any squatter. Asset `?ver=` markers are NOT available in this env (version query args are stripped).
3. **SQLite read flake**: under rapid CLI-write→HTTP-read alternation an occasional request reads a just-written option as absent. Assert from the RECORDED artifacts (the census report's `flags` block + the saved snapshot), never from an extra ad-hoc fetch.
4. **Assertion ordering**: a `wp eval` that constructs `Template_Loader` and calls `template_file()` writes its own log rows (fresh CLI process = fresh once-per-request guard). Clear `shopos_core_log` AFTER seam checks, BEFORE counting request-driven rows.

## wp-env script

```sh
WP="wp --path=<wp-env docroot>"                     # the local env's WP-CLI invocation
ENV_URL="http://localhost:<port>"
PDP_PATH="/?product=<test-product-slug>"

# 0. Install the harness for the QA window (remove at the end).
cp tools/qa/hook-listener.php <wp-env docroot>/wp-content/mu-plugins/

# 1. Flag-off baseline: census + render snapshot.
$WP shopos flags set theme.template_pdp off
$WP shopos flags set theme.fonts_selfhost off
curl -s "$ENV_URL$PDP_PATH" > /dev/null
cp <wp-env docroot>/wp-content/shopos-qa-hook-report.json /tmp/pdp-census-off.json
bash tools/render-diff.sh snapshot "$ENV_URL$PDP_PATH" /tmp/pdp-off.html

# 2. Flag-off render identity across the PR (run once on the pre-PR checkout,
#    once on the PR checkout): the two flag-off snapshots must be identical.
bash tools/render-diff.sh diff /tmp/pdp-off-pre.html /tmp/pdp-off-post.html

# 3. Flag-on: fonts first (Ruling 10), then the template flag.
$WP shopos flags set theme.fonts_selfhost on
$WP shopos flags set theme.template_pdp on
curl -s "$ENV_URL$PDP_PATH" > /dev/null
cp <wp-env docroot>/wp-content/shopos-qa-hook-report.json /tmp/pdp-census-on.json
bash tools/render-diff.sh snapshot "$ENV_URL$PDP_PATH" /tmp/pdp-on.html

# 4. Census parity: fired counts + callback census identical on and off.
diff <(jq -S '.fired,.census' /tmp/pdp-census-off.json) \
     <(jq -S '.fired,.census' /tmp/pdp-census-on.json)

# 5. Render parity: the verbatim theme copy must render byte-identically.
#    NOTE: fonts_selfhost changes <head> (fonts CSS + suppressed kit Google
#    Fonts) — for THIS diff take the flag-off snapshot with fonts already on,
#    or diff with template_pdp as the only changed flag.
bash tools/render-diff.sh diff /tmp/pdp-off-fonts-on.html /tmp/pdp-on.html

# 6. Resolution seams via a fresh CLI process (opcache-safe QA — wp-env gotcha):
$WP eval 'var_dump( ( new \ShopOS\Core\Modules\ProductPage\Template_Loader( new \ShopOS\Core\Modules\ProductPage\Module() ) )->template_file() );'
#    flag on  → <theme>/templates/woo/single-product.php
#    flag off → <core>/src/Modules/ProductPage/templates/single-product.php
#    flag on + theme file renamed away → module copy + one 'info' Logger row

# 7. Perf (R7.4): flag-on (fonts on) must pass the existing `product` budget;
#    `product_pdp_on` is the committed flag-on key. (Interface: arg 1 is the
#    base URL — an earlier revision of this doc recorded a bare `check`
#    argument, which the script would treat as the base URL and fail.)
wp shopos flags set perf.probe on
php tools/perf-budget.php "$ENV_URL" tools/perf-budgets.json

# 8. Warning path (Ruling 10): template_pdp on + fonts_selfhost off for one
#    request → exactly one 'warning' row in shopos_core_log; once per request.
$WP shopos flags set theme.fonts_selfhost off
curl -s "$ENV_URL$PDP_PATH" > /dev/null
$WP option get shopos_core_log --format=json | jq '.[0]'

# 9. Restore the env exactly as found: flags back off, harness removed.
$WP shopos flags set theme.template_pdp off
$WP shopos flags set theme.fonts_selfhost off
rm <wp-env docroot>/wp-content/mu-plugins/hook-listener.php
```

Sibling checks recorded alongside (Ruling 7.2): no shared-JS detector file changes in the PR (verbatim markup); flag-OFF pass against the current render recorded in Results.

## Results — §11.4 row 4 PR (run 2026-07-16, wp-env, core 1.42.2→1.43.0 / theme 1.11.31→1.13.0)

All runs against identity-verified fresh servers (gotcha 2), determinism pins active, coming-soon disabled for the window, env restored as found afterwards. Baseline side = the pre-PR stack tip (#20, core 1.42.2 + theme 1.11.31).

| Check | Result |
|---|---|
| R7.3(a) flag-off render identity across the PR | **byte-identical** (`render-diff` on `/?product=test-product-6`, 63,007 normalized bytes both sides) |
| Flag-off census across the PR | identical (fired counts + full callback census) |
| Flag-off log rows | none |
| R7.3(b) flag-on vs flag-off render (fonts ON both sides, `template_pdp` the only delta) | **byte-identical** — the verbatim theme copy proves parity exactly |
| R7.1 hook checklist flag-on | all 6 checklist hooks fired 1× with callback census identical to flag-off; server-side flag reads confirmed in the census report (`template_pdp: true`, `fonts_selfhost: true`) |
| Resolution seam (fresh CLI) | flag on → `themes/shopos-theme/templates/woo/single-product.php`; flag off → module copy; override rung untouched |
| Clean flag-on log | zero rows |
| Ruling-10 warning (pdp on / fonts off) | exactly 1 `warning` row per request (1 request → 1, 2 requests → 2); resolves the theme copy regardless |
| Fallback (theme copy absent, flag on) | resolves the module copy, renders byte-identical to flag-off, exactly 1 `info` row per request |
| R7.4 perf (fonts+pdp ON, probe on) | queries/render-ms/mem all within the `product` budget (232/113/18). **Bytes: 61,855 vs the 53,429 budget — a PRE-EXISTING overage**: pre-PR flags-off measures the identical 62,246 bytes as row-4 flags-off (row-4 delta = 0; the raw-byte difference between the two numbers is the fonts flag's head delta, kit-Google-Fonts-print suppressed vs fonts link added). The budget was seeded at 1.38.0 and wp-env content has drifted since; NOT reseeded (R7.4 — reseeding needs an owner call), flagged in the row-4 PR |
| R7.2 detectors | no shared-JS detector file touched in the PR (verbatim markup); flag-OFF pass = the R7.3(a) run |
| R7.5 owner screenshots + RTL | **pending — pre-flip acceptance gate**, before any live flag-on (with fonts_selfhost ON) |
| Staging-with-Pro render-diff | **deferred to the flip-time checklist** — owner decision 3a, 2026-07-16 (staging access TBD; recorded row-4 DONE-bar deviation) |
