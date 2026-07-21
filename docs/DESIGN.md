---
name: ShopOS
version: 2.0
description: Editorial quiet-luxury storefront system for a bilingual He/En WooCommerce shop on Elementor. Ink on paper, one type family at extreme scale, RTL-first. One token layer (--shopos-ui-*) drives every module; one interaction doctrine governs every state.
# ---------------------------------------------------------------------------
# MACHINE-READABLE TOKEN BLOCK — v2
# Mirrors shopos-theme/assets/css/shopos-tokens.css (the source of truth).
# This frontmatter is the ONLY place this doc states raw values; body tables
# reference token names + usage. Keep in lockstep via the tokens:check task
# (§15). Values here are the fallback/base layer; theme.json + the Design
# panel can override at runtime (§4 Governance).
# ---------------------------------------------------------------------------
palette:            # raw ink chips — --shopos-ui-palette-*
  black:      "#111111"
  ink:        "#1b1b1b"
  ink-soft:   "#3a3a3a"
  mute:       "#6b6b6b"   # ink-muted
  hairline:   "#e6e6e2"
  paper:      "#ffffff"
  paper-alt:  "#faf9f7"   # paper-soft
  paper-dim:  "#f1efea"
  sand:       "#e9e4db"
  gold:       "#b68a3a"
  forest:     "#3f6b4f"   # opt-in accent — deliberately NOT the success teal
  red:        "#b11226"   # danger
  green:      "#0e7c66"   # success
  amber:      "#a8630a"   # warning
  info:       "#225e8f"
colors:             # semantic slots — --shopos-ui-color-*
  ink:          "{palette.ink}"
  ink-soft:     "{palette.ink-soft}"
  ink-muted:    "{palette.mute}"
  paper:        "{palette.paper}"
  paper-soft:   "{palette.paper-alt}"
  paper-dim:    "{palette.paper-dim}"
  hairline:     "{palette.hairline}"
  accent:       "{palette.ink}"          # ink IS the default accent
  accent-soft:  "{palette.paper-dim}"
  accent-text:  "{palette.paper}"
  accent-hover: "color-mix(in srgb, {colors.accent} 88%, #000)"
  accent-active: "color-mix(in srgb, {colors.accent} 78%, #000)"
  danger:        "{palette.red}"
  danger-soft:   "#fde3e6"
  danger-text:   "#8f101f"    # text on danger-soft — ≥4.5:1
  success:       "{palette.green}"
  success-soft:  "#d9f2ea"
  success-text:  "#0a5c4c"    # text on success-soft — ≥4.5:1
  warning:       "{palette.amber}"
  warning-soft:  "#fdecd0"
  warning-text:  "#7c4708"    # text on warning-soft — ≥4.5:1
  info:          "{palette.info}"
  info-soft:     "#dce9f3"
  info-text:     "#1c4a73"    # text on info-soft — ≥4.5:1
inverted:           # .is-inverted remaps the semantic slots above — nothing else
  ink:         "#ffffff"
  ink-soft:    "#d6d4cf"
  ink-muted:   "#a5a29b"      # ≥4.5:1 on the ink surface
  paper:       "{palette.ink}"
  paper-soft:  "#242423"
  paper-dim:   "#2e2d2b"
  hairline:    "rgba(255,255,255,0.16)"
  accent:      "#ffffff"      # primary button flips to paper-on-ink automatically
  accent-soft: "rgba(255,255,255,0.12)"
  accent-text: "{palette.ink}"
accentThemes:       # body class .is-accent-* swaps the accent slot only
  ink:    { accent: "{palette.ink}",    accent-soft: "{palette.paper-dim}", accent-text: "{palette.paper}" }
  gold:   { accent: "{palette.gold}",   accent-soft: "#f5e6c3",             accent-text: "#1a1a1a" }
  forest: { accent: "{palette.forest}", accent-soft: "#e2ece5",             accent-text: "#ffffff" }
typography:
  fonts:
    body:    "var(--e-global-typography-sk_type_12-font-family, 'Heebo', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif)"
    display: "var(--shopos-ui-font-body)"   # ONE family — display is an alias kept for API compatibility
    mono:    "ui-monospace, SFMono-Regular, Menlo, Consolas, monospace"
  scale:            # fluid clamp() between min screen and ~1600px — --shopos-ui-text-*
    xs:      "clamp(0.75rem, 0.73rem + 0.1vw, 0.8125rem)"     # 12→13px   label / badge / meta
    sm:      "clamp(0.8125rem, 0.78rem + 0.18vw, 0.9375rem)"  # 13→15px   small / caption / button
    md:      "clamp(0.95rem, 0.9rem + 0.3vw, 1.0625rem)"      # 15→17px   body (default)
    lg:      "clamp(1.125rem, 1.06rem + 0.35vw, 1.3125rem)"   # 18→21px   title / H4
    xl:      "clamp(1.375rem, 1.22rem + 0.8vw, 1.875rem)"     # 22→30px   H3
    xxl:     "clamp(1.875rem, 1.5rem + 1.9vw, 3rem)"          # 30→48px   H2
    hero:    "clamp(2.5rem, 1.75rem + 3.8vw, 5rem)"           # 40→80px   H1
    display: "clamp(3.25rem, 2.1rem + 5.8vw, 7.5rem)"         # 52→120px  statement — ≤1 per page
  leading:
    flat:  1.05   # display only  (:lang(he) → 1.1)
    tight: 1.15   # hero–xl headings  (:lang(he) → 1.2)
    snug:  1.35   # titles
    base:  1.6    # body
    loose: 1.8
  tracking:
    tight:  "-0.02em"  # Latin, hero/display only (zeroed under :lang(he))
    normal: "0"
    wide:   "0.04em"   # Label voice, Latin only (zeroed under :lang(he))
  weight:            # hosted variable range is 300–700 — light is now real
    light: 300
    regular: 400
    medium: 500
    semi: 600
    bold: 700        # inline emphasis + prices only; never headings
  roles:             # role → element/token map (§6). Lighter-As-Larger rule applies.
    display:  { token: "text-display", weight: 300, leading: "flat",  tracking: "tight",  case: none, el: ".shopos-ui-display / h1.is-display" }
    h1:       { token: "text-hero",    weight: 400, leading: "tight", tracking: "tight",  case: none, el: "h1" }
    headline: { token: "text-xxl",     weight: 500, leading: "tight", case: none, el: "h2" }
    h3:       { token: "text-xl",      weight: 600, leading: "tight", el: "h3" }
    title:    { token: "text-lg",      weight: 500, leading: "snug",  case: none, el: "h4 / buy-box name" }
    body:     { token: "text-md",      weight: 400, leading: "base",  measure: "65ch" }
    small:    { token: "text-sm",      weight: 400, leading: "base",  el: "captions, meta, form labels" }
    label:    { token: "text-xs",      weight: 600, tracking: "wide", case: "uppercase (Latin only — see Hebrew Label Voice)", el: "h6 / badge / chip; buttons take this voice at text-sm" }
spacing:            # --shopos-ui-space-* — component rhythm (static)
  "0": "0"
  xxs: "2px"
  xs:  "4px"
  sm:  "8px"
  md:  "16px"
  lg:  "24px"
  xl:  "40px"
  xxl: "64px"
  3xl: "96px"
layout:             # page rhythm is FLUID — air scales with the viewport
  container-max:  "1360px"
  container-wide: "1560px"                              # hero, PLP grid, editorial bands
  container-pad:  "clamp(1rem, 0.5rem + 2.5vw, 3rem)"   # 16→48px gutters
  section-gap:    "clamp(4rem, 1.2rem + 6.5vw, 8rem)"   # 64→128px between sections
  drawer-width:   "min(480px, 94vw)"
  swatch-size:    "28px"
  measure:        "65ch"
