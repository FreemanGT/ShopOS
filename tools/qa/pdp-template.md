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
- The harness pins loop determinism (related shuffle off, upsells orderby `title`) — required for every render-diff pair; without it WC randomizes related/upsell order per request and the zero-diff bar is unmeetable. The same pinning applies to the staging run if the Elementor PDP renders related products.

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
#    `product_pdp_on` is the committed flag-on key.
php tools/perf-budget.php check

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

## Results — §11.4 row 4 PR

_Filled by the row-4 template PR (Ruling 7 preamble: acceptance evidence is mandatory in the template PR itself)._
