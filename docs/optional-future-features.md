# ShopOS Suite — Hard Audit & Forward Plan (Optional Future Features)

*2026-07-03 · CEO-lens strategy audit · no code, direction only.*
*Status: **proposal / optional** — nothing in this document is committed work. Per house rules, any wave below requires its own decisions-doc addendum, pre-flight, and explicit owner approval before code.*

---

## The verdict

The suite today is the **best browse layer an Elementor/Woo store can have** — validated on exactly one store. It is a world-class *product discovery* machine (search, facets with per-variation stock truth, sliders, quick view, swatches, hover galleries) sitting on a genuinely mature platform (module registry, flags, migrations, 664 tests, settings backup, rate-limited endpoints).

Measured against the stated ambition — *"build top-tier, converting ecommerce websites using only my plugin and theme"* — three things are false today:

1. **The suite owns the browse, not the buy.** Everything ShopOS renders ends at the add-to-cart click. PDP is half-owned; cart, checkout, thank-you, transactional email — the surfaces where conversion is actually won or lost (checkout abandonment runs ~70% industry-wide) — are stock WooCommerce or third-party.
2. **"Only my plugin and theme" is currently fiction.** shopos-theme ships **zero templates** — it is a token skin on Hello Elementor. Every page a shopper sees is assembled by Elementor Pro's Theme Builder, per store, by hand. Plus Style Kits for typography, WP Rocket assumed for caching, WPC plugins for bundles/FBT. Two of ~7 third-party dependencies have been eliminated so far (Filter Everything Pro, Advanced Woo Search).
3. **"Converting" is an unmeasurable claim.** Telemetry was skipped by decision (§4.8). No funnel data, no search-to-purchase attribution, no A/B capability — not even a zero-results search report.

The asset is real. The claim is not yet. The plan below closes that gap.

---

## Scorecard — the shopper journey vs. "top-tier"

| Funnel stage | What ShopOS owns | Grade |
|---|---|---|
| Search & discovery | In-house engine, ranked, bilingual, live dropdown | **A−** |
| Browse / PLP | Facets w/ variation-level stock truth, infinite scroll, quick view, card galleries, swatches | **A−** |
| Product page (PDP) | Buy box + swatches + cheapest-variation + restock — but no gallery, reviews, size guide, reassurance, sticky ATC, cross-sell | **C−** |
| Cart | Nothing | **F** |
| Checkout | Nothing | **F** |
| Post-purchase (thank-you, emails, tracking) | Nothing (restock email only) | **F** |
| Retention (wishlist, abandoned cart, capture) | Restock Notify only; wishlist was dropped | **D** |
| Trust & social proof (reviews, UGC, policies) | Nothing | **F** |
| Performance | shopos-digital is a real perf plugin; but no image pipeline/critical CSS, fixes are reactive | **B−** |
| SEO | Filtered-URL policy is excellent; no product schema; defers to SEO plugins | **C+** |
| Measurement | None, by decision | **F** |
| Operator/admin UX | Dashboard, tools, flags page, labels editor, reindex tools | **A−** |
| Design system | Tokens + RTL + a11y are top-decile; but one skin, accent presets exist in CSS **unwired to any control** | **B+** |
| Repeatability (stand up store #2) | No blueprint, no presets, no playbook, Elementor Pro hand-build per store | **F** |

The pattern: **A-grades above the fold, F-grades where the money changes hands.** Every quarter spent polishing browse now has lower marginal return than the worst grade on this card.

## Seven hard truths

1. **The suite optimizes the funnel's top and abandons its bottom.** A shopper who loves the filters still checks out through unstyled default Woo. One PDP/cart/checkout wave each will move revenue more than the last eight browse waves combined.
2. **Store #2 does not exist, even on paper.** PRODUCT.md literally describes arba4 (Hebrew-first fashion). Every QA cycle, bug, and design call has one tenant. Until a second store with a different vertical, locale, and brand runs the suite, "I build websites with this" is untested — and arba4-isms will keep fossilizing into core.
3. **The theme is not a theme — it's a skin.** Zero PHP templates, zero Woo template overrides. Header, footer, archive, PDP, checkout: all Elementor Pro, rebuilt by hand per site. The single biggest obstacle to both the "only my plugin and theme" claim and to repeatability.
4. **Trust surfaces are absent in a trust business.** Fashion buyers need reviews, size confidence, shipping/returns clarity. The brand promise is "a confident decision" — the suite currently provides confidence via typography alone.
5. **Discipline is eroding at the edges.** Three modules graduated with kill-switches hard-removed, hard rules overridden per wave, two deployed fixes with QA still pending, readme.txt stale at "1.8.3" while shipping 1.21.24+, and live conversion surfaces broken right now (on-sale facet dead on arba4 via the stale `onsale` lookup; shop-grid blowout root cause unconfirmed). Fine for one owned store; fatal habits for client stores.
6. **The business runs on one head, manual zips, and no staging.** No client-site update mechanism, no docs a hire or VA could onboard from. shopos-digital — the perf pillar — has 4 smoke tests behind a CI gate that cannot fail (`|| true`).
7. **"Internal-only" (§4.1) keeps getting re-litigated one feature at a time.** Quick View came back six weeks after being dropped; Wishlist will be next. The thesis needs restating once, formally, instead of eroding ad hoc.

---

## The strategic decision (make this first)

**Define the ShopOS Line:** *every surface between "shopper expresses intent" and "order confirmation email" is rendered by ShopOS; Elementor is for storytelling pages only.*

Concretely: keep §4.3 (no Gutenberg/FSE pivot — correct call), but change the theme's job from "skin whatever Elementor renders" to **owning the commerce templates outright** — PLP, PDP, cart, checkout, account, search results, transactional emails. Home pages, landing pages, campaign pages stay Elementor — ideally Elementor *free*, killing the per-site Pro + Style Kits dependency over time. This is the only version of "only my plugin and theme" that is both honest and achievable without building a page builder.

Also settle the identity question once: **this is an agency weapon, not a marketplace product** — which is what §4.1 already says and what "I build websites" means. If the suite is ever sold, licensing/support/kill-switch discipline become preconditions — park that as an explicit future decision instead of deciding it implicitly one feature at a time.

## The plan — Waves 9–14 (proposed)

### Wave 9 — Measure & Mend *(small, do first)*
Buy the scoreboard before investing quarters into conversion surfaces; close the open sores so the foundation is clean.
- **First-party event ledger** (partially reverses §4.8, needs a decisions addendum): privacy-light counters for the funnel already owned — search → click, zero-result terms, filter-apply → ATC, quick-view → ATC, PDP → ATC. A small KPI panel on the ShopOS dashboard. Not a GA clone; module-level proof.
- **Mend:** fix the arba4 on-sale lookup (a conversion surface is dark today), confirm the shop-grid-blowout root cause, close the two pending deployed-fix QAs, add a `/product-feed` auth token (the entire catalog + pricing is publicly scrapeable), fix the "8 modules / 1.8.3" metadata drift.

### Wave 10 — PDP ownership *(the highest-leverage conversion wave)*
Mostly assembling assets that already exist: the card-slider gallery component, the per-variation stock index, the token system, the labels-editor pattern.
- Theme-native PDP template: gallery/zoom reusing the unified card-slider, **sticky add-to-cart on mobile**, truthful availability messaging from the index ("last one in M" — truth, not urgency spam; on-brand), shipping/returns reassurance block (label-editable, per-store), size guide (per-category attachment), editorial cross-sell ("complete the look" — replaces WPC FBT/Bundles), recently viewed.
- **Reviews decision:** the one genuinely new subsystem. Recommendation: restyle + own Woo's native reviews (photos optional later) rather than build a review engine.

### Wave 11 — Cart
- Slide-in cart drawer (drawer primitives exist from QuickView/ShopFilters), free-shipping progress bar (truthful threshold math), one quiet in-cart cross-sell slot, sane coupon UX. Typically pays for itself; also retires cart-fragment jank shopos-digital currently works around.

### Wave 12 — Checkout & post-purchase
Don't rebuild checkout — **own its skin and its friction**: trim fields, Hebrew/RTL-correct validation, phone-first for the IL market, express-pay placement, trust elements at the payment step. Then the surfaces nobody owns today: a branded thank-you page with cross-sell + email capture, and **designed transactional emails** (RestockNotify's bilingual email infra generalizes — order emails are the most-opened brand surface a store has, and they are unstyled Woo defaults today).