rounded:            # --shopos-ui-radius-* — see Radius-Is-Identity, §3
  xs:    "2px"    # buttons, inputs, selects, textareas, list thumbnails
  md:    "6px"    # cards, panels, popovers, search panel, toasts
  lg:    "12px"   # modals, sheets, drawer leading edge
  pill:  "999px"  # badges, filter chips
  round: "50%"    # icon buttons, swatch dots, count bubbles
shadows:            # --shopos-ui-shadow-* — depth is tonal-first, shadow-second
  xs:    "0 1px 1px rgba(0,0,0,0.04)"
  sm:    "0 1px 2px rgba(0,0,0,0.06)"   # card at rest (the one sanctioned resting shadow)
  md:    "0 8px 24px rgba(0,0,0,0.08)"  # card hover, search/quick-view panels
  lg:    "0 24px 48px rgba(0,0,0,0.12)" # drawers, modals, toasts
  xl:    "0 40px 80px rgba(0,0,0,0.18)" # full-screen overlays only
  inset: "inset 0 0 0 1px var(--shopos-ui-color-hairline)"
motion:             # --shopos-ui-motion-* (durations collapse to 0 under reduced-motion)
  instant: "90ms"
  fast:    "180ms"
  base:    "280ms"
  slow:    "480ms"
  spin:    "0.8s"   # spinner loop — intentionally NOT zeroed by reduced-motion
  ease:     "cubic-bezier(0.2, 0, 0, 1)"
  ease-out: "cubic-bezier(0.2, 0.8, 0.2, 1)"   # entrances
  ease-in:  "cubic-bezier(0.4, 0, 1, 1)"       # exits
overlay:
  scrim: "rgba(0, 0, 0, 0.45)"
  blur:  "2px"
breakpoints:        # reference values only — CSS custom props can't be used in @media
  tablet: "1024px"
  mobile: "640px"   # CANON. Any module still keyed to 768px migrates to these two (§20).
zIndex:             # --shopos-ui-z-*
  card:    1
  sticky:  100
  overlay: 5000
  modal:   10000
  toast:   10100
  max:     99999
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor:       "{colors.accent-text}"
    border:          "1px solid {colors.accent}"
    typography:      "label voice at text-sm (uppercase Latin, 600, 0.04em / He: 600, word-spacing 0.06em)"
    rounded:         "{rounded.xs}"
    padding:         "0 24px"
    minHeight:       "48px"
    hover:           "translateY(-1px) + bg {colors.accent-hover}"
    active:          "translateY(0) + bg {colors.accent-active}"
    disabled:        "opacity 0.4, no lift"
  button-ghost:
    backgroundColor: "transparent"
    textColor:       "{colors.ink}"
    border:          "1px solid {colors.hairline}"
    hover:           "border→ink, bg→paper-soft"
    rounded:         "{rounded.xs}"
    padding:         "0 24px"
    minHeight:       "48px"
  button-link:
    backgroundColor: "transparent"
    textColor:       "{colors.ink}"
    case:            "none, no tracking, underlined (offset 3px, thickness 1px)"
    minHeight:       0
    padding:         0
  button-sm:
    minHeight:       "36px"
    padding:         "0 16px"
    typography:      "{typography.scale.xs}"
  iconbtn:            # NEW — the one icon-trigger primitive, everywhere
    shape:           "{rounded.round} — a circle, always, at every state"
    sizes:           "sm 32px / md 40px (default) / lg 48px"
    glyph:           "outline icon, 1.5px stroke; 18px in sm, 20px in md/lg"
    rest:            "transparent bg, {colors.ink} glyph, no border, no shadow"
    hover:           "bg {colors.paper-dim}"
    active:          "bg {palette.sand}"
    selected:        "filled glyph, {colors.ink}; bg stays transparent"
    disabled:        "opacity 0.4"
    transition:      "background-color, color @ motion-fast — NOTHING else"
    countBubble:     "16px min, {rounded.round}, ink fill, paper text, text-xs, top inline-end"
  chip:               # NEW — filter chips
    minHeight:       "32px"
    padding:         "0 12px"
    rounded:         "{rounded.pill}"
    typography:      "label voice, text-xs"
    rest:            "paper bg, 1px hairline border, ink text"
    hover:           "border→ink"
    selected:        "ink bg, paper text, ink border"
  input:
    backgroundColor: "{colors.paper}"
    textColor:       "{colors.ink}"
    border:          "1px solid {colors.hairline}"
    borderHover:     "1px solid {colors.ink-soft}"
    focus:           "global focus ring only (outline 2px ink, offset 2px) — border unchanged"
    error:           "border→danger on [aria-invalid=true] / .is-error; message text-sm {colors.danger-text}"
    rounded:         "{rounded.xs}"
    padding:         "0 16px"
    minHeight:       "44px"   # 36px sm
  badge:
    backgroundColor: "{colors.paper-soft}"
    textColor:       "{colors.ink}"
    typography:      "label voice, text-xs"
    rounded:         "{rounded.pill}"
    padding:         "0 8px"
    height:          "22px"
    variants:        "soft fill + matching *-text color: success / danger / warning / info; accent = accent fill + accent-text"
  card:
    backgroundColor: "{colors.paper}"
    textColor:       "{colors.ink}"
    border:          "1px solid transparent → 1px hairline on hover"
    rounded:         "{rounded.md}"
    shadow:          "{shadows.sm} → {shadows.md} on hover"
    hover:           "translateY(-2px); media: crossfade to alt image if present, else img scale(1.03)"
    mediaAspect:     "4 / 5"
    mediaBg:         "{colors.paper-dim}"
    padding:         "16px"
  note:               # NEW — inline notice
    fill:            "*-soft variant bg + matching *-text color"
    rounded:         "{rounded.md}"
    padding:         "{spacing.sm} {spacing.md}"
    typography:      "text-sm; optional label-voice lead word"
  toast:              # NEW
    surface:         "paper, 1px hairline, {shadows.lg}, {rounded.md}"
    position:        "bottom inline-center, z-toast"
    enter:           "slide-up + fade @ motion-base ease-out; auto-dismiss ~4s"
---

# Design System: ShopOS v2 ("The Quiet Boutique")

> **This is the single source of truth for the ShopOS visual language.** Every
> module, button, card, and swatch across `shopos-core`, `shopos-theme`, and the
> Elementor widgets is built from the tokens and rules below. If a value isn't
> here, it shouldn't be in the code.
>
> **Where the numbers live:** the canonical token layer is
> [`shopos-theme/assets/css/shopos-tokens.css`](../shopos-theme/assets/css/shopos-tokens.css).
> The frontmatter above is its generated mirror and the only place this doc
> states raw values — body sections reference token names and usage. The
> per-module *look* is catalogued in [`CLAUDE-DESIGN.md`](CLAUDE-DESIGN.md).
>
> **What's new in v2, in one breath:** one type family at extreme scale (52→120px
> statement display, Lighter-As-Larger weights) · inverted ink surfaces (footer +
> one editorial band, by token remap) · a single interaction doctrine (radius
> never changes, three sanctioned hover recipes, one circular icon-button
> primitive) · fluid page rhythm (16→48px gutters, 64→128px section gaps) · an
> imagery art-direction section · a composition pattern library with exemplar
> markup · contrast-safe semantic text tokens · a Hebrew label voice · and a
> migration checklist (§20) for syncing `tokens.css` and the modules.

---

## 1. Overview — Creative North Star: "The Quiet Boutique"

ShopOS dresses a WooCommerce storefront the way a considered boutique dresses its
floor: **ink on paper, one typeface carrying the entire hierarchy through scale,
and color held in reserve until it means something.** The default voice is
near-monochrome — deep ink (`#1b1b1b`) on white and warm-neutral papers — with
emphasis built from *size contrast, weight contrast, and air*, not decoration.
It is bilingual and **RTL-first**: Hebrew is the primary reading direction, and
every rule here must hold mirrored.

