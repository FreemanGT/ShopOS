# Feature flags

Every roadmap item that changes runtime behavior ships behind a feature flag (CLAUDE.md hard rule #1). This doc defines the convention.

## Naming

```
shopos_core_<module>_<feature>_enabled
```

- `<module>` — snake_case module slug (e.g. `variation_swatches`).
- `<feature>` — snake_case feature slug (e.g. `tooltip`, `auto_color`).
- ASCII only. No spaces, no hyphens.

Example: `shopos_core_variation_swatches_tooltip_enabled`.

## Default

Flags default to `false`. Code paths must assume OFF until the option is explicitly set. Callers therefore never need to seed the option on activation.

## Reading a flag

```php
use ShopOS\Core\Core\Feature_Flags;

if ( Feature_Flags::is_enabled( 'variation_swatches', 'tooltip' ) ) {
    // ...new behavior...
}
```

`is_enabled()` returns a strict `bool`. Boolean parsing is explicit:

| Stored value | Result |
|---|---|
| missing, `false`, `0`, `'0'`, `''`, `'false'`, `'no'`, `'off'` | `false` |
| `true`, `1`, `'1'`, `'true'`, `'yes'`, `'on'` | `true` |
| anything else (e.g. `'banana'`) | `false` |

This avoids the `(bool) 'false' === true` footgun and matches WP-CLI's usual `0`/`1` convention.

## Where to call it

At the **top of feature entry points**, before any side effect (enqueue, hook registration, output). The OFF path must produce byte-identical output to the pre-flag baseline.

## Enabling and disabling

```bash
# Enable:
wp option update shopos_core_variation_swatches_tooltip_enabled 1

# Rollback (disable):
wp option update shopos_core_variation_swatches_tooltip_enabled 0
```

The rollback command is what every PR's "Rollback plan" section refers to.

## Admin page

`ShopOS → Feature Flags` (added Wave 5.1, 1.11.44) lists every flag from `Feature_Flags::registry()` as a checkbox grouped by module, each with its description, the equivalent `wp option update …` line, the introducing version, and a "shared switch" marker where one flag drives several sub-features. Saving posts to `admin-post.php` (action `shopos_save_feature_flags`, nonce + `manage_woocommerce`) and writes each registry flag to `1` or `0` — a flag's checkbox absent from the POST means "off". It writes nothing else; it does not run module activation routines or flush rewrites.

A flag whose effective state is **forced by a `shopos_core/feature_flag/...` filter** renders disabled, with a note — the DB option can't override a code filter, so the page reflects the effective state rather than letting you write a value the filter ignores. The save handler skips those flags too.

The page is unconditional infra (like the Dashboard and Tools pages); it is not itself behind a flag.

## Programmatic override (filter)

Each flag exposes a dynamic filter mirroring WordPress's `option_{$option}` pattern:

```
shopos_core/feature_flag/{$module}/{$feature}
```

Useful for forcing a flag ON in `wp-config.php`-loaded mu-plugins (staging, dev) without DB writes:

```php
add_filter( 'shopos_core/feature_flag/variation_swatches/tooltip', '__return_true' );
```

Listeners receive `( bool $enabled, string $module, string $feature )`.

## Active flags

The list below is the long-form reference. The short-form canonical list — flag, label, one-line "gates" description, introducing version, and a "shared" marker — lives in code as `ShopOS\Core\Core\Feature_Flags::registry()`, which also drives the **ShopOS → Feature Flags** admin page (see below). When the two disagree, the registry is authoritative; a PHPUnit test (`FeatureFlagsAdminTest`) keeps the registry in sync with the actual `is_enabled()` call sites in `src/`.

| Flag | Default | Gates |
|---|---|---|
| `shopos_core_variation_swatches_auto_color_enabled` | `false` | Wave 2.2 / 4d (1.11.27) **+ 4e (1.11.28)** — shared flag. Flag ON activates the auto-color sampler pipeline: `Color_Sampler` (modal-with-edge-filter sampling via GD with Imagick auto-upgrade, stores hex as variation post-meta `_shopos_core_vs_sampled_color`) + `Sampler_Scheduler` (sample-on-save via `woocommerce_save_product_variation`, pre-warm on flag-flip via batched WP-Cron — first-of-kind cron precedent for shopos-core, default batch size 50, filterable via `shopos_core/variation_swatches/sampler_prewarm_batch_size`, self-reschedules until queue empty). Cache invalidation handlers cover `_thumbnail_id` change, variation deletion, and attachment deletion. Hot-path lazy fallback retained as safety net for variations created via direct DB writes / import tools that bypass the save hook. **4d alone produces no frontend behavior change** — the pipeline runs and caches but no swatch reads the cached value until 4e wires it into the render path. Shared-flag design is intentional: shipping 4d with the flag ON in production before 4e is harmless. Flag OFF: scheduler listeners are still registered (cheap option-read at hook entry) but bail before doing real work; sampler is never called. |
| `shopos_core_variation_swatches_tooltip_enabled` | `false` | Wave 2.2 / 4c (1.11.25). Flag ON: hover tooltip on color and image swatches in both shop picker and PDP buy-box. Default tooltip text is the term name; admins can override per-term via the `shopos_core_variation_swatches_term_tooltip_text` term-meta key (input added to the existing taxonomy term-edit screen, next to color and image fields). Pure CSS via `data-tooltip` attribute + `:hover::after`, mirroring the existing `data-default-text` pattern; no JS. Text-button swatches deliberately skipped (label is inline). Per-option `tt` field added to the prepared-data payload (always emitted when flag is ON, empty when no override). Flag flip is implicit in the prepared-data transient cache key. Flag OFF: no `tt` field emitted, no `data-tooltip` attributes rendered, no admin UI. Byte-identical to pre-1.11.25. |
| `shopos_core_variation_swatches_image_swatches_enabled` | `false` | Wave 2.2 / 4b (1.11.24). Flag ON: per-attribute-term swatch image upload (Iconic/WPC pattern). Each term gets a `shopos_core_variation_swatches_term_image_id` term-meta entry storing the attachment ID. Image wins over color when both are set; color is the fallback. The legacy WC term-edit screen gains a media-uploader pair (button + thumbnail preview + remove link), `wp_enqueue_media()` enqueued only on `'edit-tags.php' / 'term.php'` screens. Both shop picker (`legacy/templates/shop-variation-pick.php`) and PDP buy-box (`legacy/templates/variation-buy-box.php`) render image branches when an option's `img` URL is non-empty; class names `.shopos-shop-pick__opt-img` and `.shopos-swatch__img`, round shape hardcoded to match existing color circles. One additive filter: `shopos_core/variation_swatches/term_image_url`. Flag OFF: image-meta is never read or written, term-edit screen has no upload UI, payload omits the `img` field per option (byte-identical to pre-1.11.24). Flag flip is implicit in the prepared-data transient cache key (same trick as 4f). |
| `shopos_core_variation_swatches_card_image_swap_enabled` | `false` | Wave 2.2 / 4f (1.11.23). Flag ON: shop-listing swatch click swaps the card's main image to the matching variation's image; shopper stays on the listing (no nav, no PDP, no Quick View). The PHP-side payload (`prepare_product_data()` in `legacy/class-archive.php`) emits per-variation `image_src` / `image_srcset` / `image_sizes`; the JS-side handler (`refreshCardImage()` in `shopos-shop-swatches.js`) finds the card image via the `ShopOSShopVS.cardImageSelector` localize value and swaps in/out, restoring originals when the picker returns to an unresolved state. Two filters: `shopos_core/variation_swatches/card_image_selector` (CSS selector) and `shopos_core/variation_swatches/card_image_payload` (per-variation image payload). Flag OFF: payload byte-identical to pre-1.11.23 (no image fields), JS no-ops. Flag flip is implicit in the prepared-data transient cache key, so flipping the option immediately rebuilds payloads without an explicit cache-bust. |
| `shopos_core_variation_swatches_bundle_compat_enabled` | `false` | Wave 4.5 (1.11.40). Flag gates three ShopOS-shipped code paths: (1) the VariationSwatches add-to-cart JS payload-build branch — flag-ON forwards all form fields via `serializeArray()` to WC's `wc-ajax=add_to_cart` endpoint (with a denylist of WP/WC nonces), so WPC Product Bundles (`woosb-ids-*`) and WPC FBT (`woobt_ids`) hidden inputs reach the cart; flag-OFF retains the legacy `product_id`/`quantity`/`attribute_*` whitelist payload byte-identical to pre-1.11.40; (2) the `window.ShopOSCoreVSFlags = { bundleCompat: <bool> }` JS global emitted via `Module::inject_feature_flags()` on `wp_enqueue_scripts` priority 10001 (the boolean itself reflects flag state); (3) the `woobt_added_to_cart` → `wc_fragment_refresh` document-body bridge for FBT's "Add All" path, bound at DOM-ready only when the global is true. Separately and not gated (Hard Rule #1 additive exception): a single `do_action( 'woocommerce_before_add_to_cart_button' )` in `legacy/templates/variation-buy-box.php` inside `.shopos-actions`. Standard WC hook, no ShopOS-shipped listener attaches; the injection point exists regardless of flag state so bundle/FBT plugins can inject their hidden inputs (without it, plugins have nowhere to inject and flag-ON forwarding has nothing to forward). Single-line legacy edit, Hard Rule #3 exception approved at `docs/roadmap.md` off-roadmap Wave 4.5 entry, mirrors the same hook position in `legacy/templates/simple-buy-box.php`. |

## Retired flags

| Flag | Retired in | What happened |
|---|---|---|
| `shopos_core_tools_settings_import_enabled` | 1.23.0 flag-graduation sweep | Import form always available under ShopOS → Tools (capability + nonce remain the gate). |
| `shopos_core_sliders_advanced_controls_enabled` | 1.23.0 flag-graduation sweep | Autoplay / loop / indicator controls always registered on both slider widgets; ProductSlider grid mode still suppresses them via the `display_mode` gate. |
| `shopos_core_cheapest_variation_strategy_enabled` | 1.23.0 flag-graduation sweep | Strategy selector always active (default setting `cheapest` = the legacy path). |
| `shopos_core_infinite_scroll_trigger_modes_enabled` | 1.23.0 flag-graduation sweep | Trigger-mode / history-mode / container-selector settings + the wrapper render path always active (defaults = legacy observer behaviour); `triggerModesEnabled` stays `true` in the JS payload for shipped JS. |
| `shopos_core_restock_notify_csv_export_enabled` | 1.23.0 flag-graduation sweep | Export Subscribers submenu + CSV handler always registered on admin requests. |
| `shopos_core_quick_view_frontend_enabled` | 1.23.0 flag-graduation sweep | QuickView storefront + AJAX surface boots whenever the module is enabled — the module toggle is the kill-switch. |
| `shopos_core_product_page_coupon_notice_enabled` / `_stock_urgency_enabled` / `_layout_enabled` | 1.23.0 flag-graduation sweep | All three ProductPage surfaces (coupon notice, stock-urgency badge, designed-layout template takeover) boot whenever the module is enabled — the module toggle is the kill-switch. |
| `shopos_core_variation_swatches_settings_hub_enabled` | Wave 2.2 / 4g (1.11.45) — introduced 4a (1.11.21) | The ShopOS → Variation Swatches page graduated to being the **sole** editing surface for the 14 swatch settings: `Module::settings_schema()` now always returns the keys (no flag gate) and `Settings_Reader::get()` always prefers the new `shopos_core_variation_swatches_*` key with the legacy `shopos_vs_*` key as a permanent fallback (§4.5). The legacy WooCommerce → Settings → Products → "Shop swatches" section is soft-deprecated (kept registered so the URL resolves; renders a one-line "moved" notice). The `shopos_core_variation_swatches_settings_hub_enabled` option is **not deleted** but is now ignored — except by the one-shot 1.11.45 re-sync migration (`Migrations::resync_variation_swatches_settings_from_legacy()`), which reads it once to decide whether to copy current `shopos_vs_*` values onto the new keys (done on sites where the flag was OFF — the default; skipped where it was ON). |

## Settings export/import notes (Wave 0.3)

- Envelope shape: `{ "version": 1, "exported_at": "<ISO>", "site_url": "<url>", "options": { ... } }`. Only `version: 1` is accepted; every other version is rejected with no write and no backup.
- `site_url` is **decorative metadata only** — used to label the export and the backup rows in the admin UI. It is not validated and not enforced. Cross-site imports (staging → prod) work as long as both sides run a compatible plugin version. If a future operational concern needs enforcement, a follow-up PR adds an opt-in filter; do not enforce by default.
- **Import is not atomic.** Writes happen in deterministic key-sorted order. On the first `update_option()` returning false, the loop halts; options written before the failure point remain. The auto-backup created pre-write is the rollback path — restore from it.
- Auto-backup fires **after validation, before first write**. Failed/rejected imports never consume a backup slot. Last 5 are kept in a non-autoloaded option; oldest is dropped on overflow.

## What doesn't need a flag

Per CLAUDE.md hard rule #1, the additive exception covers:

- A new hook or filter (no behavior change without a listener).
- A new CSS variable that has a backward-compatible fallback.
- A new helper class with no callers (added in Wave 0.2 — purely infrastructure).

Anything else: gate it.
