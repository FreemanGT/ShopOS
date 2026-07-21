# ShopOS — TODO

The actionable work queue. Grouped, roughly in priority order. Each item still runs the
[CLAUDE.md](CLAUDE.md) contract (pre-flight → plan → owner approval → flag → tests → ledger).
Strategy context is in [roadmap.md](roadmap.md); module specs in [Modules.md](Modules.md).

**Last updated:** 2026-07-22

---

## A. Build queue — unbuilt modules
Lead path is the revenue path: **Side Cart → Checkout**. Full specs + rationale in
[Modules.md](Modules.md#build-roadmap-to-20-modules).

- [x] **Side Cart** — slide-out drawer, free-ship meter, coupon, recommendations *(shipped 1.55.0, 2026-07-22; default OFF)*
- [ ] **Checkout** — WC checkout replacement; IL city autocomplete, live validation, upsell *(large)*
- [x] **Bundle Deals** — volume/tiered, curated, mix-&-match, BOGO *(shipped 1.46.0, 2026-07-19; default OFF)*
- [ ] **Product Reviews** — rating summary, verified badge, photos, voting, store replies
- [ ] **Product Badges** — auto + manual; shapes/animations, per-corner + mobile, bilingual *(light)*
- [ ] **Flash Sale Banner** — per-timezone countdown, placements, auto-hide *(light)*
- [ ] **Fortune Wheel** — spin-to-win + lead capture *(light-med)*
- [ ] **Bulk Price Editor** — admin tool: %/fixed, rounding, targeting, preview + rollback
- [ ] **Custom Email Templates** — branded per-status WC emails, dynamic vars, preview + test send
- [ ] **Advanced Add-to-Cart** — *consolidation*, not net-new; wire the existing buy-box modules + buy-now / AJAX add / reserve timer / sticky mobile bar

## B. ShopOS Line — flip the built flags (staging gates)
The theme-owned PDP/PLP/fonts are built but **default OFF**. Before flipping on a live store
(decisions [§11-B](decisions-2026-04-28.md)):

- [ ] Flip `fonts_selfhost` **first** (Ruling 10 ordering), then the template flags
- [x] Render-diff evidence, wp-env-with-Pro (2026-07-20): **template_pdp** ON == fonts-ON byte-identical +
      hook-census identical + resolves the theme copy; **template_plp** ON renders the classic `ul.products`
      grid + ShopFilters slot, 0 PHP errors (structural change by design). Snapshots in scratchpad `flip-evidence/`.
- [x] Google-fonts preconnect: root-caused to **shopos-digital `add_preconnect`** (not the kit). Fix = enable
      digital `fe_disable_elementor_gfonts` at flip (zero code, verified). See [BUGS.md](BUGS.md).
- [ ] `tools/perf-budget.php` on flag-on: queries/render-ms marginal + bytes over = **pre-existing wp-env
      content drift** (documented; reseed is the owner's R7.4 call) — not flip regressions. Re-run authoritative on staging.
- [ ] RTL pass + owner screenshots on the flag-on render — **still the human gate** (wp-env is `en_US`; real store `he_IL`)
- [ ] Then: ≥30 days flag-on spanning ≥1 theme release, zero rollbacks (record flip dates in decisions state line)

## B2. ShopOS v2 design migration — live-store follow-ons
The v2 migration (branch `feat/design-v2-migration`, core 1.54.0 + theme 1.21.0) is code-complete +
tests green; these blocks (DESIGN.md §20 / #9, #19) need the owner's live store to QA before finishing:

- [ ] **iconbtn adoption** — the slider `.cs-arrow` (per-widget Elementor CSS-var size/radius controls),
      card-slider/gallery arrows, ShopFilters close/chip (text-glyph + refined variant), and the chrome
      hamburger/cart CSS-glyph icons still carry bespoke styles. Reclass to `.shopos-ui-iconbtn` where the
      live render + operator controls allow (needs search-in-header + configured sliders/filters to verify).
- [ ] **PLP/PDP restyle depth** — the default WC shop grid/buttons + WC `span.onsale` stay Elementor-kit/WC-
      owned (§11.4 row-5 keeps WC `ul.products`); decide whether the ShopOS Line PLP should also restyle WC
      buttons → `radius-xs` and the sale flash → the badge primitive (§12.3), or leave WC-owned.
- [ ] **RTL (he_IL) pass** on the v2 render — Hebrew Label Voice, zeroed tracking, mirrored iconbtn/hero
      (wp-env is en_US; real store is he_IL). Roll into the §11-B RTL gate.
- [ ] **Publish decision** — Stable tag held at 1.52.0. Bump to 1.54.0 + `publish.sh`/manifest when ready.

## C. §11-B deferred surfaces (after the gate clears)
Theme-owned classic PHP templates; flag names **not yet minted**. Do not start until §11-B unlocks
(PDP+PLP proven + theme PHPUnit/CI lane exists + store #2 committed or owner re-affirms).

- [x] Theme PHPUnit/CI lane — **DONE 2026-07-20** (decisions §11-B Ruling 7.8, Option A): `@group theme`
      in core's suite + named "ShopOS Theme lane" CI step (`php -l` + `phpunit --group theme`, 34 tests green).
      Each surface below adds its own `@group theme` `*TemplateTest`.
- Owner **overrode the §11-B calendar gate 2026-07-20** (30-day/store-#2 not met); building **one plan-first PR per surface**:
  - [x] **Header/footer chrome** — DONE 2026-07-20 (core 1.47.0 + theme 1.16.0, flag `theme.template_chrome`,
        default OFF; passthrough byte-identical + chrome renders, wp-env-verified). Pre-flip: owner screenshots + RTL.
  - [x] **Cart page** — DONE 2026-07-20 (core 1.48.0 + theme 1.17.0, flag `theme.template_cart`, default OFF).
        Whole cart page theme-owned via a flag-gated `woocommerce_locate_template` redirect (7 forked `cart/*` templates,
        hooks/nonces verbatim); flag-off = WC default byte-identical (Ruling 6). `CartTemplateTest` (`@group theme`) green.
        Block-cart stores need a block→shortcode content-migration (Ruling 9). Pre-flip: render-diff + owner screenshots + RTL.
  - [x] **My Account** — DONE 2026-07-20 (core 1.50.0 + theme 1.18.0, flag `theme.template_account`, default OFF).
        Reuses the cart's `woocommerce_locate_template` filter, generalized to the shared `locate_woo_template`; two
        structural templates forked (`my-account.php` shell + `navigation.php` rail), content + auth/payment forms
        CSS-skinned (WC keeps the nonces). `AccountTemplateTest` (`@group theme`) green. Pre-flip: render-diff + owner screenshots + RTL.
  - [x] **Checkout** — DONE 2026-07-20 (core 1.52.0 + theme 1.20.0, flag `theme.style_checkout`, default OFF).
        Skin-ONLY (Ruling 9 resolved-as-moot 2026-07-20): NO forked templates — `is_checkout()`-gated `shopos-checkout.css`
        restyles WC's own markup, WC keeps every nonce/gateway. Works on block + shortcode, no per-store migration.
        `CheckoutSkinTest` (`@group theme`) green. Pre-flip: render-diff + owner screenshots + RTL.
  - [ ] Search-results template · [ ] Transactional emails *(**Core-side, not the theme** — decisions ownership map)*

## D. Open audit follow-ups (2026-07-03 audit, B-5)
- [x] Uninstall **created-vs-cleaned matrix** + **dead-code cross-check** — swept 2026-07-20,
      findings in [audit-b5-2026-07-20.md](audit-b5-2026-07-20.md). Fixes below (each its own PR):
  - [x] **HIGH** RestockNotify `shopos_restock_subscribers` PII table dropped on uninstall — **1.56.0** (owner-approved PII deletion 2026-07-22; + off-prefix `notification_log` option)
  - [x] Core uninstall completeness — **1.56.0**: `_shopos_core_vs_sampled_color` postmeta (new VariationSwatches `on_uninstall`), core-owned options (`shopos_core_log`/`settings_backups`/`design_*`/`productfeed_last_generated`). Feed/stock-fix crons were **already** cleared in `on_deactivate` (runs before Delete→uninstall) — no code needed.
  - [ ] shopos-digital uninstall transient **prefix drift** (`fd_*` → `shopos_digital_%`) — no live transient is cleaned *(next: digital 1.7.9)*
  - [ ] Dead localize/CSS cleanup: ShopFilters + BundleDeals + Search + RestockNotify payloads, `notification_log` writer, orphan `.shopos-toast` rules *(next: core dead-code PR)*

## E. Ops & hygiene
- [ ] **Re-upload core** to the stranded stores on 1.44.3–1.45.0 (one-time; see [BUGS.md](BUGS.md))
- [ ] **Breakpoint unification** across the 8 modules (768px ×5 files etc.) — PR-01 declared the
      canon in `--shopos-ui-*`; moving modules onto it is a follow-up sweep, not a blocker
- [ ] Stand up **store #2** (different vertical/locale, no Elementor Pro) — unblocks §11-B and is
      the permanent single-store-ism test