**Three words:** restrained, precise, trustworthy — a place where a shopper makes
a confident decision without being shouted at.

**The two signatures.** Boldness is spent in exactly two places, and everything
around them stays quiet:
1. **The statement display** — one enormous, light-weight (300) headline moment
   per page, 52→120px. This is the editorial voice of the brand.
2. **The Live Search Panel** — the interaction-quality bar every other module is
   measured against (§9.9).

**Reference constellation** (what "right" looks like — agents should anchor
here, not on generic e-commerce): Toteme, The Row, COS, Arket, Aesop. Study the
air, the type scale, the photography discipline — not the literal layouts.

**Key characteristics:**
- Ink-on-paper monochrome by default; color is rare and meaningful.
- One family, extreme size jumps — hierarchy is scale and weight, never a second
  typeface and never ornament.
- Tonal layering over shadow; hairlines do real structural work.
- One inverted ink moment per page (plus the footer) for dynamic range.
- RTL-first and bilingual; mirrored-correct, locale-aware on He and En.
- One token layer drives every module — change the token, not the component.

**Audience:** bilingual **Hebrew-first / RTL** WooCommerce fashion shoppers on
Elementor, mostly mobile; secondary audience is the store operator in wp-admin.

---

## 2. Design principles (the "why")

- **Serve the decision, not the page.** Every surface (search, filters, card,
  quick view, swatches, buy box) exists to move the shopper toward a confident
  purchase. Reduce friction; remove anything that doesn't help the decision.
- **Tokens are the single source of truth.** Modules consume `--shopos-ui-*`
  tokens through `var(--shopos-ui-*, <fallback>)`; visual change happens in the
  token layer so the whole suite stays coherent. Never hardcode what a token
  already expresses.
- **RTL & bilingual are first-class.** Hebrew is primary; every layout, label,
  price, and animation must be correct mirrored and locale-aware on both sides.
- **Truthful state over optimistic state.** Stock, availability, price, and facet
  counts reflect real per-variation truth — a sold-out size drops out, a no-match
  search shows an honest empty result. Never show a product the shopper can't buy.
- **Restraint is the brand — but restraint is a choice, not an absence.** Type,
  weight, and space before color or ornament. Every page still needs one focal
  point and one dramatic scale jump; "quiet" without a statement is just empty.

---

## 3. Named rules (the "what")

These are the non-negotiables. Each has a name so it can be cited in review.

- **The Ink-First Rule.** The primary accent is ink, not a hue. Reach for
  `--shopos-ui-color-ink` before any color. Gold/forest are per-site opt-in
  themes (`.is-accent-*`) — color on demand, never by default.
- **The Semantic-Only Color Rule.** Green/red/amber/blue appear **only** to
  report state (in-stock, error, caution, info). Forbidden as decoration. The
  accent may never equal a semantic hue — that's why forest is `palette-forest`,
  not the success teal. Corollary: a compare-at price (`<del>`) is **ink-muted**,
  not red — a sale is not an error.
- **The Hebrew Flat-Tracking Rule.** Letter-spacing is zeroed under `:lang(he)`.
  Latin gets the −0.02em display tightening and the 0.04em label spacing; Hebrew
  gets neither. Never ship a tracked Hebrew heading. The **Hebrew Label Voice**
  (§6.3) defines what replaces uppercase.
- **The One Uppercase Voice Rule.** Uppercase is reserved for the **Label** role
  (buttons, badges, h6 eyebrow, filter chips) — Latin only. Headings and body are
  never uppercased. The eyebrow appears **at most once per page**, above the lead
  section only.
- **The Flat-At-Rest Rule.** Surfaces rest tonally flat. Shadow appears as a
  reaction to hover/focus/float. The single sanctioned exception: a product card
  rests at the near-subliminal `shadow-sm` so it separates from a paper page.
- **The Hairline-Does-The-Work Rule.** Before adding a shadow to separate two
  surfaces, try a 1px hairline or a tonal paper step. Shadows are the last resort
  for separation, not the first.
- **The Radius-Is-Identity Rule (new).** Every component class has exactly one
  radius, fixed for life (§8.1 shape ladder). `border-radius` **never appears in
  a `transition` list** and **never changes between states** — no square-to-round
  morphing, ever. A circle at rest is a circle on hover, pressed, and selected.
- **The One-Recipe-Per-Component Rule (new).** Every interactive element uses
  exactly one of the three sanctioned hover recipes (§8.2) and keeps it in every
  context. Two modules rendering the same primitive must be indistinguishable.
- **The Lighter-As-Larger Rule (new).** As type gets bigger it gets lighter:
  display 300 · hero 400 · xxl 500 · xl 600. Weight 700 never appears on a
  heading — it belongs to inline emphasis and prices only.
- **The One-Ink-Moment Rule (new).** At most one `.is-inverted` band per page,
  plus the footer. Inverted ink is seasoning, not a theme.

---

## 4. Governance — how the one token system works

Every visual value resolves through a single, predictable chain.

**The consumption contract.** Module CSS never hardcodes a value it can token.
It reads `var(--shopos-ui-<name>, <fallback>)` where **the fallback equals the
canonical value**, so a store running ShopOS Core *without* the theme still
renders on-palette. The theme's `tokens.css` defines the real values on
`:root, .shopos-theme`.

**Precedence (last writer wins), lowest → highest:**

1. **`shopos-tokens.css`** — the base/fallback layer. Enqueued at
   `wp_enqueue_scripts` priority 20.
2. **theme.json → token bridge** ([`design-tokens.php`](../shopos-theme/inc/design-tokens.php),
   priority 21) — re-emits **palette / spacing / radius / motion** from
   theme.json presets + Global-Styles overrides. Kill switch:
   `shopos_theme_design_tokens_enabled`. Typography, semantic colors,
   `.is-accent-*`, the `.is-inverted` remap, and primitives stay on the CSS layer.
3. **Design panel** ([`Design.php`](../shopos-core/src/Core/Design.php),
   priority 30) — the operator's write side (§15). Emits an inline `:root{…}`
   block after the theme's tokens so owner overrides win. Gated by
   `shopos_core_design_panel_enabled` (default off).
4. **`.is-accent-*` body class** — swaps only the accent slot.
5. **`.is-inverted` section scope** — remaps the semantic color slots inside a
   band (§7.3). It is a *remap, not a theme*: components don't know they're on
   ink; they just read the same tokens.
6. **`@media` preference overrides** — `prefers-contrast: more` and
   `prefers-reduced-motion: reduce` redefine tokens at the layer (§13) and are
   re-asserted by the bridge, so they always win the accessibility case.

**Fonts:** one family. `--shopos-ui-font-body` points at the Elementor Style-Kit
slot (`sk_type_12`, de-hardcoded via `Design::kit_slots()`) with the Heebo stack
as fallback; `--shopos-ui-font-display` is an alias of `font-body`, retained so
existing module CSS doesn't break. Self-hosted variable woff2 (Hebrew + Latin
subsets) **covering weights 300–700** — light must ship as a real axis, not
synthesis.

**Naming convention:** `--shopos-ui-<group>-<variant>[-<modifier>]` where
`group = color | palette | text | space | radius | shadow | motion | overlay |
card | input | button | iconbtn | chip | badge | z`.

**Anti-drift:** the frontmatter of this file is *generated from* `tokens.css`
(or diffed against it) by a `tokens:check` script that runs in CI / pre-commit.
A value that exists in two places by hand will drift; v1 proved it. Values live
in `tokens.css`; this doc's body never restates them.

---

## 5. Token catalog — names and jobs

