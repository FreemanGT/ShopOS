# Product

## Register

product

## Users

Bilingual Hebrew/English shoppers (Hebrew-first, RTL) browsing a WooCommerce
fashion/apparel storefront built on Elementor. They arrive to find a product,
compare variants (size, color), check availability, and buy — often on mobile.
Their context is consumer shopping, not work: they want to decide quickly and
with confidence, and any friction in search, filtering, or the buy box costs a
sale. Locale-aware copy and prices matter on both the He and En sides.

The secondary audience is the store owner/operator using the admin: module
toggles, facet config, search index, feed, and settings. Their job is to keep
the storefront tuned without touching code.

## Product Purpose

ShopOS is a two-package WordPress product — `shopos-theme` (Hello Elementor
child: tokens, typography, RTL, Woo template overrides) and `shopos-core` (all
business logic as independently togglable modules). It replaces a pile of
single-purpose plugins (variation swatches, restock notify, infinite scroll,
product feed, an external search plugin, shop filters, quick view, hover-swap)
with one coherent, in-house suite that shares a single design API.

Success is a storefront that feels custom-built and trustworthy rather than
assembled from off-the-shelf parts: search that returns the right products
instantly, filters that reflect real per-variation stock truth, product cards
that read clean, and a buy box that's unambiguous in both languages — all fast,
RTL-correct, and accessible. Data (subscribers, settings, search index, feed)
must never be orphaned, so the plugin works without the theme.

## Brand Personality

Editorial, quiet-luxury, confident. Monochrome ink-on-paper with typography
carrying the hierarchy; color is reserved and meaningful (a restrained accent,
plus semantic success/danger/info). Restraint over decoration. The voice is
calm and precise — labels and microcopy are spare, locale-aware, and never
shout. Three words: **restrained, precise, trustworthy.**

The interface should feel like a considered boutique, not a sale bin. Motion is
purposeful and subtle (the token system gates it behind reduced-motion).
Emphasis comes from weight, scale, and spacing — not from badges, gradients, or
noise.

## Anti-references

- **Generic Elementor/Woo defaults** — stock Elementor demo styling and default
  WooCommerce chrome: badge-heavy product cards, templated card grids, busy
  loops, off-the-shelf widget look. The whole point of the suite is to not look
  assembled from parts.
- **Loud discount-store clutter** — flashing sale callouts, urgency spam,
  competing CTAs, cramped grids.
- **Trend-chasing AI slop** — glassmorphism, gradient text, cream-everything,
  an uppercase tracked eyebrow above every section.

## Design Principles

- **Serve the decision, not the page.** Every storefront surface (search,
  filters, card, quick view, swatches, buy box) exists to move a shopper toward
  a confident purchase. Reduce friction; remove anything that doesn't help the
  decision.
- **Tokens are the single source of truth.** Modules consume
  `--shopos-ui-*` design tokens; visual changes happen in the token layer so the whole
  suite stays coherent. Don't hardcode what a token already expresses.
- **RTL and bilingual are first-class, not an afterthought.** Hebrew is the
  primary reading direction; every layout, label, and animation must be
  correct mirrored, and prices/labels are locale-aware on both sides.
- **Truthful state over optimistic state.** Stock, availability, price, and
  facet counts reflect real per-variation truth — a sold-out size drops out, a
  no-match search shows an honest empty result. Never show a product the shopper
  can't actually buy.
- **Restraint is the brand.** Emphasis through type, weight, and space before
  color or ornament. When in doubt, remove.

## Accessibility & Inclusion

Target **WCAG 2.1 AA**. Body text ≥4.5:1, large text ≥3:1; visible focus on all
interactive controls (filters, search combobox, quick-view drawer, swatches).
Keyboard-operable throughout. Full RTL correctness. Honor
`prefers-reduced-motion` (token durations collapse to 0) and `prefers-contrast`
(borders strengthen, shadows drop) — both already wired in the token layer.
Adequate touch targets for a mobile, consumer audience.
