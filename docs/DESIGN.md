---
name: ShopOS
description: Editorial quiet-luxury storefront system for a bilingual He/En WooCommerce shop on Elementor.
colors:
  ink: "#1b1b1b"
  ink-soft: "#3a3a3a"
  ink-muted: "#6b6b6b"
  paper: "#ffffff"
  paper-soft: "#faf9f7"
  paper-dim: "#f1efea"
  sand: "#e9e4db"
  hairline: "#e6e6e2"
  gold: "#b68a3a"
  success: "#0e7c66"
  danger: "#b11226"
  warning: "#a8630a"
  info: "#225e8f"
typography:
  display:
    fontFamily: "Assistant, Heebo, Rubik, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "clamp(2.5rem, 1.9rem + 2.6vw, 4rem)"
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: "-0.01em"
  headline:
    fontFamily: "Assistant, Heebo, Rubik, sans-serif"
    fontSize: "clamp(1.9rem, 1.55rem + 1.5vw, 2.75rem)"
    fontWeight: 600
    lineHeight: 1.18
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Assistant, Heebo, Rubik, sans-serif"
    fontSize: "clamp(1.1rem, 1rem + 0.4vw, 1.25rem)"
    fontWeight: 500
    lineHeight: 1.35
    letterSpacing: "normal"
  body:
    fontFamily: "Heebo, Rubik, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif"
    fontSize: "clamp(0.95rem, 0.9rem + 0.3vw, 1.05rem)"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  label:
    fontFamily: "Heebo, Rubik, sans-serif"
    fontSize: "clamp(0.72rem, 0.68rem + 0.2vw, 0.78rem)"
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: "0.04em"
rounded:
  xs: "2px"
  sm: "4px"
  md: "6px"
  lg: "12px"
  xl: "20px"
  pill: "999px"
spacing:
  xxs: "2px"
  xs: "4px"
  sm: "8px"
  md: "16px"
  lg: "24px"
  xl: "40px"
  xxl: "64px"
components:
  button-primary:
    backgroundColor: "{colors.ink}"
    textColor: "{colors.paper}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "0 24px"
    height: "48px"
  button-ghost:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.sm}"
    padding: "0 24px"
    height: "48px"
  input:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.sm}"
    padding: "0 16px"
    height: "44px"
  badge:
    backgroundColor: "{colors.paper-soft}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.pill}"
    padding: "0 8px"
    height: "22px"
  card:
    backgroundColor: "{colors.paper}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
    padding: "16px"
---

# Design System: ShopOS

## 1. Overview

**Creative North Star: "The Quiet Boutique"**