Raw values live in the frontmatter / `tokens.css`. This section is the *usage*
map: what each group is for and the rules that govern reaching for it.

- **Palette (`--shopos-ui-palette-*`)** — raw chips. Modules never consume these
  directly except `sand` (large calm fields, iconbtn pressed state) and `black`
  (icon-circle deepest contrast). Everything else goes through semantic slots.
- **Semantic colors (`--shopos-ui-color-*`)** — the only colors modules use.
  Ink ramp for text (`ink` → `ink-soft` → `ink-muted`), paper ramp for surfaces
  (`paper` → `paper-soft` → `paper-dim`), `hairline` for structure, `accent-*`
  for CTAs and active states, and the four status families — each with a `-soft`
  fill and a **`-text`** partner that is the *only* legal text color on that
  fill (contrast-verified, §7.4).
- **Typography (`--shopos-ui-text/leading/tracking/weight-*`)** — §6.
- **Spacing (`--shopos-ui-space-*`)** — component rhythm, static. Page rhythm
  (gutters, section gaps) uses the fluid layout tokens instead — never build
  section spacing from `space-*` sums.
- **Layout** — `container-max` for text-led sections, `container-wide` for the
  hero, product grids, and editorial bands; `container-pad` and `section-gap`
  are fluid and are the *only* source of page air.
- **Radii (`--shopos-ui-radius-*`)** — the shape ladder, §8.1. Five steps, no
  more: `xs · md · lg · pill · round`.
- **Shadows** — §10. `sm` is the only resting shadow (cards); everything else is
  a state or a floating surface.
- **Motion** — three eases (standard / enter / exit), five durations. There is
  no bounce, spring, or overshoot anywhere in the system.
- **Z-stack** — shared ladder for sticky bars, drawers, QuickView, toasts.
  Modules keep the current literal as a `var()` fallback.
- **Breakpoints** — `640` and `1024` are canon, period. Modules still keyed to
  768px migrate (§20).

---

## 6. Typography — one family, extreme scale

**One family: Heebo** (variable, Hebrew + Latin, 300–700), fed through the
Style-Kit slot. There is no second typeface. Hierarchy is built from three
levers only — **size, weight, and air** — which is why the scale spans a full
**10× jump** from 12px labels to the 120px statement display, and why weight
runs *against* size (Lighter-As-Larger, §3).

### 6.1 Roles → elements

| Role | Token | Weight | Leading | Tracking (Latin) | Case | Element |
|---|---|---|---|---|---|---|
| **Display** | `text-display` | 300 | flat | tight | none | `.shopos-ui-display` / `h1.is-display` — the statement, ≤1 per page |
| **H1** | `text-hero` | 400 | tight | tight | none | `h1` |
| **Headline** | `text-xxl` | 500 | tight | normal | none | `h2` |
| **H3** | `text-xl` | 600 | tight | normal | none | `h3` |
| **Title** | `text-lg` | 500 | snug | normal | none | `h4`, buy-box product name |
| **Body** | `text-md` | 400 | base | normal | none | `p` — measure capped at 65ch |
| **Small** | `text-sm` | 400 | base | normal | none | captions, meta, form labels |
| **Label** | `text-xs` | 600 | wide | UPPERCASE (Latin) | — | `h6` eyebrow, badges, chips; **buttons take this voice at `text-sm`** |

Card titles are `text-md` / 500 / snug (they are list items, not headings).
`h5` is retired — nothing in the storefront needs a fifth heading level; use
Title or Small.

### 6.2 Base element rules (`.shopos-theme` scope)

Headings use `leading-tight`, `margin 0 0 space-md`, weights per the table.
Body is `text-md` / `leading-base` / `color-ink` on `color-paper`, antialiased,
`text-rendering: optimizeLegibility`. Long-form content (product descriptions,
editorial): `max-inline-size: var(--shopos-ui-measure)`.

Base elements beyond headings are specced so no module improvises them:
- **Lists:** `padding-inline-start space-lg`, `gap xs` between items, markers in
  `ink-muted`.
- **Tables** (size charts, specs): full-width, `text-sm`, header row = label
  voice + hairline bottom border, body rows hairline-divided, cell padding
  `sm md`, no zebra fills, no outer border.
- **Blockquote:** `text-lg` / 300 / snug, `padding-inline-start space-lg`, no
  border stripe — the size shift *is* the quote treatment.
- **`hr`:** 1px hairline, `margin-block space-xl`.

### 6.3 The Hebrew Label Voice

Hebrew has no letter case, so the Label role's uppercase+tracking signal doesn't
exist in the primary locale. Under `:lang(he)` the label voice is instead:
**one size step up (`text-sm` where Latin uses `text-xs`; buttons already sit at
`text-sm`), weight 600, `word-spacing: 0.06em`, `text-transform: none`,
`letter-spacing: 0`.** Word-spacing supplies the air that tracking supplies in
Latin. This is the *defined* Hebrew voice — not a degraded fallback — and both
locales must read as equally deliberate.

Under `:lang(he)` also: all tracking → 0, `leading-flat` → 1.1,
`leading-tight` → 1.2 (Hebrew display lines need slightly more breathing room
than Latin at the same size).

### 6.4 Numerals, prices, and bidi

- All prices, counts, and quantities set `font-variant-numeric: tabular-nums`.
- Current price: `text-lg` (buy box: `text-xl`) / weight 500 / `color-ink`.
- Compare-at `<del>`: same size, `color-ink-muted`, strikethrough — **never red**
  (Semantic-Only corollary). If a discount percentage is shown at all, it is
  label voice in `ink-muted`.
- Any mixed-direction run (₪ + digits inside Hebrew, Latin SKU in a Hebrew
  sentence) is wrapped in `<bdi>`. Currency placement follows the store's
  WooCommerce locale setting; the markup, not CSS `direction` hacks, guarantees
  order.

---

## 7. Color & surfaces

### 7.1 Surface discipline

A page is built from exactly four sanctioned section surfaces: **paper**
(default), **paper-soft** (quiet alternation), **sand** (one large calm field —
e.g., behind an editorial quote or the newsletter block), and **inverted ink**
(§7.3). Media wells are always `paper-dim`. No other section background exists —
no arbitrary greys, no gradients, no imagery-as-texture. Two adjacent sections
never share a non-paper surface (no soft-on-soft banding).

### 7.2 The ink ramp and its floors

`ink` for primary text · `ink-soft` for secondary text and form labels ·
`ink-muted` for meta/SKU/captions — and `ink-muted` is legal **only on `paper`
and `paper-soft`**. On `paper-dim` or `sand`, secondary text steps up to
`ink-soft` (muted falls under 4.5:1 there). Placeholders are `ink-muted`, never
lighter.

### 7.3 Inverted surfaces — `.is-inverted`

Dynamic range without a dark mode. A section carrying `.is-inverted` remaps the
semantic slots (values in frontmatter → `inverted:`): paper becomes ink, the ink
ramp becomes a paper ramp, hairlines become translucent white, and **accent
becomes paper** — so the primary button automatically flips to a white button
with ink text, the ghost button to a white-hairline outline, and the focus ring
to white, with zero component changes. That is the point: **invert by remap,
never by writing dark variants of components.**

Rules: footer is always inverted; beyond it, at most one inverted editorial band
per page (One-Ink-Moment). Status colors on inverted surfaces appear only as
soft-fill badges (their light fills carry the contrast). `sand` and imagery
wells are not used inside inverted bands. Shadows are invisible on ink —
structure inside an inverted band is drawn with the remapped hairline.

### 7.4 Contrast contract

Every sanctioned text/surface pair clears WCAG 2.1 AA (≥4.5:1 body, ≥3:1 large
text and UI). The pairs table lives in `tokens.css` as a comment block and is
the review reference; the load-bearing rules:

