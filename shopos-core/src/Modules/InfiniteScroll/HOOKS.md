# Infinite Scroll — Public API

## Shipped hooks

### `shopos_core/infinite_scroll/root_margin` (filter, since 1.21.40)
```php
apply_filters(
    'shopos_core/infinite_scroll/root_margin',
    string $root_margin // default '800px 0px'
);
```
Filters the `IntersectionObserver` `rootMargin` — how far below the viewport
the sentinel triggers the next page prefetch. Larger values load earlier
(smoother, more requests); smaller values load later. The filtered value is
passed to the front-end via the localized settings payload (`CFG.rootMargin`)
and read once when the observer is constructed.

The selector-override (`shopos_core/infinite_scroll/selector`) and
render-bracket (`shopos_core/infinite_scroll/before_render`,
`shopos_core/infinite_scroll/after_render`) hooks also ship — see
[/docs/archive/wave-3.1-master-plan.md](../../../../docs/archive/wave-3.1-master-plan.md) §4-D7
and `tests/baseline-hooks.txt` for their signatures.

## Template overrides

Skeleton card markup is inline in `assets/js/infinite-scroll.js` (`makeSkeletonCard()`). No filter override exists today.