ShopOS dresses a WooCommerce storefront the way a considered boutique dresses its
floor: ink on paper, type carrying the hierarchy, and color held in reserve until
it means something. The system is a single design API — a token layer
(`--shopos-ui-*`) that every module (cards, swatches, search, filters, quick view) reads
from, so the whole shop moves as one. The default voice is near-monochrome: a deep
ink (#1b1b1b) on white and warm-neutral papers, with emphasis built from weight,
scale, and spacing rather than decoration. It is bilingual and RTL-first — Hebrew
is the primary reading direction, and every rule here must hold mirrored.

What it rejects is just as defining. This is **not** stock Elementor demo styling
or default WooCommerce chrome: no badge-encrusted product cards, no templated
icon-heading-text grids, no busy loop widgets, nothing that reads as assembled
from off-the-shelf parts. It is not a discount bin — no flashing sale callouts,
no urgency spam, no competing CTAs. And it does not chase the AI aesthetic of the
moment: no glassmorphism, no gradient text, no cream-everything, no tracked
uppercase eyebrow stacked above every section.

Depth is conveyed tonally. Three paper steps (paper → paper-soft → paper-dim) plus
hairline rules do most of the layering; shadows are a quiet secondary, present only
as a soft response to state. The result should feel calm, precise, and trustworthy
— a place where a shopper makes a confident decision without being shouted at.

**Key Characteristics:**
- Ink-on-paper monochrome by default; color is rare and meaningful
- Type-led hierarchy — weight, scale, and space before ornament
- Tonal layering over shadow; hairlines do real work
- RTL-first and bilingual; mirrored-correct, locale-aware
- One token layer drives every module — change the token, not the component

## 2. Colors

A near-monochrome palette of warm-neutral papers and deep ink, with one optional
metallic accent and a tight set of semantic colors. Color is the exception, not the
field.

### Primary
- **Ink** (#1b1b1b): The default everything — body text, headings, the primary
  button fill, active states, swatch outlines. In this system ink *is* the accent.
- **Ink Soft** (#3a3a3a): Secondary text, form labels, the calmer end of the
  text ramp when full ink is too heavy.
- **Ink Muted** (#6b6b6b): Meta text, SKUs, captions, the dimmed price. The floor
  of the text ramp — never lighter than this on paper, or body contrast fails.

### Secondary
- **Boutique Gold** (#b68a3a): The opt-in brand accent (`.is-accent-gold`). A
  muted, antique gold for CTAs and active states on sites that want warmth. Never
  used as text on its own; always a fill behind dark ink.

### Neutral
- **Paper** (#ffffff): The base surface — page and card background.
- **Paper Soft** (#faf9f7): The first tonal step up — badges, hovered ghost
  buttons, subtle section banding.
- **Paper Dim** (#f1efea): The second tonal step — media wells, skeleton base,
  recessed panels. This is how depth is built.
- **Sand** (#e9e4db): The warm-neutral deep step for larger calm fields.
- **Hairline** (#e6e6e2): Borders, dividers, input strokes. The system's most-used
  structural color — it does the work shadows would do elsewhere.

### Tertiary (semantic only)
- **Success** (#0e7c66): In-stock, restock confirmations, positive state.
- **Danger** (#b11226): Out-of-stock, errors, destructive actions, sale-from price `<del>`.
- **Warning** (#a8630a): Low-stock and caution states.
- **Info** (#225e8f): Neutral informational notices.

### Named Rules
**The Ink-First Rule.** The primary accent is ink, not a color. Reach for ink
before reaching for hue. Gold and forest are per-site opt-in themes
(`.is-accent-*`) — color on demand, never color by default.

**The Semantic-Only Color Rule.** Green, red, amber, and blue appear *only* to
report state (stock, error, caution, info). They are forbidden as decoration. If a
color isn't telling the shopper something true, it doesn't belong on the page.

## 3. Typography

**Display Font:** Assistant (with Heebo / Rubik fallback)
**Body Font:** Heebo (with Rubik, then system-sans fallback)
**Label/Mono Font:** Heebo for labels; `ui-monospace` stack for code only

**Character:** Two Hebrew-native humanist sans families on a weight-and-role
contrast axis — Assistant carries headings with a touch more character, Heebo
holds the calm, highly-legible body. Both render Hebrew and Latin cleanly, which
is the whole point: one type system, two scripts, no seams. (Where Elementor +
Style Kits define global typography, those vars are authoritative; these are the
fallback stacks.)

### Hierarchy
- **Display** (600, clamp 2.5→4rem / ~60px ceiling, line-height 1.18): Page H1
  and hero headlines. Tracking −0.01em in Latin, flattened to 0 in Hebrew.
- **Headline** (600, clamp 1.9→2.75rem, line-height 1.18): Section H2.
- **Title** (500, clamp 1.1→1.25rem, line-height 1.35): Card titles, sub-section
  H4, the buy-box product name.
- **Body** (400, clamp ~0.95→1.05rem / ~16px, line-height 1.55): Running text and
  product copy. Cap measure at 65–75ch.
- **Label** (600, ~12px, letter-spacing 0.04em, UPPERCASE): Buttons, badges, the
  H6 eyebrow, filter chips. The system's only uppercase voice.

### Named Rules
**The Hebrew Flat-Tracking Rule.** Negative and positive letter-spacing are zeroed
under `:lang(he)` — Hebrew letterforms don't take tracking. Latin gets the −0.01em
display tightening and the 0.04em label spacing; Hebrew gets neither. Never ship a
tracked Hebrew heading.

**The One Uppercase Voice Rule.** Uppercase is reserved for the Label role
(buttons, badges, H6). Headings and body are never uppercased. No tracked
all-caps eyebrow above sections — that's the off-the-shelf tell.

## 4. Elevation

Depth is **tonal first, shadow second**. The three-step paper ramp (paper →
paper-soft → paper-dim) plus hairline borders carry most of the layering; a media
well is paper-dim, a raised badge is paper-soft, a divider is a hairline. Shadows
exist but stay quiet — they're a *response to state*, not a resting property. A
card sits nearly flat (a 1–2px ambient shadow) and only lifts (translateY −2px +
a deeper shadow) on hover. Under `prefers-contrast: more`, shadows drop to zero
entirely and borders take over, which is the truest statement of the system's
intent: structure is drawn with lines and tone, not haze.

### Shadow Vocabulary
- **xs** (`0 1px 1px rgba(0,0,0,0.04)`): Barely-there seat for resting chips/badges.
- **sm** (`0 1px 2px rgba(0,0,0,0.06)`): Card at rest. Almost subliminal.
- **md** (`0 8px 24px rgba(0,0,0,0.08)`): Card hover, search/quick-view panels.
- **lg** (`0 24px 48px rgba(0,0,0,0.12)`): Drawers, modals.
- **xl** (`0 40px 80px rgba(0,0,0,0.18)`): Full-screen overlays only.

### Named Rules
**The Flat-At-Rest Rule.** Surfaces rest tonally flat. Shadow appears as a reaction
to hover, focus, or float (panel/drawer/modal) — never as a default decoration on
a static card. If it isn't reacting to state, it shouldn't cast.

**The Hairline-Does-The-Work Rule.** Before adding a shadow to separate two
surfaces, try a 1px hairline (#e6e6e2) or a tonal step. Shadows are the last
resort for separation, not the first.

## 5. Components

### Buttons
- **Shape:** Gently squared (4px radius, `--shopos-ui-button-radius`). Never pill, never
  hard-zero. 48px tall (44–48px is the touch floor).
- **Primary:** Ink fill (#1b1b1b) with paper text, full ink border, UPPERCASE
  Label type with 0.04em tracking, padding `0 24px`. The single confident action.
- **Hover / Focus:** Lifts `translateY(-1px)`; focus-visible draws a 2px ink
  outline at 2px offset. No color shift on the default ink variant.
- **Ghost:** Transparent fill, ink text, hairline border; on hover the border
  goes ink and the fill goes paper-soft. The secondary action.
- **Link:** No box — underlined ink text, no uppercase, no tracking. Tertiary.

### Chips (filter facets)
- **Style:** Low-key, tonal. Unselected reads as hairline-bordered paper; the
  Label type sets them apart. Built for the Shop Filters facet panel.
- **State:** Selected flips to ink fill / paper text (the badge--accent treatment).

### Cards (product)
- **Corner Style:** 6px radius (`--shopos-ui-card-radius`) — softer than buttons, never lg.
- **Background:** Paper body; media well is paper-dim (#f1efea) at a 4/5 aspect.
- **Shadow Strategy:** sm at rest, md on hover (see Elevation). Image scales 1.03
  on hover; the card lifts −2px.
- **Border:** Transparent at rest → 1px hairline on hover. The border *arrives*
  with the lift; it doesn't sit there.
- **Internal Padding:** 16px (`--shopos-ui-space-md`); body is a tight 4px-gap stack.

### Inputs / Fields
- **Style:** Paper fill, 1px hairline stroke, 4px radius, 44px tall, 16px inline pad.
- **Focus:** Border goes ink (accent) plus a 2px ink focus ring; outline removed in
  favor of the ring. Hover deepens the border to ink-soft.
- **Error:** `aria-invalid="true"` / `.is-error` turns the border danger-red.

### Navigation / Links
- **Style:** Inherit ink color; underline at 1px thickness, 3px offset. No global
  hover-opacity (it greyed out image content) — text links opt into the dim effect
  via `.shopos-ui-link--dim`; image anchors stay crisp.

### Signature Component — Live Search Panel
The storefront's most distinctive pattern. A body-appended results panel
(`.shopos-search-panel`), JS-positioned under the field (anchored) or dropped as a
full-width sheet below the header (overlay; full-screen on mobile). White surface,
1px hairline, md shadow, 0.5rem bottom radius, 70vh scroll cap. Each
`.shopos-search-item` is a 44px thumbnail + title / SKU / price row, hairline-divided,
with a paper-soft hover and `aria-selected` keyboard state — a proper combobox
listbox. Locale-aware, server-rendered `price_html` (with `<del>` for sale-from).
This is the bar every other module's interaction quality is measured against.

## 6. Do's and Don'ts

### Do:
- **Do** consume `--shopos-ui-*` tokens. Change a value in `shopos-tokens.css` and let it
  propagate; never hardcode what a token already expresses.
- **Do** keep ink as the default accent. Reach for `--shopos-ui-color-ink` before any hue.
- **Do** build depth from the paper ramp and hairlines first; add a shadow only as
  a response to state.
- **Do** zero letter-spacing under `:lang(he)`; test every layout, label, and
  animation mirrored in RTL.
- **Do** hold body text at ink-muted (#6b6b6b) or darker on paper, and verify
  ≥4.5:1 — including placeholders. Bump toward ink if it's even close.
- **Do** reserve uppercase for the Label role (buttons, badges, H6).

### Don't:
- **Don't** ship stock Elementor demo styling or default WooCommerce chrome —
  badge-encrusted cards, templated icon-heading-text grids, busy loop widgets.
  Nothing that reads as assembled from off-the-shelf parts.
- **Don't** turn the shop into a discount bin: no flashing sale callouts, no
  urgency spam, no competing CTAs.
- **Don't** use glassmorphism, gradient text, cream-everything, or a tracked
  uppercase eyebrow above every section. That's the AI tell.
- **Don't** use color as decoration. Green/red/amber/blue report state only.
- **Don't** use `border-left`/`border-right` >1px as a colored stripe on cards,
  list items, or callouts. Use full borders, tonal fills, or nothing.
- **Don't** rest a shadow on a static card or apply a global hover-opacity over
  image content — it greys out the products.
- **Don't** track Hebrew headings, and don't let a long heading word overflow its
  grid at any breakpoint — test the copy mobile-to-desktop.