- On status `-soft` fills, text is always the matching **`-text`** token — never
  the base status hue (base amber/green sit near 4:1 on their soft fills and
  fail at badge size).
- **Gold is never a text color and never a focus color.** Under
  `.is-accent-gold`, gold appears only as fills and borders with dark text
  (`accent-text #1a1a1a`); text links and the focus ring stay ink (the global
  focus ring is ink by definition — §8.4).
- `text-xs` bottoms out at 12px — nothing in the system renders smaller.

---

## 8. Interaction doctrine — one way to behave

This section exists because inconsistent hover behavior is the fastest way to
make a quiet design feel cheap. Every interactive element in every module obeys
the same physics.

### 8.1 The shape ladder (Radius-Is-Identity)

| Shape | Token | Components — exhaustive |
|---|---|---|
| 2px | `radius-xs` | buttons, inputs, selects, textareas, list-row thumbnails |
| 6px | `radius-md` | cards, panels, popovers, search panel, toasts, notes |
| 12px | `radius-lg` | modals, sheets, the drawer's leading edge |
| pill | `radius-pill` | badges, filter chips |
| circle | `radius-round` | icon buttons, swatch dots, count bubbles |

A component's shape never varies by module, context, or state.
`border-radius` is banned from every `transition` property list — grep for it
in review (§20).

### 8.2 The three hover recipes

Every hover is one of these, and each component is permanently assigned one:

1. **Tonal fill** — background steps one paper tone (transparent → `paper-dim`,
   or `paper` → `paper-soft`). *Used by:* icon buttons, list rows, search items,
   ghost buttons (with recipe 2).
2. **Hairline awakening** — border `hairline` → `ink` (fill may step with it).
   *Used by:* ghost buttons, inputs (hover → `ink-soft`), chips.
3. **Lift** — `translateY(-1px)` buttons / `translateY(-2px)` cards, shadow one
   step up, hairline border arrives with the lift. *Used by:* primary buttons,
   product cards. Nothing else in the system moves.

No component blends recipes beyond the pairings named above, and no module
invents a fourth.

### 8.3 What may transition

`background-color`, `color`, `border-color`, `box-shadow`, `opacity`, and
`transform` (only the lifts and the card-image scale) — at `motion-fast` for
color moves, `motion-base` for lifts and fades. **Never** transitioned:
`border-radius`, `width`, `height`, `padding`, `font-size`, `letter-spacing`,
`font-weight`.

### 8.4 Focus — one ring everywhere

`:focus-visible` on every interactive element: `outline: 2px solid
var(--shopos-ui-color-ink); outline-offset: 2px`. That's the entire focus
system — inputs included (their border does not change on focus; the ring
carries it). Because the ring reads `color-ink`, it flips to white inside
`.is-inverted` for free, and it stays ink under every accent theme, which is
what keeps it ≥3:1 always. The old inner box-shadow ring is retired.

### 8.5 State matrix (primitives)

| | rest | hover | active | selected | disabled |
|---|---|---|---|---|---|
| **button-primary** | accent fill | −1px + accent-hover | 0px + accent-active | — | 0.4, no lift |
| **button-ghost** | hairline outline | border→ink, bg→paper-soft | bg→paper-dim | — | 0.4 |
| **iconbtn** | transparent, ink glyph | bg paper-dim | bg sand | filled glyph | 0.4 |
| **chip** | paper + hairline | border→ink | — | ink fill, paper text | 0.4 |
| **input** | paper + hairline | border→ink-soft | — | — | 0.4, bg paper-soft |
| **card** | paper + shadow-sm | −2px, hairline, shadow-md, media swap/zoom | — | — | — |
| **search item** | transparent | bg paper-soft | — | `[aria-selected]` bg paper-soft | — |

### 8.6 Entrances

Content enters with `.shopos-ui-fade-in` (opacity) or `.shopos-ui-slide-up`
(8px rise), `motion-base` / `ease-out`. Lists may stagger children by 40ms,
capped at 5 items. Nothing bounces, springs, pulses, or loops (spinner
excepted). Under reduced motion all of it is instant (§13).

---

## 9. Component specs (the primitives)

Exact specs live in [`shopos.css`](../shopos-theme/assets/css/shopos.css),
scoped under `.shopos-theme`, built from tokens. Business components compose
these; per-module looks are in [`CLAUDE-DESIGN.md`](CLAUDE-DESIGN.md).

### 9.1 Buttons — `.shopos-ui-btn`
Inline-flex centered, `gap space-sm`, `min-height 48px`, `padding 0 space-lg`,
`radius-xs`, accent fill + accent-text + 1px accent border, **label voice at
`text-sm`** (uppercase Latin / Hebrew Label Voice). Hover recipe 3 (−1px +
`accent-hover`); pressed returns to 0 with `accent-active`. `--ghost`: recipes
1+2 as specced. `--link`: ink text, underline (3px offset / 1px thickness), no
uppercase, no box. `--sm`: 36px / `text-xs`. `--block`: full-width. On
inverted surfaces all variants flip via the remap — no extra classes.

### 9.2 Icon buttons — `.shopos-ui-iconbtn` *(new)*
**The only way an icon trigger is built** — header cart/search/account,
wishlist on cards, quick-view triggers, gallery arrows, drawer closes.
A circle (`radius-round`) at 40px (32 `--sm` for dense rows, 48 `--lg` for the
mobile header), transparent at rest with a 20px outline glyph in `color-ink`,
no border, no shadow, ever. Hover fills the circle `paper-dim`; pressed fills
`sand`; a toggled state (wishlisted) switches to the filled glyph and the fill
returns to transparent. Transitions background and color only. The cart count
is a `radius-round` ink bubble, paper text, `text-xs`, pinned top inline-end.
This primitive replaces every per-module icon-hover invention — square hovers,
opacity dims, and radius morphs are all retired (§20).

### 9.3 Inputs — `.shopos-ui-input` / `-select` / `-textarea` / `-label`
Block, 100% width, `min-height 44px` (`--sm` 36px), `padding 0 space-md`, 1px
hairline, `radius-xs`, paper fill. Hover recipe 2 (border → `ink-soft`). Focus:
global ring only (§8.4). Error: `[aria-invalid="true"]` / `.is-error` →
border `danger`, message line `text-sm` in `danger-text`. Labels: `text-sm`,
weight 500, `ink-soft`, `margin-block-end space-xs`. Textarea: ×2.5 min-height,
vertical resize.

### 9.4 Badges — `.shopos-ui-badge`
22px, `radius-pill`, `padding 0 space-sm`, label voice at `text-xs`, nowrap.
Default paper-soft + ink. Status variants pair `-soft` fill with the matching
`-text` color; `--accent` uses accent fill + accent-text. Max **one badge per
product card** (§12.3).

### 9.5 Filter chips — `.shopos-ui-chip` *(new)*
32px, `radius-pill`, `padding 0 12px`, label voice at `text-xs`, paper +
hairline. Hover recipe 2; selected = ink fill / paper text / ink border. The
chip is the one place selection inverts locally — a selected filter should read
at a glance.

### 9.6 Cards — `.shopos-ui-card`
Paper, 1px transparent border, `radius-md`, `shadow-sm`, `overflow: hidden`.
Hover recipe 3: −2px, hairline arrives, `shadow-md`. `__media`: aspect 4/5,
`paper-dim` well; on hover **crossfade to the alternate image** (opacity,
`motion-base`) when one exists, otherwise scale the image to 1.03
(`motion-slow`) — never both. `__body`: `padding space-md`, column,
`gap space-xs`. `__title`: `text-md` / 500 / snug, clamped to 2 lines.
`__meta`: `text-sm`, `ink-muted`.

