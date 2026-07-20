# Search-results template QA — hook checklist + wp-env script (§11 Ruling 7.1 / 7.3)

The committed per-template acceptance artifact for §11-B **surface 4**
(`shopos-theme/templates/woo/search-results.php` behind
`shopos_core_theme_template_search_enabled`, resolved by the shared theme
loader's search claim arm in `shopos-theme/inc/class-shopos-template-loader.php`).
The harness (`tools/qa/hook-listener.php`) is shared with the PDP/PLP files; it
scopes its census per surface (`surface: "search"` in the report).

This surface is the **mirror-positive of the PLP loader's search refusal** — the
same product-archive hook stack as [plp-template.md](plp-template.md), claimed
only when the request carries search. **Read plp-template.md first**: the hook
checklist table, the shared harness, and gotchas 1–12 all carry over unchanged.
This doc records only the search deltas.

## What differs from PLP

- **The Search module already owns the query.** By the time this template
  renders, `Results_Query` (shopos-core, always-on) has short-circuited the
  native search WP_Query via `pre_get_posts:5` — `post__in` = the engine-ranked
  ids, `orderby = post__in` — and fed the Shop Filters facet universe
  (`shopos_core/shop_filters/*_search_product_ids`). The template only reskins
  the surrounding archive; it must not re-query. Verify the grid order matches
  the engine ranking, not `menu_order`.
- **Results heading.** The template prints `Search results for “<term>”` from
  `ShopOS_Theme_Template_Loader::request_search_term()` (the request, not the
  query var — the term never reaches the query vars on the Elementor shape).
  Assert the term is `esc_html`'d and that the generic `Search results` heading
  renders for the unparseable-payload sentinels (`?s[]=x`, `?s=%3Cb%3E`).
- **Empty state.** A genuine no-match makes `Results_Query::plan_ids` force
  `post__in = [0]` ⇒ zero products ⇒ `woocommerce_no_products_found` fires
  (WC's own "no products matched your search" copy). This is the expected
  non-empty-index empty result, NOT a regression — distinguish it from the
  digital `no_found_rows` empty-grid failure (PLP gotcha 9).

## Claim shape (BINDING — the Ruling 2 carve-out, positive half)

`should_claim_search` claims exactly what `should_claim_plp` refuses on the
search axis, and **only for product archives**:

| Request shape | `is_shop` | `is_search` | `?s=` term | Claimed? |
|---|---|---|---|---|
| Native product search (`/?post_type=product&s=x`) | ✓ | ✓ | (redirected) | **yes** |
| Elementor search page (product archive, term in URL) | ✓ | ✗ | ✓ | **yes** |
| Product taxonomy + search | ✗ (tax ✓) | ✓/✗ | ✓/✗ | **yes** |
| Bare shop / taxonomy, no search | ✓ | ✗ | ✗ | no → **PLP arm** |
| Generic post/page search (`/?s=x`, no product archive) | ✗ | ✓ | ✓ | **no** (Results_Query refuses it too) |
| Admin / not main query | — | — | — | no |

The two full-page arms are **disjoint by construction** (asserted exhaustively in
CI: `SearchTemplateTest::test_plp_and_search_claims_are_disjoint`).

## wp-env script (search deltas over plp-template.md)

Run the full PLP script for the shared harness/gotchas; the search-specific
steps:

```sh
WP="wp --path=<wp-env docroot>"
ENV_URL="http://localhost:<port>"

# Flag-on: fonts first (Ruling 10), then the search flag.
$WP shopos flags set theme.fonts_selfhost on
$WP shopos flags set theme.template_search on

# 1. Native product search — the canonical URL avoids WP's canonical redirect
#    dropping `s` (PLP gotcha 11).
curl -sL "$ENV_URL/?post_type=product&s=hoodie" > /dev/null
jq '.surface, .template, .fired' <wp-env docroot>/wp-content/shopos-qa-hook-report.json
#    → surface "search"; template = <theme>/templates/woo/search-results.php;
#      archive hook stack fires per plp-template.md's checklist table.

# 2. Grid is engine-ranked, not menu_order: the Search module owns the query.
#    Confirm Results_Query took over (active) and the facet feed fired.
$WP eval 'var_dump( has_action( "pre_get_posts" ), has_filter( "shopos_core/shop_filters/search_product_ids" ) );'

# 3. Empty result (non-empty index, no match) → no_products_found, not a leak.
curl -sL "$ENV_URL/?post_type=product&s=zzzznomatch" > /dev/null
jq '.fired.woocommerce_no_products_found, .fired.woocommerce_shop_loop' \
   <wp-env docroot>/wp-content/shopos-qa-hook-report.json   # 1 and 0

# 4. Generic (non-product) search is NEVER claimed — the theme search template
#    must not take over the site's post/page search.
curl -sL "$ENV_URL/?s=hoodie" > /dev/null
jq '.surface, .template' <wp-env docroot>/wp-content/shopos-qa-hook-report.json  # "", the current render

# 5. Resolution + claim seams via a fresh CLI process (PLP gotcha 4 — before log counts):
$WP eval 'var_dump(
  ShopOS_Theme_Template_Loader::should_claim_search( false, true, true,  false, true,  "" ),      // native search: true
  ShopOS_Theme_Template_Loader::should_claim_search( false, true, true,  false, false, "hoodie" ),// Elementor shape: true
  ShopOS_Theme_Template_Loader::should_claim_search( false, true, false, false, true,  "hoodie" ),// generic search: false
  ShopOS_Theme_Template_Loader::should_claim_search( false, true, true,  false, false, "" )       // bare shop (PLP): false
);'
$WP eval '$l = new ShopOS_Theme_Template_Loader(); $_GET["s"]="hoodie"; $GLOBALS["fr_page_type"]="shop";
          var_dump( $l->maybe_load_template( "/fallback.php" ) );'   # theme search-results.php + context "search"

# 6. Ruling-10 warning: template_search on + fonts_selfhost off → exactly one
#    'warning' row per request naming fonts_selfhost AND template_search.
$WP shopos flags set theme.fonts_selfhost off
curl -sL "$ENV_URL/?post_type=product&s=hoodie" > /dev/null
$WP option get shopos_core_log --format=json | jq '.[0]'

# 7. Flag-off render identity across the PR (R7.3a): pre vs post snapshots of a
#    search URL byte-identical; zero Logger rows; callback census identical.
$WP shopos flags set theme.template_search off
# (snapshot /?post_type=product&s=hoodie pre and post, diff via tools/render-diff.sh)

# Restore the env as found (flags off, coming-soon restored, harness removed).
```

## Notes carried to the flip-time checklist

- **R7.5 owner screenshots + RTL** (fonts_selfhost ON) — pre-flip acceptance
  gate, before any live flag-on. The flag-on delta replaces the Elementor
  search-page content; judged there (owner ask 9 shape, as PLP).
- **Staging-with-Pro render-diff** — deferred to flip-time; wp-env covers the
  Elementor-free fallback path only.
- **Route-shape verification (the one real risk, plan §11-B):** confirm the live
  store's search route actually satisfies `is_shop()`/product-archive as the
  Ruling 2 carve-out asserts (vs arriving as a bare `is_search()`), on the real
  store's permalink + Elementor search-page setup, before flag-on. The claim
  matrix covers all shapes, but which shape the live store hits is env-specific.

## Results — §11-B surface 4 PR

_Pending first wp-env run (this PR ships the surface flag-off; CI theme lane green:
`SearchTemplateTest` 9 tests, `phpunit --group theme` 73/296)._
