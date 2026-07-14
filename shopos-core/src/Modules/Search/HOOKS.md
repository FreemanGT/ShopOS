# Search — Public API

## Shipped hooks

### `shopos_core/search/max_results` (filter, since 1.21.40)
```php
apply_filters(
    'shopos_core/search/max_results',
    int $cap // default Search_Repository::MAX_RESULTS (500)
);
```
Filters the hard ceiling on how many product ids one search query returns.
`Search_Repository::effective_limit()` clamps both the "unlimited" results-page
call and any oversized request to this cap; a smaller explicit per-call limit
(e.g. the dropdown's `max_results`) still wins. The cap exists (since 1.21.19)
to stop one broad search hydrating the whole catalogue into memory — raise it
only if a legitimately large result set must be returned in a single query.

## Core hook used by this module

### `shopos_core/rate_limit_defaults` (filter, since 1.21.40)
```php
apply_filters(
    'shopos_core/rate_limit_defaults',
    array  $defaults, // [ 'max' => int hits, 'window' => int seconds ]
    string $bucket    // the rate-limit bucket id
);
```
Declared in `ShopOS\Core\Core\Security::rate_limit()` (core, not Search-owned)
and applied to **every** rate-limited endpoint, so a single listener can tune
the per-IP ceiling and window. Return the `$defaults` array with `max` and/or
`window` overridden; the `$bucket` argument lets a listener scope the change to
one endpoint. The public search dropdown (`search_query` bucket, default
`30`/`60`) is one such caller; Shop Filters (`shop_filters_query`) and QuickView
(`quick_view_product`) share the same default and filter.