### 9.7 Links
Inherit ink, underline thickness 1px / offset 3px, color transition
`motion-fast`. No global hover-opacity (it greys product images) — text links
opt into `.shopos-ui-link--dim` (0.75); image anchors stay crisp.

### 9.8 Notes, toasts, skeletons
**Note** `.shopos-ui-note`: inline notice — `-soft` fill + `-text` color,
`radius-md`, `padding sm md`, `text-sm`, optional label-voice lead word
("Heads-up"). Never a colored side-stripe. **Toast** `.shopos-ui-toast`: paper,
hairline, `shadow-lg`, `radius-md`, bottom inline-center at `z-toast`,
slide-up entrance, ~4s auto-dismiss, one at a time. **Skeleton**
`.shopos-ui-skeleton`: paper-soft→paper-dim→paper-soft shimmer (1.4s, reversed
under RTL), and its radius always matches the component it stands in for —
a skeleton card is `radius-md`, a skeleton chip is a pill.

### 9.9 Signature component — Live Search Panel
The interaction-quality bar. Body-appended results panel
(`.shopos-search-panel`), JS-positioned under the field or dropped as a
command-palette modal over the scrim. Paper surface, 1px hairline, `shadow-md`,
`radius-md` bottom, 70vh scroll cap. Each `.shopos-search-item` is a 52×52
`radius-xs` thumbnail + title / SKU / price row, hairline-divided, with a
paper-soft `[aria-selected]` state — a proper combobox listbox. Locale-aware
server-rendered `price_html` (muted `<del>` per §6.4), ↑/↓/Enter/Esc,
`aria-live` count. Mobile floats above the software keyboard via
`--shopos-vvh/--shopos-vvt`.

---

## 10. Elevation

Depth is **tonal first, shadow second**. The paper ramp and hairlines carry the
layering: a media well is paper-dim, a raised badge is paper-soft, a divider is
a hairline. Shadows stay quiet — the card's near-subliminal `shadow-sm` is the
only resting shadow in the system; everything else earns shadow through state
(hover `md`) or floating (drawers/modals `lg`, full-screen overlays `xl`).
Inside `.is-inverted`, shadows read as nothing — structure is drawn with the
remapped hairline instead. Under `prefers-contrast: more`, shadows drop to zero
and borders take over: structure is lines and tone, not haze.

---

## 11. Imagery art direction *(new)*

The photography is half the design. These rules are load-bearing.

- **One ground.** Every product shot sits on the same visual ground: the
  `paper-dim` media well. Shots with white or near-white studio backgrounds
  blend into the well; true cut-outs sit on it directly. **Never mix** grey
  studio, pure white, and lifestyle backgrounds inside one grid — background
  inconsistency is the single biggest "cheap shop" tell.
- **Ratios by context:** product card & PDP gallery **4:5** · hero **16:9**
  desktop / **4:5** mobile (art-directed via `<picture>`) · editorial band
  imagery **3:2**. No other ratios.
- **Hover:** alternate image crossfade when available (on-model ↔ flat-lay is
  the ideal pair); zoom 1.03 only as the fallback. Never both.
- **One mode per strip.** A grid row or collection strip is either all on-model
  or all flat-lay — mixing modes inside a row breaks the rhythm.
- **Text over imagery** only with the ink scrim gradient (transparent → 45%)
  behind it, and only display/hero/label roles in paper color; body text never
  sits on a photo. Prefer text *beside* imagery (split hero) over text on it.
- **Never decorate the image:** no borders, no rounded frames beyond the
  clipping container, no filters, no duotones. Radius comes from the card
  (`radius-md`) or the section (full-bleed = none).
- **RTL crops:** key subject matter must survive mirroring of the layout —
  compose hero crops with the subject in the vertical center-third, and test
  both directions.

---

## 12. Composition patterns *(new — the pattern library)*

Pages are assembled **only** from these named patterns. Each is citable in
review, like the rules. Air between patterns is `section-gap` — never ad-hoc
margins. Every view has exactly **one focal point** and at least one type-scale
jump of ≥3× (display or hero against body).

### 12.1 Hero / Statement — ≤1 per page
Two sanctioned forms. **Split** (default): text column + full-height image on
the `container-wide` grid — Display role headline (2–6 words), one line of
`text-lg` / 300 support, one primary button, optional `--link`. **Full-bleed**:
16:9 image, ink scrim, Display in paper, one CTA. Vertical padding inside the
hero: `section-gap` top and bottom. The eyebrow, if the page uses one at all,
lives here and only here.

### 12.2 Section Header
`h2` Headline + optional lead (`text-sm`, `ink-muted`, ≤65ch) + optional
"View all" `--link` aligned inline-end on the same baseline. No eyebrow (§3),
no icon, no divider — the size jump does the work.

### 12.3 Product Grid (PLP)
`container-wide`. Columns: **2** below 640 · **3** at 640–1023 · **4** at
1024+. Gutters `space-lg` (`space-md` below 640); tracks pinned to
`minmax(0, 1fr)`. Card anatomy, fixed order: media (4:5) → title (≤2 lines) →
price row (price + muted `<del>`) → swatch dots (max 5, then "+N"). **At most
one badge** per card ("New" / "Sale"), top inline-start over the media, standard
badge primitive. Wishlist iconbtn top inline-end over the media, visible on
hover/focus (always visible on touch). Filters: chips row on desktop, drawer
below 1024.

### 12.4 PDP (product page)
≥1024: two columns on `container-max` — gallery 55–60% at inline-start, buy box
sticky at inline-end. Buy-box order, fixed: product name (Title role; `text-xl`
at 1024+, `text-lg` below) → price (§6.4) → variation
swatches → quantity + primary `--block` button → accordion details (hairline
dividers, `h4` Title rows, no cards, no boxes). Below 1024: swipeable gallery,
content stacked, and a sticky bottom add-to-cart bar (paper, top hairline,
`z-sticky`, safe-area padded).

### 12.5 Editorial Band
Full-width `sand` **or** `.is-inverted` ink (One-Ink-Moment). Content on
`container-max`: a Display or `text-xxl`/300 statement, ≤1 CTA. Used between
product sections as the page's breath. Sand band and ink band never touch.

### 12.6 Footer
Always `.is-inverted`. 3–4 columns ≥1024 (label-voice column headings,
`text-sm` links in remapped `ink-soft`, hover → `ink`), accordion below 640.
Newsletter input + primary button (both flip via remap). Legal line in remapped
`ink-muted`, hairline-separated.

### 12.7 Announcement Bar
One line, `text-xs` label voice, ink band (paper text) or paper + bottom
hairline. Static — no marquee, no rotation, no close animation drama.

### 12.8 Empty & Result States
Type-only, centered on `container-max`: `text-xl`/400 statement of fact
("Nothing matches these filters"), one `text-sm` `ink-soft` line of direction,
one ghost action ("Clear filters"). No illustrations, no emojis. Copy is
direct and active-voice; errors say what happened and what to do, and never
apologize.

### 12.9 Exemplar markup

The canonical skeletons agents copy from. Logical properties only — these must
render correctly in both directions unchanged.