### Wave 13 — Retention
- Abandoned-cart email (opt-in-aware), **Wishlist un-dropped** (retention + a back-in-stock audience feeding RestockNotify), tasteful email capture. All reusing the subscriber/email machinery already built.

### Wave 14 — The Factory *(runs parallel from Wave 11 onward)*
What makes it a business instead of a hobby with excellent test coverage.
- **Blueprint:** extend Settings_Tools export into a full store preset — modules, flags, labels, facet config, tokens — so store #2 starts configured, not blank.
- **Wire the design presets:** `.is-accent-gold/forest/ink` exist in CSS with no admin control; ship 2–3 named skins ("Quiet Boutique" is one brand, not the system) and a token-editing surface. Extend DESIGN.md to the money-path components (cart, checkout, email) — they have no design language yet.
- **Store #2, deliberately different:** EN-first/LTR, different vertical, no Elementor Pro. The permanent generalization test and demo showroom.
- **Ops:** staging environment, a client-site update mechanism (even a private update endpoint), a new-store playbook someone else could follow, shopos-digital maturity pass (make its CI actually fail, minimal real tests), and the image-pipeline/critical-CSS decision (absorb into shopos-digital, or formally accept WP Rocket as a blessed dependency — currently it's ambient and unacknowledged).

## What NOT to build (the discipline list)

No page builder. No headless/REST platform (§4.4 stands). No marketplace/multi-vendor. No subscriptions engine until a client pays for it. No AI/semantic search — a synonyms list + typo tolerance closes the gap at 1–10k SKUs. No payment/shipping/tax rebuilding, ever. No dark mode (the brand is ink on paper). No white-labeling until the "sell it" decision formally reopens. No more competitor-parity features admitted one at a time through the side door — if it's not on the funnel scorecard, it waits.

## The north star

> **A shopper goes from ad click to order confirmation touching only surfaces ShopOS renders — at a measured conversion rate the suite can prove — and store #2 can be stood up in under a week.**

Numbers to run this by: store conversion rate + funnel-stage rates (Wave 9 makes these visible), CWV p75 per template, count of third-party plugins per store (target: Woo + ShopOS ×3 + Elementor free + an SMTP service), and time-to-launch for a new store.

## Where to start

A decisions-doc addendum covering the three calls that unlock everything — the ShopOS Line (theme owns commerce templates), the partial reversal of §4.8 (first-party measurement), and re-opening Wishlist under Wave 13 — then Wave 9 (Measure & Mend) as the first PR-sized work. Each is a separate decision needing explicit owner approval.
