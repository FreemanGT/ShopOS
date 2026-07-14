# Shop Filters — Public API

Shop Filters ships several additive hooks (the search-feed filters
`shopos_core/shop_filters/pre_search_product_ids` and
`…/search_product_ids`, the `…/panel_html` render filter, and the WooCommerce
`loop_shop_per_page` consumer). Their signatures are listed in
`tests/baseline-hooks.txt`. The tunability knob added in 1.21.40 is documented
below.

## Shipped hooks

### `shopos_core/shop_filters/facet_cache_ttl` (filter, since 1.21.40)
```php
apply_filters(
    'shopos_core/shop_filters/facet_cache_ttl',
    int $seconds // default 5 * MINUTE_IN_SECONDS (300)
);
```
Filters the time-to-live, in seconds, of the facet-response cache transient
(`shopos_core_sf_q_<hash>`) written by `Query_Builder::query()`. The cache is
a whole-catalog browse-state optimisation introduced in 1.21.24; the TTL only
backstops the event-driven index-revision invalidation, so raising it trades
freshness lag for fewer rebuilds and lowering it does the reverse. A value of
`0` makes the transient effectively non-expiring (WordPress treats `0` as no
expiry) — prefer a positive value.