```html
<!-- Icon button (the ONLY icon-trigger pattern) -->
<button class="shopos-ui-iconbtn" aria-label="עגלת קניות">
  <svg class="shopos-ui-icon" aria-hidden="true"><!-- 20px, 1.5px stroke --></svg>
  <span class="shopos-ui-iconbtn__count">3</span>
</button>

<!-- Product card -->
<article class="shopos-ui-card">
  <a class="shopos-ui-card__media" href="…">
    <img src="…" alt="…">                <!-- primary, 4:5, on paper-dim well -->
    <img src="…" alt="" aria-hidden="true"> <!-- alt image: hover crossfade -->
    <span class="shopos-ui-badge">חדש</span>
    <button class="shopos-ui-iconbtn shopos-ui-card__wish" aria-label="הוספה למועדפים">…</button>
  </a>
  <div class="shopos-ui-card__body">
    <h4 class="shopos-ui-card__title">שם המוצר בשתי שורות לכל היותר</h4>
    <p class="shopos-ui-card__price"><bdi>₪320</bdi> <del><bdi>₪420</bdi></del></p>
    <div class="shopos-ui-card__swatches"><!-- ≤5 dots + "+N" --></div>
  </div>
</article>

<!-- Split hero (statement) -->
<section class="shopos-ui-hero shopos-ui-container--wide">
  <div class="shopos-ui-hero__copy">
    <h1 class="shopos-ui-display">הקולקציה החדשה</h1>
    <p class="shopos-ui-hero__lead">שורה אחת, שקטה, שמכוונת להחלטה.</p>
    <a class="shopos-ui-btn" href="…">לצפייה בקולקציה</a>
  </div>
  <figure class="shopos-ui-hero__media"><img src="…" alt="…"></figure>
</section>

<!-- Section header -->
<header class="shopos-ui-section-head">
  <h2>מעילים</h2>
  <a class="shopos-ui-btn--link" href="…">לכל המעילים</a>
</header>
```

---

## 13. Accessibility & motion (always on)

- **WCAG 2.1 AA** via the contrast contract (§7.4): body ≥4.5:1, large text and
  UI ≥3:1, verified pairs only — including placeholders and status badges.
- **Focus** (§8.4): one ink ring, everywhere, keyboard-operable throughout —
  filters, search combobox, quick-view drawer, swatches, gallery.
- **`prefers-reduced-motion: reduce`** collapses
  `motion-instant/fast/base/slow` to `0ms` at the token layer (re-asserted by
  the bridge), so every transition referencing them becomes instant. Spinners
  keep `motion-spin` (freeze to a static ring where appropriate); fades,
  slides, staggers, and image crossfades resolve immediately.
- **`prefers-contrast: more`** strengthens card/input borders (1px→ink, hover
  2px), drops card shadows to none, thickens the focus ring to 3px.
- Touch targets ≥44×44 (iconbtn `--sm` 32px is desktop-density only).
- Both preference modes are wired in the token layer, so modules inherit them
  for free.

---

## 14. RTL & bilingual

Hebrew is primary. Primitives use **logical properties** (`margin-inline-*`,
`padding-inline-*`, `inset-inline-*`, `border-inline-*`) so they flip
automatically; [`shopos-rtl.css`](../shopos-theme/assets/css/shopos-rtl.css)
(loaded only when `is_rtl()`) handles the exceptions:

- `.shopos-rtl` sets `direction: rtl; text-align: right`.
- **Icon mirroring:** directional glyphs (arrows, external-link) get
  `transform: scaleX(-1)`; symmetric glyphs (cart, heart, search) don't.
- **Label voice swap:** buttons, badges, chips, and `h6` drop `text-transform`,
  zero `letter-spacing`, and apply the Hebrew Label Voice (§6.3).
- **Positioned input icons** re-pad to the correct side; **skeleton shimmer**
  reverses to follow reading flow.
- Prices and mixed runs rely on `<bdi>` (§6.4), never on CSS direction hacks.
- Test every layout, label, price, and animation mirrored. Never track a Hebrew
  heading; never let a long Hebrew heading word overflow its grid — the display
  scale makes this a real risk, so `overflow-wrap: break-word` is off for
  headings and copy is length-checked instead (2–6 word statements).

---

## 15. Operator controls (what a store owner can change)

Two surfaces change tokens without code — both write through the governance
chain (§4), both sanitize values before interpolating into inline CSS.

**Design panel** (ShopOS → Design, `Design.php`, flag
`shopos_core_design_panel_enabled`):
- **Accent preset** (repaints `--shopos-ui-palette-gold`): `default` (theme
  gold) · `terracotta #b5532a` · `forest #3f6b4f` (aligned with
  `palette-forest`) · `indigo #4a54b5` · `plum #7a3f6b`.
- **Curated colour overrides** (blank = inherit): Accent/primary →
  `palette-gold` · Text → `palette-ink` · Text (soft) → `palette-ink-soft` ·
  Surface → `palette-paper` · Surface (alt) → `palette-paper-alt` · Borders →
  `palette-hairline`. **Semantic colors are deliberately NOT overridable** —
  they carry meaning, not brand. Neither are the inverted remap values.
- **Corner radius** → `--shopos-ui-radius-md` (cards/panels), clamped 0–24px.
  Control shapes (`radius-xs`), pills, and circles are not operator-tunable —
  Radius-Is-Identity applies to owners too.

**theme.json bridge** (`design-tokens.php`): palette, spacing, radius, and
motion values set in theme.json / Global Styles re-emit as tokens
automatically. Style-Kit typography is wired through `Design::kit_slots()`
(single slot: `body → sk_type_12`; the display slot maps to the same).

Everything else is a code-level decision — change it in `tokens.css` and let
the `tokens:check` task regenerate this doc's frontmatter.

---

## 16. Do's and Don'ts

### Do
- **Do** consume `--shopos-ui-*` tokens with an on-value fallback; change values
  in `tokens.css` and let them propagate.
- **Do** keep ink as the default accent, and spend boldness only on the two
  signatures: the statement display and the search panel.
- **Do** build depth from the paper ramp and hairlines first; shadow only as a
  response to state or float.
- **Do** give every page one focal point, one ≥3× scale jump, and fluid air
  (`section-gap`, `container-pad`) — quiet needs contrast to read as intent.
- **Do** use the iconbtn primitive for every icon trigger, the three hover
  recipes for every hover, and the `-text` tokens on every `-soft` fill.
- **Do** zero letter-spacing under `:lang(he)` and apply the Hebrew Label
  Voice; test everything mirrored.
- **Do** normalize product imagery to the one ground (§11) before anything
  ships to a grid.

### Don't
- **Don't** ship stock Elementor demo styling or default WooCommerce chrome —
  badge-encrusted cards, templated icon-heading-text grids, busy loop widgets.
- **Don't** transition `border-radius`, change a component's shape between
  states, or invent a fourth hover recipe. No square-to-round morphs, no
  per-module icon-hover styles, no scale effects on icons.
- **Don't** turn the shop into a discount bin: no flashing sale callouts, no
  urgency spam, no competing CTAs, no red `<del>` prices.
- **Don't** use glassmorphism, gradient text, bounce/spring easing, cream-
  everything, or a tracked uppercase eyebrow above every section. No dark
  *mode* — inverted bands are a moment, not a theme.
- **Don't** use color as decoration; green/red/amber/blue report state only,
  and the accent never equals a semantic hue.
- **Don't** use side border-stripes on cards, notes, or list items; use full
  hairlines, tonal fills, or nothing.
- **Don't** put `ink-muted` text on `paper-dim`/`sand`, base status hues on
  `-soft` fills, or gold as text or focus anywhere.
- **Don't** mix icon sets or icon styles — one outline set, 1.5px stroke,
  everywhere. Dashicons never render on the storefront.
- **Don't** mix product-photo backgrounds inside a grid, and don't put body
  text on photography.

---

## 17. Anti-references (what we never ship)

- **Generic Elementor/Woo defaults** — badge-encrusted cards, templated
  icon-heading-text grids, busy loop widgets, dashicons, mixed icon styles,
  anything assembled from off-the-shelf parts.
- **Discount-bin clutter** — flashing sale callouts, urgency spam, competing
  CTAs, cramped grids, red strikethrough prices.
- **AI-slop trend-chasing** — glassmorphism, gradient text, cream-everything,
  bouncing micro-interactions, a tracked uppercase eyebrow above every section,
  dark mode.
- **Shape-shifting UI** — controls that morph radius on hover, icons that grow,
  cards that change shape. A confetti of radii is the opposite of a system.
- **Washed-out sameness** — a page that is only white-on-white hairlines with
  no focal point, no scale jump, and no ink moment. Compliance without
  composition is also a failure state.

---

## 18. Review rubric (agent + human checklist)

Run before any screen ships. Cite rule names in review comments.

1. Every value traces to a token; nothing hardcoded that a token expresses.
2. One focal point; one type jump ≥3×; Display used ≤1× per page.
3. Weights follow Lighter-As-Larger; no 700 headings; no second typeface.
4. Shapes match the ladder (§8.1); `border-radius` in no transition list;
   every icon trigger is the iconbtn circle.
5. Every hover is one of the three recipes; state matrix (§8.5) holds.
6. Focus ring present and ink (paper on inverted) on every interactive.
7. Surfaces: only paper / paper-soft / sand / inverted; ≤1 inverted band +
   footer; media wells paper-dim; imagery on the one ground, correct ratios.
8. Color: semantic only for state; `-text` on `-soft`; `<del>` muted; no gold
   text; `ink-muted` only on paper/paper-soft.
9. Page air from `section-gap`/`container-pad` (fluid), not ad-hoc margins;
   grids per §12.3.
10. RTL pass done: mirrored layout, Hebrew Label Voice, zero tracking, `<bdi>`
    prices, mirrored directional icons, skeleton direction.
11. `prefers-reduced-motion` and `prefers-contrast` verified; touch targets
    ≥44px; text ≥12px.
12. Copy: active voice, decision-serving, no filler; empty/error states follow
    §12.8.

---

## 19. Where things live (file map)

| Concern | File |
|---|---|
| **Canonical token values** | [`shopos-theme/assets/css/shopos-tokens.css`](../shopos-theme/assets/css/shopos-tokens.css) |
| Primitive component classes (`.shopos-ui-*`) | [`shopos-theme/assets/css/shopos.css`](../shopos-theme/assets/css/shopos.css) |
| RTL overrides | [`shopos-theme/assets/css/shopos-rtl.css`](../shopos-theme/assets/css/shopos-rtl.css) |
| Self-hosted fonts (Heebo variable 300–700) | [`shopos-theme/assets/css/shopos-fonts.css`](../shopos-theme/assets/css/shopos-fonts.css) + `assets/fonts/` |
| theme.json → token bridge | [`shopos-theme/inc/design-tokens.php`](../shopos-theme/inc/design-tokens.php) |
| Design panel (operator overrides) | [`shopos-core/src/Core/Design.php`](../shopos-core/src/Core/Design.php) |
| Per-module screen catalogue | [`CLAUDE-DESIGN.md`](CLAUDE-DESIGN.md) |
| Module CSS (consume tokens) | `shopos-core/src/Modules/*/assets/css/*.css` |
| Frontmatter ↔ tokens.css parity | `tokens:check` script (add to CI / pre-commit) |

---

## 20. Migration checklist — v1 → v2 (for Claude Code)

> **Migration status (branch `feat/design-v2-migration`).** Blocks 1–8, 10–18,
> and 20 are **DONE** (token layer, one-family fonts, Radius-Is-Identity,
> single ink focus, 640/1024 breakpoints, radius-sm retirement + button polish,
> ink-muted prices + tabular-nums, `-text` on `-soft` fills, Hebrew Label Voice,
> inverted mechanism + footer + editorial band, statement display + hero +
> section-header, chip/note/toast, and the `tokens:check` guard —
> `tools/tokens-check.php`, 71 tokens in sync). Blocks **9** and **19** are
> delivered at the **foundation** level — the `.shopos-ui-iconbtn` primitive
> exists and the module font fallbacks are one-family, but adopting the iconbtn
> into every existing module icon-trigger (drawer closes, gallery arrows,
> card-slider nav) and the structural PLP/PDP relayout (column counts, card
> anatomy order, one-badge cap, sticky bars) are **visual-QA-gated** and remain
> the live-store follow-on. 1069 tests / 3363 assertions green (php@8.3);
> versions NOT yet bumped (ship-time decision).

Work top-down; each block is a reviewable PR. Grep patterns are literal.

**Tokens (`shopos-tokens.css`) — change first:**
1. Typography: replace the scale with the v2 clamps (incl. new `text-display`);
   add `leading-flat 1.05`; retune `tight → 1.15`, `base → 1.6`; tracking
   `tight → -0.02em`; **delete `tracking-caps`**; point
   `--shopos-ui-font-display` at `var(--shopos-ui-font-body)`.
2. Fonts: extend the hosted Heebo variable files to cover **300–700**; drop the
   Assistant files and `sk_type_2` wiring (kit_slots default: both roles →
   `sk_type_12`).
3. Radii: retire `radius-sm 4px` and `radius-xl 20px`. Remap consumers:
   controls (`button/input/select/textarea/search thumbnails`) `sm → xs`;
   anything on `xl → lg`. Grep: `radius-sm`, `radius-xl`.
4. Motion: **delete `ease-bounce`**. Grep: `bounce`.
5. Colors: add `palette-forest #3f6b4f`; add the four `*-text` tokens; add the
   `.is-inverted` remap block (frontmatter → `inverted:`); align the Design
   panel forest preset to `#3f6b4f`.
6. Layout: make `container-pad` and `section-gap` fluid (frontmatter values);
   add `container-wide 1560px`.
7. Delete `card-hover-scale` (1.015) — the only media motion is the crossfade /
   1.03 image scale; delete `input-focus-ring` — focus is the global outline.

**Doctrine sweeps (whole suite):**
8. **Radius-Is-Identity:** grep `transition` declarations for `border-radius`
   and remove it everywhere; grep `:hover` blocks that set `border-radius` and
   delete the override. This is the square↔round morph fix.
9. **Iconbtn:** implement `.shopos-ui-iconbtn` per §9.2; replace every
   module-local icon-trigger style (header icons, card wishlist, gallery
   arrows, drawer closes) with it. Grep: `dashicons` (storefront), local
   `border-radius: 50%`, icon `:hover` opacity/scale rules.
10. **Focus:** remove per-component focus box-shadows; a single
    `:focus-visible` rule per §8.4.
11. **Breakpoints:** grep `768` in module CSS/JS `matchMedia` and reconcile to
    640/1024 — now, not "when touching."
12. **Buttons/inputs:** radius to `xs`; button font-size unified at `text-sm`
    (label voice); add hover `accent-hover` bg; inputs lose the focus
    border-change.
13. **Prices:** `<del>` styling to `ink-muted` (remove danger); add
    `tabular-nums`; wrap mixed runs in `<bdi>` in the PHP templates.
14. **Badges/notes:** switch text colors on `-soft` fills to the `*-text`
    tokens.
15. **Hebrew Label Voice:** in `shopos-rtl.css` / `:lang(he)` rules, add the
    size-step and `word-spacing: 0.06em` alongside the existing
    transform/tracking reset.

**New builds:**
16. `.is-inverted` scope + footer refactor (§12.6) + one Editorial Band
    pattern (§12.5).
17. `.shopos-ui-display` statement class + hero patterns (§12.1) +
    section-header pattern (§12.2).
18. `.shopos-ui-chip`, `.shopos-ui-note`, `.shopos-ui-toast` primitives.
19. PLP/PDP alignment to §12.3–12.4 (column counts, card anatomy order, badge
    cap, sticky buy box / mobile add-to-cart bar).
20. `tokens:check` script: parse `tokens.css`, diff against this file's
    frontmatter, fail CI on drift.

> **Maintenance rule:** `shopos-tokens.css` is the value source;
> this file's frontmatter is its generated mirror; the body never restates
> values. When a token changes, change `tokens.css`, regenerate, and ship both
> in the same PR.
