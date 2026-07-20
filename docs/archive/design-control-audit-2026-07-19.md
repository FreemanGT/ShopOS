# ShopOS Design & Behavior Control Audit — 2026-07-19

**Goal:** "owner controls everything" — every brand-visible styling decision routed through `--shopos-ui-*` tokens or a Design-panel / Settings-Hub control; every meaningful behavior owner-switchable.

**Method:** 15 parallel per-module audits (every PHP/CSS/JS/template file read) measured against the canonical token + settings contract (§A), synthesized into the prioritized PR list (§B). Per-module detail in §C.

> **⚠️ Confirmed live bug found during audit:** RestockNotify storefront subscribe is broken. `assets/js/frontend.js:81` posts AJAX action `shopos_restock_subscribe`, but the only registered handler is `wp_ajax(_nopriv)_rsn_subscribe` (`legacy/includes/class-shopos-restock-ajax.php:7-8`). No other registration of either action exists in `src/`, and no `.min.js` variant differs. Every subscribe request 400s at admin-ajax. Fix lands as step 0 of PR-05.

---

# §A — Canonical contract (token vocabulary + settings surfaces)

# ShopOS Design Token & Settings Reference (canonical)

Sources read in full: `shopos-core/src/Core/Design.php`, `shopos-theme/assets/css/shopos-tokens.css`, `shopos-core/src/Core/Settings_Hub.php`, `shopos-core/src/Core/Module_Base.php`, `shopos-core/src/Core/Elementor/{Category,Widget_Base}.php`.

## (a) Full `--shopos-ui-*` token vocabulary

Defined in `shopos-theme/assets/css/shopos-tokens.css` on `:root, .shopos-theme`. Naming: `--shopos-ui-<group>-<variant>[-<modifier>]`.

### Palette (raw)

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-palette-black` | `#111111` | Deepest black |
| `--shopos-ui-palette-ink` | `#1b1b1b` | Primary text ink |
| `--shopos-ui-palette-ink-soft` | `#3a3a3a` | Softer text ink |
| `--shopos-ui-palette-mute` | `#6b6b6b` | Muted/secondary text |
| `--shopos-ui-palette-hairline` | `#e6e6e2` | Hairline borders |
| `--shopos-ui-palette-paper` | `#ffffff` | Base surface white |
| `--shopos-ui-palette-paper-alt` | `#faf9f7` | Alternate surface |
| `--shopos-ui-palette-paper-dim` | `#f1efea` | Dimmed surface |
| `--shopos-ui-palette-sand` | `#e9e4db` | Sand neutral |
| `--shopos-ui-palette-gold` | `#b68a3a` | Brand accent (gold) |
| `--shopos-ui-palette-red` | `#b11226` | Danger/critical red |
| `--shopos-ui-palette-green` | `#0e7c66` | Success green |
| `--shopos-ui-palette-amber` | `#a8630a` | Warning amber |
| `--shopos-ui-palette-info` | `#225e8f` | Informational blue |

### Semantic colors

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-color-ink` | `var(--shopos-ui-palette-ink)` | Body text color |
| `--shopos-ui-color-ink-muted` | `var(--shopos-ui-palette-mute)` | Muted text |
| `--shopos-ui-color-ink-soft` | `var(--shopos-ui-palette-ink-soft)` | Soft text |
| `--shopos-ui-color-paper` | `var(--shopos-ui-palette-paper)` | Default surface |
| `--shopos-ui-color-paper-soft` | `var(--shopos-ui-palette-paper-alt)` | Soft surface |
| `--shopos-ui-color-paper-dim` | `var(--shopos-ui-palette-paper-dim)` | Dim surface |
| `--shopos-ui-color-hairline` | `var(--shopos-ui-palette-hairline)` | Border color |
| `--shopos-ui-color-accent` | `var(--shopos-ui-palette-ink)` | CTA/active-state accent |
| `--shopos-ui-color-accent-soft` | `var(--shopos-ui-palette-paper-dim)` | Accent tint background |
| `--shopos-ui-color-accent-text` | `var(--shopos-ui-palette-paper)` | Text on accent |
| `--shopos-ui-color-danger` | `var(--shopos-ui-palette-red)` | Danger state |
| `--shopos-ui-color-danger-soft` | `#fde3e6` | Danger tint |
| `--shopos-ui-color-success` | `var(--shopos-ui-palette-green)` | Success state |
| `--shopos-ui-color-success-soft` | `#d9f2ea` | Success tint |
| `--shopos-ui-color-warning` | `var(--shopos-ui-palette-amber)` | Warning state |
| `--shopos-ui-color-warning-soft` | `#fdecd0` | Warning tint |
| `--shopos-ui-color-info` | `var(--shopos-ui-palette-info)` | Info state |
| `--shopos-ui-color-info-soft` | `#dce9f3` | Info tint |

### Typography

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-font-body` | `var(--e-global-typography-sk_type_12-font-family, 'Heebo', 'Rubik', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif)` | Body font (Style Kits "Body" slot authoritative) |
| `--shopos-ui-font-display` | `var(--e-global-typography-sk_type_2-font-family, 'Assistant', var(--shopos-ui-font-body))` | Display/heading font (Style Kits "Heading 1" slot) |
| `--shopos-ui-font-mono` | `ui-monospace, SFMono-Regular, Menlo, Consolas, monospace` | Monospace stack |
| `--shopos-ui-text-xs` | `clamp(0.72rem, 0.68rem + 0.2vw, 0.78rem)` | ~12px fluid step |
| `--shopos-ui-text-sm` | `clamp(0.82rem, 0.78rem + 0.22vw, 0.9rem)` | ~14px fluid step |
| `--shopos-ui-text-md` | `clamp(0.95rem, 0.9rem + 0.3vw, 1.05rem)` | ~16px fluid step |
| `--shopos-ui-text-lg` | `clamp(1.1rem, 1rem + 0.4vw, 1.25rem)` | ~20px fluid step |
| `--shopos-ui-text-xl` | `clamp(1.35rem, 1.2rem + 0.7vw, 1.75rem)` | ~28px fluid step |
| `--shopos-ui-text-xxl` | `clamp(1.9rem, 1.55rem + 1.5vw, 2.75rem)` | ~40px fluid step |
| `--shopos-ui-text-hero` | `clamp(2.5rem, 1.9rem + 2.6vw, 4rem)` | ~60px hero size |
| `--shopos-ui-leading-tight` | `1.18` | Tight line-height |
| `--shopos-ui-leading-snug` | `1.35` | Snug line-height |
| `--shopos-ui-leading-base` | `1.55` | Base line-height |
| `--shopos-ui-leading-loose` | `1.8` | Loose line-height |
| `--shopos-ui-tracking-tight` | `-0.01em` | Tight letter-spacing |
| `--shopos-ui-tracking-normal` | `0` | Normal letter-spacing |
| `--shopos-ui-tracking-wide` | `0.04em` | Wide letter-spacing |
| `--shopos-ui-weight-regular` | `400` | Regular weight |
| `--shopos-ui-weight-medium` | `500` | Medium weight |
| `--shopos-ui-weight-semi` | `600` | Semibold weight |
| `--shopos-ui-weight-bold` | `700` | Bold weight |

### Spacing

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-space-0` | `0` | Zero step |
| `--shopos-ui-space-xxs` | `2px` | Hairline gap |
| `--shopos-ui-space-xs` | `4px` | Extra-small gap |
| `--shopos-ui-space-sm` | `8px` | Small gap |
| `--shopos-ui-space-md` | `16px` | Medium gap |
| `--shopos-ui-space-lg` | `24px` | Large gap |
| `--shopos-ui-space-xl` | `40px` | Extra-large gap |
| `--shopos-ui-space-xxl` | `64px` | Section-level gap |
| `--shopos-ui-space-3xl` | `96px` | Largest gap |

### Radii

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-radius-xs` | `2px` | Minimal rounding |
| `--shopos-ui-radius-sm` | `4px` | Inputs/buttons rounding |
| `--shopos-ui-radius-md` | `6px` | Card/default rounding |
| `--shopos-ui-radius-lg` | `12px` | Large rounding |
| `--shopos-ui-radius-xl` | `20px` | Extra-large rounding |
| `--shopos-ui-radius-pill` | `999px` | Pill shape |
| `--shopos-ui-radius-round` | `50%` | Circle |

### Shadows

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-shadow-xs` | `0 1px 1px rgba(0,0,0,0.04)` | Faintest elevation |
| `--shopos-ui-shadow-sm` | `0 1px 2px rgba(0,0,0,0.06)` | Small elevation |
| `--shopos-ui-shadow-md` | `0 8px 24px rgba(0,0,0,0.08)` | Medium elevation |
| `--shopos-ui-shadow-lg` | `0 24px 48px rgba(0,0,0,0.12)` | Large elevation |
| `--shopos-ui-shadow-xl` | `0 40px 80px rgba(0,0,0,0.18)` | Maximum elevation |
| `--shopos-ui-shadow-inset` | `inset 0 0 0 1px var(--shopos-ui-color-hairline)` | Inset hairline ring |

### Motion

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-motion-instant` | `90ms` | Instant transition |
| `--shopos-ui-motion-fast` | `180ms` | Fast transition |
| `--shopos-ui-motion-base` | `280ms` | Base transition |
| `--shopos-ui-motion-slow` | `480ms` | Slow transition |
| `--shopos-ui-ease` | `cubic-bezier(0.2, 0, 0, 1)` | Standard ease |
| `--shopos-ui-ease-out` | `cubic-bezier(0.2, 0.8, 0.2, 1)` | Ease-out |
| `--shopos-ui-ease-in` | `cubic-bezier(0.4, 0, 1, 1)` | Ease-in |
| `--shopos-ui-ease-bounce` | `cubic-bezier(0.68, -0.55, 0.265, 1.55)` | Bounce ease |

### Card primitives

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-card-radius` | `var(--shopos-ui-radius-md)` | Product-card radius |
| `--shopos-ui-card-gap` | `var(--shopos-ui-space-lg)` | Grid gap between cards |
| `--shopos-ui-card-aspect` | `4 / 5` | Card image aspect ratio |
| `--shopos-ui-card-bg` | `var(--shopos-ui-color-paper)` | Card background |
| `--shopos-ui-card-border` | `1px solid transparent` | Card resting border |
| `--shopos-ui-card-border-hover` | `1px solid var(--shopos-ui-color-hairline)` | Card hover border |
| `--shopos-ui-card-shadow` | `var(--shopos-ui-shadow-sm)` | Card resting shadow |
| `--shopos-ui-card-shadow-hover` | `var(--shopos-ui-shadow-md)` | Card hover shadow |
| `--shopos-ui-card-hover-shift` | `-2px` | Card hover translate-Y |

### Form / input primitives

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-input-height` | `44px` | Input height |
| `--shopos-ui-input-height-sm` | `36px` | Small input height |
| `--shopos-ui-input-radius` | `var(--shopos-ui-radius-sm)` | Input radius |
| `--shopos-ui-input-bg` | `var(--shopos-ui-color-paper)` | Input background |
| `--shopos-ui-input-border` | `1px solid var(--shopos-ui-color-hairline)` | Input border |
| `--shopos-ui-input-border-hover` | `1px solid var(--shopos-ui-color-ink-soft)` | Input hover border |
| `--shopos-ui-input-focus-ring` | `0 0 0 2px var(--shopos-ui-color-accent)` | Focus ring |
| `--shopos-ui-input-padding-x` | `var(--shopos-ui-space-md)` | Input horizontal padding |

### Button primitives

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-button-height` | `48px` | Button height |
| `--shopos-ui-button-height-sm` | `36px` | Small button height |
| `--shopos-ui-button-radius` | `var(--shopos-ui-radius-sm)` | Button radius |
| `--shopos-ui-button-padding-x` | `var(--shopos-ui-space-lg)` | Button horizontal padding |
| `--shopos-ui-button-font-weight` | `var(--shopos-ui-weight-semi)` | Button weight |
| `--shopos-ui-button-tracking` | `var(--shopos-ui-tracking-wide)` | Button letter-spacing |

### Badge primitives

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-badge-height` | `22px` | Badge height |
| `--shopos-ui-badge-radius` | `var(--shopos-ui-radius-pill)` | Badge radius |
| `--shopos-ui-badge-padding` | `0 var(--shopos-ui-space-sm)` | Badge padding |
| `--shopos-ui-badge-font` | `var(--shopos-ui-text-xs)` | Badge font size |

### Layout

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-container-max` | `1360px` | Max content width |
| `--shopos-ui-container-pad` | `var(--shopos-ui-space-md)` | Container side padding |
| `--shopos-ui-section-gap` | `var(--shopos-ui-space-xxl)` | Vertical section rhythm |

### Z-stack

| Token | Default | Purpose |
|---|---|---|
| `--shopos-ui-z-card` | `1` | Card layer |
| `--shopos-ui-z-sticky` | `100` | Sticky elements |
| `--shopos-ui-z-overlay` | `5000` | Overlays/drawers |
| `--shopos-ui-z-modal` | `10000` | Modals |
| `--shopos-ui-z-toast` | `10100` | Toasts |
| `--shopos-ui-z-max` | `99999` | Top layer |

### Contextual overrides in tokens.css (not new tokens)

- Accent themes via body class: `.shopos-theme.is-accent-ink` / `.is-accent-gold` / `.is-accent-forest` remap `--shopos-ui-color-accent{,-soft,-text}`.
- `@media (prefers-contrast: more)`: card/input borders → 1–2px solid ink, shadows → none, focus ring → 3px.
- `@media (prefers-reduced-motion: reduce)`: all four `--shopos-ui-motion-*` durations → `0ms`.

## (b) Design panel editable vs theme-CSS-only

The Design panel (`Design.php`, flag `shopos_core_design_panel_enabled`, default **off**) is a deliberately curated allow-list, not a theme customizer. Emit mechanism: options resolve → `resolve_values()` → `build_css()` → inline `:root{…}` block on style handle `shopos-design-tokens`, enqueued at `wp_enqueue_scripts` prio 30 (after the theme bridge's prio-21 emit) with a dependency on the `shopos-tokens` handle when registered, so overrides win. Only changed values emit; an untouched panel emits nothing. Kill switch: filter `shopos_core/design/tokens_css_enabled`.

**Owner-editable via the Design panel (7 tokens):**

| Token | Control |
|---|---|
| `--shopos-ui-palette-gold` | Accent preset select (`default`/`terracotta` `#b5532a`/`forest` `#3f7a4b`/`indigo` `#4a54b5`/`plum` `#7a3f6b`) AND individual "Accent / primary" color override (override wins over preset) |
| `--shopos-ui-palette-ink` | "Text" color field |
| `--shopos-ui-palette-ink-soft` | "Text (soft)" color field |
| `--shopos-ui-palette-paper` | "Surface" color field |
| `--shopos-ui-palette-paper-alt` | "Surface (alt)" color field |
| `--shopos-ui-palette-hairline` | "Borders" color field |
| `--shopos-ui-radius-md` | "Corner radius" number field, clamped 0–24 px |

Options: `shopos_core_design_accent`, `shopos_core_design_col_{gold,ink,ink_soft,paper,paper_alt,hairline}`, `shopos_core_design_radius`, group `shopos_core_design_group`, page slug `shopos-design`. Empty value = inherit = nothing emitted.

**Adjacent lever with no UI:** `--shopos-ui-font-body` / `--shopos-ui-font-display` map to Style Kits slots `sk_type_12` / `sk_type_2` by default; `Design::kit_slots()` re-maps them via option `shopos_core_theme_kit_slots` or filter `shopos_core/theme/kit_slots` (slugs clamped to `[a-z0-9_]`). Not a Design-panel field.

**Everything else in the (a) table is theme-CSS-only** — all semantic colors, remaining palette entries (black, mute, paper-dim, sand, red, green, amber, info), all typography scale/leading/tracking/weight tokens, spacing, all radii except `radius-md`, shadows, motion, card/input/button/badge primitives, layout, and z-stack. Override path: child stylesheet or Customizer CSS on `:root`/`.shopos-theme`. Semantic status colors (red/green/amber/info) are intentionally excluded from the panel — they carry meaning, not brand.

**Sanitizers (defence in depth):**
- `sanitize_hex()`: `^#[0-9a-fA-F]{3,8}$` else `''` (matches Settings_Hub `color` sanitizer).
- `sanitize_value()` (re-run in `build_css()` on every value): rejects `; { } < > @ \`, then allows only `[a-zA-Z0-9#%.,()/ _-]`.
- `build_css()` also validates the var name itself: `^--shopos-ui-[a-z0-9-]+$`.
- Accent option whitelisted against preset keys; radius clamped to 0–24 and emitted as `Npx`.

## (c) Supported settings field types (Settings_Hub schema)

Module schemas return `key => { label, type, description, default, choices (select), section (optional grouping) }`. Rendered by `Settings_Hub::render_field()`, sanitized by `sanitizer_for()`. Options are named `{prefix}_{module_id}_{key}` via `Module_Base::option_name()`; group `shopos_{module_id}`; `Module_Base::get_option()` falls back to schema default. `Module_Base::label_fields()` mass-generates `label_<key>` text fields from a Labels defaults map.

| Type | Renders as | Sanitizer | Extra schema keys |
|---|---|---|---|
| `text` (default) | text input `regular-text` | `sanitize_text_field` | — |
| `textarea` | 4×60 `large-text` textarea | `sanitize_textarea_field` | — |
| `checkbox` | single checkbox, value 1 | bool → 1/0 | `checkbox_label` |
| `select` | dropdown | `sanitize_text_field` (value not re-whitelisted) | `choices` |
| `color` | text input with `.shopos-color-field` → wp-color-picker (enqueued on all ShopOS screens) | hex regex `^#[0-9a-fA-F]{3,8}$` else `''` | — |
| `number` | `type=number` `small-text` | numeric → `$v + 0` else 0 | — |
| `range` | `type=range` + live `<output>` | numeric, clamped to min/max | `min`, `max`, `step`, `unit` |
| `media` | hidden attachment-ID input + thumbnail preview + pick/remove buttons (`wp.media`, enqueued only if any module declares a `media` field — none do today) | `absint` | `button_label` |
| `typography-select` | dropdown, each option styled `font-family: <value>` | whitelist against `choices`, fallback to default | `choices` |
| `multiselect` | multi-`<select>`, submits `name[]` | array, each item whitelisted against `choices` | `choices` |
| `email` | falls through to plain text input (sanitizer-only type) | `sanitize_email` | — |
| `url` | falls through to plain text input (sanitizer-only type) | `esc_url_raw` | — |

Note: the file's docblock advertises only `text | textarea | checkbox | select | color | number`; the switch statements support the full set above. `email`/`url` have dedicated sanitizers but no dedicated renderer.

## (d) Elementor widget conventions (`shopos-core/src/Core/Elementor/`)

Two files: `Category.php`, `Widget_Base.php`. No per-widget controls live here — widgets (Category Slider, Product Slider) extend the base.

- **Panel category** (`Category.php`): slug `shopos`, title "ShopOS", icon `eicon-woocommerce`, registered on `elementor/elements/categories_registered` (no-op without Elementor), wired once from `Plugin::boot()`.
- **Base class** (`Widget_Base extends \Elementor\Widget_Base`): all ShopOS widgets extend it. Conventions it establishes:
  - `get_categories()` returns `[ 'shopos', 'woocommerce-elements', 'general' ]` — ShopOS panel first, Woo + General kept so existing placements never vanish.
  - `get_term_options( $taxonomy = 'product_cat' )` — term-id ⇒ name map for SELECT2 controls, capped at 200 terms for editor responsiveness.
  - `slider_int()` / `slider_float()` — coerce SLIDER control values, handling both `['size'=>N,'unit'=>…]` and legacy scalar shapes; float variant exists for fractional steps (e.g. `per_view_mobile` 1.4 = card + peek).
  - `is_elementor_edit_mode()` — guarded editor-preview detection.
  - `resolve_direction( $setting )` — a Direction control convention of `auto|ltr|rtl`, `auto` resolving via `is_rtl()`.
  - `ids_array()` is deliberately NOT in the base — the two slider widgets' copies differ (Product Slider also splits comma/space strings), each keeps its own.
- Token linkage: widgets/modules consume design tokens via `var(--shopos-ui-*, <fallback>)` chains where `--e-global-*` Elementor Style-Kit values are the fallback, never the target — the Design panel writes semantic tokens only and never touches Elementor page layout or Style Kit values.

---

# §B — Synthesis & prioritized PR list

# ShopOS Design/Behavior Control Audit — Synthesis & PR Plan

## 1. Coverage Scoreboard

| Module | Behavior coverage | Design coverage | Hardcoded findings | Worst gap |
|---|---|---|---|---|
| VariationSwatches | good (15 settings + per-term meta + flags) | **poor** — two private var blocks (`--shopos-*`, `--eshop-*`), raw hex | ~46 | Primary CTA red `#ca2b1d` + pill language baked; Design panel has zero effect on the store's most prominent button (high ×3) |
| ShopFilters | good (16 labels + facet matrix + style select) | partial — refined style tokened, **classic default is not** | ~49 | Default filter panel entirely un-tokenized; mobile Apply hardcoded black (high ×2) |
| ProductSlider | good (rich Elementor controls) | partial — controls exist but colors route through hardcoded `--cs-*` | ~35 | Whole module bypasses tokens via CategorySlider's oklch `--cs-*`; mobile height force-220px; CTA unstylable (high ×3) |
| CategorySlider | good (rich Elementor controls) | partial — same `--cs-*` parallel system, zero token consumption | ~30 | Rebrand never reaches the slider; `--cs-accent` is dead code (high) |
| RestockNotify | good (legacy screen, 18 options) but bypasses Settings_Hub | **poor** — zero tokens; inline critical CSS re-hardcodes everything | ~30 | Zero token consumption on the OOS conversion surface + RTL baked into markup (high ×2); **likely live subscribe AJAX bug** |
| QuickView | good (6 labels + filters + template override) | partial — drawer tokened, trigger fully baked | ~31 | White-circle trigger unbrandable + position pinned, on every card site-wide (high ×2) |
| HoverSwap | partial (mode + arrows only) | **poor** — zero token consumption | 10 | Gallery-slider 1rem radius ignores `--shopos-ui-card-radius` (high) |
| ProductPage | good (coupon/urgency/labels/button_color) | good — heavy token consumption | 17 | 1rem radius literals on the PDP's two dominant surfaces bypass the radius token (high) |
| PageTransitions | partial (on/off + label only) | partial — colors tokened, all geometry/motion not | 16 | Cross-fade duration/easing fixed — the most visible motion in the suite (high) |
| Search | good (9 settings + labels + shortcode/widget) | good — near-full token consumption | 23 | OOS products always excluded from search, ignoring Woo's setting (high, behavior) |
| MyAccount | poor (on/off only, empty schema) | good — fully token-aliased colors/type/space | 28 | Primary buttons ignore accent system (`--fma-accent` defined and never used) (medium) |
| InfiniteScroll | good (7 settings) | **good — the model pattern** (settings → `--shopos-ui-is-*` tokens) | 12 | End/error message alien Tailwind gray; tap-to-navigate IIFE has no off switch (medium) |
| CheapestDefaultVariation | good (3 settings + filters) | n/a (no storefront surface) | 0 | Per-product strategy is raw post meta, no UI (low) |
| ProductFeed | good (2 settings + button) | n/a (XML + wp-admin only) | 3 (admin-only) | All low; nothing storefront-visible |
| VariableStockFix | good (1 setting + audit UI) | n/a (wp-admin only) | 6 (admin-only) | Per-product opt-out filter-only (medium) |

## 2. Cross-Module Themes → Shared Foundation Work

These repeat in 3+ modules and must land as **one tokens.css PR first**, or every module PR invents its own value again:

1. **No overlay-scrim token.** Four bespoke scrims: PageTransitions `rgba(15,18,26,.35)` (css:44), QuickView `rgba(0,0,0,.45)` (css:87), Search `rgba(17,17,17,.5)` (css:213), ShopFilters `rgba(0,0,0,.45)` (css:496). → `--shopos-ui-overlay-scrim` (+ optional `--shopos-ui-overlay-blur` for page-transitions.css:45).
2. **No accent-hover/-active tokens.** RestockNotify `#333` (frontend.css:96), VariationSwatches `#a8241a`/`#8f1e15` (shopos-swatches.css:198-199, 942), ProductPage flattens all three CTA states to one hex (Template_Loader.php:268-269). → `--shopos-ui-color-accent-hover` / `-active` (color-mix defaults from accent so the Design panel drives them for free).
3. **No spin-duration token.** PageTransitions 0.7s (css:73), RestockNotify 0.8s (frontend.css:101), VariationSwatches 720ms (shopos-swatches.css:1016). → `--shopos-ui-motion-spin`.
4. **No hover-scale token.** Both sliders zoom images `scale(1.015)` (category-slider.css:285, product-slider.css:199). → `--shopos-ui-card-hover-scale` (sibling of existing `-shift`).
5. **Parallel private palettes instead of token chains.** `--cs-*` (category-slider.css:13-17), `--fma-*` (my-account.css:16-55, aliases — fine), `--shopos-*`/`--eshop-*` (shopos-swatches.css:186-204, shopos-shop-swatches.css:20-31). The fix pattern is proven by InfiniteScroll and Wave 4.2 `--cs-arrow-*`: redefine each local var as `var(--shopos-ui-x, <current literal>)` — byte-identical when tokens absent.
6. **Motion literals everywhere.** 180ms/`.18s`/`.25s`/`.3s`/`.2s` + two recurring cubic-beziers across MyAccount (×6), CategorySlider, ProductSlider, HoverSwap, QuickView, ShopFilters, RestockNotify, VariationSwatches — all exact or near matches for `--shopos-ui-motion-fast/base` + `--shopos-ui-ease/-out`. Mapping these is mechanical and gives free reduced-motion zeroing.
7. **No breakpoint vocabulary.** Hardcoded and mutually inconsistent: 1024/640 (both sliders), 1024/768 (ProductPage), 768 (Search, ShopFilters, VariationSwatches), 880/600/640 (MyAccount), 480 (RestockNotify, VariationSwatches) — several duplicated in JS (product-page.js:160, shop-filters.js:20, shopos-swatches.js:720). Media queries can't read `var()`; ship documented canonical constants (`--shopos-ui-bp-tablet: 1024px`, `--shopos-ui-bp-mobile: 640px` as reference values + a PHP/JS constants source). Full unification is its own project — the foundation PR just declares the canon.
8. **Focus-ring token bypassed.** MyAccount inputs (my-account.css:647-655), RestockNotify (frontend.css:77), VariationSwatches (shopos-swatches.css:1216, shop css:834), ShopFilters wp-admin-blue `#2271b1` on the storefront (shop-filters.css:217), Search outline convention (search.css:180-181, 321-322). `--shopos-ui-input-focus-ring` exists; consuming it also restores the prefers-contrast upgrade.
9. **Button/input/badge primitives exist but are skipped.** MyAccount (css:807-816), ProductSlider button (css:329-347), RestockNotify (frontend.css:63-69, 89-94), ShopFilters, Search field heights (search.css:256, 300-301, 331).
10. **JS constants duplicating CSS values.** product-page.js:114 (0.14 fill floor) / product-page.css:191; shop-filters.js:124 (350ms) / css:514; shopos-shop-swatches.js:695 (340ms) / css:691; RestockNotify frontend.js:94-96,165,168,177,199 (jQuery durations); category-slider js:502 (12%) / css:364. Pattern: read from `getComputedStyle` or localized payload.

## 3. Prioritized PR List

### Foundation (land first)

---

**PR-01 `tokens/foundation-vocabulary`** — Effort: **S**
- **Scope:** `shopos-theme/assets/css/shopos-tokens.css` only (+ token reference doc).
- **Adds new tokens:** `--shopos-ui-overlay-scrim`, `--shopos-ui-overlay-blur`, `--shopos-ui-color-accent-hover`, `--shopos-ui-color-accent-active` (color-mix from accent; update the three `is-accent-*` body-class blocks), `--shopos-ui-motion-spin`, `--shopos-ui-card-hover-scale`, `--shopos-ui-tracking-caps` (0.14em, category-slider.css:48-50), `--shopos-ui-weight-light` (300, category-slider.css:57-60), `--shopos-ui-drawer-width` (QuickView css:100), `--shopos-ui-swatch-size` (shop-filters.css:185-186), `--shopos-ui-focus-outline`, `--shopos-ui-measure` (product-page.css:284 vs :587 — pick 65ch or 75ch), plus documented canonical breakpoint constants (`--shopos-ui-bp-tablet`/`--shopos-ui-bp-mobile`, reference-only).
- **Rationale:** Every module PR below consumes these. Pure additions, zero visual change, unblocks parallel work. Skipped: `--shopos-ui-rail-fade`, `--shopos-ui-scrubber-min-thumb`, `--shopos-ui-placeholder-tone-*`, per-module one-offs — add only when a second consumer appears.

### Highest storefront visibility

---

**PR-02 `sliders/cs-token-bridge`** — Effort: **S** — *the single highest-value diff in the audit*
- **Scope:** `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:13-17` — redefine `--cs-bg` → `var(--shopos-ui-color-paper, oklch(0.985 0.005 80))`, `--cs-ink` → `var(--shopos-ui-color-ink, oklch(0.18 0.01 60))`, `--cs-mute` → `var(--shopos-ui-color-ink-muted, oklch(0.45 0.01 60))`, `--cs-line` → `var(--shopos-ui-color-hairline, oklch(0.88 0.008 70))`, `--cs-accent` → `var(--shopos-ui-color-accent, …)`.
- Also: delete dead `--cs-accent` Elementor control (CategorySlider/Widget.php:293-303) or wire it to a rule — currently no CSS consumes it, but ProductSlider's sale badge *does* use `--cs-accent`, so verify before deleting; likely keep and just chain.
- **Rationale:** Five lines retrofit BOTH sliders (ProductSlider inherits this palette) into the Design panel and accent body-classes. Byte-identical rendering when tokens absent — the proven Wave 4.2 pattern. Fixes the #1 high-impact gap in two modules at once.

---

**PR-03 `variation-swatches/tokenize-buy-box`** — Effort: **L**
- **Scope:** `VariationSwatches/assets/css/shopos-swatches.css`: local var block 186-204 chained to tokens (ink/ink-muted/hairline/paper/paper-dim, accent + new accent-hover/-active for 197-199, success 200, radius-pill 201, radius-xl 202, space-sm 203, motion-fast+ease 204); price `1.5rem`→text-xl (:48); buybox max 640px (:216, NEW token candidate — accept as constant for now); swatch placeholder/ring (:354, :362); tooltip (:435); literal `999px` repeats (:492, 621, 675, 766, 923); `#fff` text (:518, 759, 927); OOS red `#b91c1c`→danger (:580); button heights 54/58/52→button-height (:619, 754, 920); sticky bar surface/border/shadow (:866) + transition (:871); **sticky-bar raw `#ca2b1d`/`#a8241a` (:924, 942)**; ripple (:987), spinner→motion-spin (:1016), checkmark (:1044), toast block (:1125-1135), focus ring (:1216); breakpoints noted (:808, 858).
- `shopos-shop-swatches.css`: `--eshop-*` block 20-31 chained; price 18px→text-lg (:100); placeholder/ring (:283, 290); tooltip (:358); `#fff`/`#000` (:410, 499, 514); +N hover (:478); toast (:679, 681, 685, 691); **scope bug — body-mounted toasts reference out-of-scope `var(--shopos-success)` (:712) and `var(--eshop-danger)` (:736), fallbacks always win and error red `#d14c4c` mismatches `#ca2b1d`** → point both at `--shopos-ui-color-success/danger`; focus ring (:834).
- JS: shopos-swatches.js:808 (220ms scroll), :720 (768px matchMedia); shopos-shop-swatches.js:612 (toast TTLs), :695 (340ms dismiss — track the CSS token).
- **Rationale:** The buy box, Buy Now button, and sticky bar are the most conversion-critical, most visible surfaces in the suite, and three of the audit's high gaps live here (baked red CTA, baked pill shape, panel-blind palette). Preserve the `!important`-heavy specificity — chain values, don't restructure selectors.

---

**PR-04 `shop-filters/tokenize-classic-and-fix-z`** — Effort: **M**
- **Scope:** `ShopFilters/assets/css/shop-filters.css` — classic style: checkbox accent `#111`→accent (:59); **mobile Apply `#111`/`#fff`→accent/accent-text (:583-586)**; swatch selected ring (:206); focus ring `#2271b1`→input-focus-ring (:217); counts `#767676`→ink-muted (:52, :119); chips/panels `#f6f6f6`/`#fff`/rgba-black borders (:200, 509, 573, 76, 88, 138, 188, 480, 598); scrim→overlay-scrim (:496); drawer shadow/radius (:512, 511); pill (:198); weights 600→weight-semi (:34, 131, 274, 551); font sizes (:11, 53, 80, 201, 301, 383, 247, 394, 477, 552, 563); swatch size→new token (:185-186, 196); motion (:63, 104, 84, 191, 249, 395, 207, 332, 485, 588, 603, 499, 514, 526); stagger (:529-536 — leave literal, `ponytail:` comment); breakpoints noted (:413, 424, 471); refined list max-height (:354 — leave).
- **Z-stack fix:** overlay and drawer both use `--shopos-ui-z-max` with different fallbacks (99998/99999) → `--shopos-ui-z-overlay` / `--shopos-ui-z-modal`.
- JS: shop-filters.js:20 (breakpoint), :124 (350ms fallback — derive from motion-base).
- **Rationale:** The classic style is the *default* — every store's archive page shows an un-tokenized panel today. Ships site-wide (head-enqueued on every page). Two high gaps.

---

**PR-05 `restock-notify/tokenize-and-fix-subscribe`** — Effort: **L**
- **Step 0 (blocker, verify first):** frontend.js:81 posts action `shopos_restock_subscribe` but handlers are `wp_ajax(_nopriv)_rsn_subscribe` (legacy/includes/class-shopos-restock-ajax.php:7-8) — if confirmed, every subscribe fails. Fix + regression check before any styling.
- **Scope:** `RestockNotify/assets/css/frontend.css` full tokenization: card (:17-21, 23, 27), bell circle→accent/accent-text (:35-36), heading (:43-46), description (:51-53), gaps (:56-57), inputs (:63-69, 76), focus→input-focus-ring (:77), GDPR (:85-87), button→accent + new accent-hover (:89-94, 96), spinner→motion-spin (:101), success (:104-106, 108), error→danger/danger-soft (:111-114), entrance animations→motion-base/ease-out (:11, 103, 110, 116), breakpoint noted (:119).
- **Inline critical CSS** (Frontend.php:467-496) must mirror every change as `var(--shopos-ui-*, <literal>)`; kill the `-apple-system` stack→`var(--shopos-ui-font-body, …)` (Frontend.php:471). JS fallback cssText (Frontend.php:315). Do NOT patch the dead legacy copies (legacy/includes/class-shopos-restock-{email,frontend,stock-monitor}.php — never required).
- **RTL fix:** hardcoded `dir="rtl"` (Frontend.php:501) and `direction:rtl` (frontend.css:8-10, 24-25, 73-74) → `is_rtl()` / logical properties.
- **Email branding:** Email.php:241, 254-259, 237, 247, 262-267 — resolve Design-panel accent/ink options server-side (emails can't read CSS vars). Small PHP helper reading `shopos_core_design_*` options.
- JS durations (frontend.js:94-96, 165, 168, 177, 199) — leave as-is unless trivial (jQuery, low value).
- **Rationale:** The OOS form is conversion-critical and completely brand-blind; the RTL bake breaks any LTR store. admin.css:1-69 excluded (wp-admin).

---

**PR-06 `hover-swap-quickview/tokenize-card-chrome`** — Effort: **M** (one PR — QuickView hard-depends on HoverSwap's card-slider assets)
- **HoverSwap:** hover-swap.css:28 (0.35s→motion-base/ease); card-slider.css:17 (**1rem→`var(--shopos-ui-card-radius)` — the high gap**), :55-56 (26px arrow — keep literal, `ponytail:` comment), :59 (→radius-round), :60 (white puck→color-mix paper 90%), :61 (`#111`→ink), :64 (→motion-fast), :65 (→shadow-sm), :70/74 (6px inset — keep). Gallery_Slider.php:165, 168 (SVG geometry — leave, icon plumbing). Preserve the doubled-selector `.a.a` specificity hack.
- **QuickView:** quick-view.css — trigger: offsets :34-35 (keep 12px literal), size 36px→button-height-sm (:41-42), :46→radius-pill, **`#fff`/`#000`→paper/ink (:47-48, 56-57)**, shadow (:50), transition→motion-fast/ease-out (:51); scrim→overlay-scrim (:87), fade (:89); drawer width→drawer-width token (:100), shadow→shadow-lg (:105), slide (:108); spacing sweep (:136, 137, 154-155, 175, 204, 217, 222, 229, 235, 242, 249, 253); :159→radius-pill; :224→`var(--shopos-ui-leading-tight, 1.25)`.
- **Rationale:** Trigger + card image effects appear on every product card site-wide; two high gaps (radius mismatch, unbrandable trigger). Trigger icon swap / position setting skipped — add when a store actually asks.

---

**PR-07 `product-page/radius-and-cta-states`** — Effort: **S**
- **Scope:** product-page.css:126 and :231 (**1rem→radius-lg — the high gap, PDP's two dominant surfaces**); :429 (hover lift→card-hover-shift); :284 + :587 (65ch/75ch→`--shopos-ui-measure`, unify); coupon-notice.css:41 (0.4em gap — keep, em-relative is fine). Template_Loader.php:268-269 — derive `--shopos-primary-hover/-active` via color-mix from the owner's `button_color` instead of flattening three states to one hex.
- Leave as documented constants with comments: progress bar :178, :191 + js:114 twin; breakpoints :65/142/870/876 + js:160, :731; sticky reservation :872; sizes attr single-product.php:138; del scale :273.
- **Rationale:** Small diff, closes the module's only high gap, and the module is otherwise the best-tokenized storefront surface.

---

**PR-08 `page-transitions/tokenize`** — Effort: **S**
- **Scope:** page-transitions.css — :23-24 (fade→motion-fast/ease — **the high gap**; token routing also lets theme-level motion overrides tune the suite's most-seen animation), :44→overlay-scrim, :45→overlay-blur, :48→motion-fast/ease, :59→space-md, :62→radius-lg, :63→space-lg/xl, :64→shadow-md, :71/:94→hairline track, :73→motion-spin, :77-79→text-sm/weight-semi/leading-snug. Fix drifted fallbacks (z 100000→99999 at :40; ink `#1c2230`→`#1b1b1b` at :61, :72).
- Skipped: independent layer toggles, trigger-selector filter (js:25-38), 8s timeout (js:71) — behavior PR territory, add on request.

### Long tail (mechanical sweeps)

---

**PR-09 `my-account/primitives-and-accent`** — Effort: **M**
- **Scope:** my-account.css — **wire the dead `--fma-accent`/`--fma-accent-tx` (:23-24) into primary buttons (:808-810, 829-831)** so accent presets finally restyle account CTAs; consume button primitives weight/tracking/padding-x/radius (:807-816); input focus→input-focus-ring (:647-655); six 180ms transitions→motion-fast/ease (:218, 331, 455, 587, 643, 820); badge primitives on status pills (:416-417, 423, 430-432); spacing sweep (:89, 91, 108, 212, 381, 442-445, 471, 586, 634, 727, 856); fix fallback drift in the alias block (radius 8px vs 6px, ink `#0a0a0a`). Leave as constants with comments: sidebar 264px (:86), accent bar (:253-257), mask fade (:191-196), underline (:328-329), breakpoints (:99, 106, 670, 680 — unify 600 vs 640 while there), minmax (:500). Canvas 1180px→document as deliberate (or `--shopos-ui-container-max` if owner wants parity).
- **Rationale:** Logged-in page, lower traffic than shop/PDP, and colors already work — this is completion, not rescue.

---

**PR-10 `search/scrim-and-primitives`** — Effort: **S**
- **Scope:** search.css — :213→overlay-scrim; :256→input-height, :331→button-height, :300-301→input-height; :68→`var(--shopos-ui-leading-snug, 1.3)`; :61, :95, :108 (small gaps→space-xs); focus outlines :180-181, :321-322→focus-outline token. Leave as constants: thumb 52px (:50-51), palette geometry (:223, 198, 393, 19, 224), micro-interactions (:176, 186, 372), breakpoint (:391), SVG icons (Frontend.php:79, search.js:30, :33 — note the manual-sync duplication in a comment).
- **Rationale:** Already the second-best token citizen; this is polish.

---

**PR-11 `sliders/long-tail-sweep`** — Effort: **M** (after PR-02; both files, one PR)
- **CategorySlider css:** spacing :41, 43, 325; eyebrow :48-50 (→text-xs/weight-medium/tracking-caps); headline :57-60 (→text-xxl/leading-tight/tracking-tight/weight-light) — killing the fixed 36px/28px overrides at :443, :452 since text-xxl is already fluid; :64-65; motion :233, 273, 313, 341, 368, 381, 419; placeholder :238-239 (keep hue mechanism), :247-249 (→font-mono/text-xs), :251-252, :254 (→radius-xs); shape radii :259-261 (rect→radius-md; soft/pill stay literal + comment), ring radii :276-278 (derive); zoom :285→card-hover-scale; card name :298-299, count :303-304; progress :364 (+js:502 twin), :369; foot :393-394; empty state :435; edge fade :153, 456-457 (keep, comment); arrows Widget.php:798, 801, css:120-121 (leave).
- **ProductSlider css:** ring :143-146 (→hairline/radius/motion-base/ease-out); zoom :183, 199 (→motion-slow/ease-out, card-hover-scale); shape radii :187 (1rem→radius-lg), :191 (→radius-md); sale badge :214, 219, 222-225, 230, 236 (→badge tokens; 0.1em tracking→tracking-caps or wide — decide once); title/price :249, 251-254, 268, 273-275, 280, 288; **button :329-347 → button primitives + text-sm** (radius: keep pill deliberately via literal + comment, or button-radius — decide); :366; pagination :421, 426; mobile overrides :440, 453 (220px — see PR-13), :456-457, :461-463; :471; breakpoints :106, 111, 435, 444 + Widget.php:1001 (note canon); Widget.php:1331, 1334, 1404-1405 (leave).
- **Rationale:** After PR-02 the colors are right; this makes type/space/motion/radius follow the system. Mechanical, low-risk, big file count — one sweep, not fifteen micros.

---

**PR-12 `infinite-scroll/polish`** — Effort: **S**
- **Scope:** infinite-scroll.css — :71 `#6b7280`→ink-muted (**the medium gap — alien Tailwind gray**), :72→text-sm, :73→tracking (pick normal, don't invent), :70→space-lg/md, :37→`var(--shopos-ui-card-aspect, 3 / 4)`, :57→ease-out, :79-83 margins→space tokens (divider dims stay), :91→button primitives, :18, :38/48/81 (14px→space-sm), :97 leave. js:110/670 fade stagger — add `fade_stagger_ms` setting alongside its siblings only if asked; skip for now. Dead-UI cleanup: remove `hybrid_threshold` from the schema and the single-option `trigger_mode` select (or restore button mode — owner call, flag in PR description).
- **Rationale:** Already the reference implementation; small finish.

### Behavior/settings (separate from styling sweeps)

---

**PR-13 `settings/owner-control-bundle`** — Effort: **M**
- **Search OOS setting** (highest behavior gap): `include_out_of_stock` checkbox defaulting to follow Woo's hide-OOS setting; touch Ajax.php:104, Results_Query.php:141, 235, 269, 319, 335.
- **ProductSlider mobile card height**: Elementor `card_height_mobile` control feeding the 220px override (product-slider.css:453).
- **VariationSwatches sticky-bar + Buy Now toggles**: two checkboxes in the existing 15-key schema.
- **ProductPage sticky-bar toggle** (mirrors above).
- **InfiniteScroll tap-to-navigate off switch** (js:901-940 IIFE — medium gap, no escape hatch today).
- **ShopFilters sort labels**: add `label_sort_*` fields for Module::orderby_label() values — the only visible panel strings not owner-editable.
- **Rationale:** Every item is a one-checkbox/one-control diff against an existing Settings_Hub schema; bundled because they're the same shape. Skipped: QuickView trigger position/per-category suppression, PageTransitions layer split, HoverSwap image-size setting, per-product CheapestDefaultVariation UI — all low-demand, add when a store asks.

### Explicitly not PRs
- **ProductFeed, VariableStockFix, CheapestDefaultVariation** — no storefront surface; admin inline styles (ProductFeed Module.php:306, 313, 334; VariableStockFix Module.php:505-518) are wp-admin palette matches, not brand leaks. Do nothing.
- **RestockNotify Settings_Hub migration** (legacy screen → schema) — real debt but orthogonal to "owner controls styling"; separate initiative.
- **Breakpoint unification across modules** — PR-01 declares the canon; actually moving 8 modules onto it is a follow-up project, not a blocker for token coverage.

### Suggested land order
PR-01 → PR-02 (5-line, huge win) → PR-03 → PR-04 → PR-05 (bug first) → PR-06 → PR-07 → PR-08 → PR-13 → PR-09 → PR-10 → PR-11 → PR-12. PRs 03-08 are independent after PR-01/02 and can run in parallel worktrees.

---

# §C — Per-module audit detail

## CategorySlider (shopos-core/src/Modules/CategorySlider)

**Behavior controls today:**
- Elementor: eyebrow / headline / headline_mute (header text)
- Elementor: limit (max categories, 1-50)
- Elementor: orderby (name/count/slug/menu_order) + order (ASC/DESC)
- Elementor: hide_empty (skip empty categories)
- Elementor: parent_only (top-level terms only)
- Elementor: child_of (sub-categories of one parent)
- Elementor: include / exclude (explicit term multi-selects)
- Elementor: per_view / per_view_tablet / per_view_mobile (cards per view per breakpoint)
- Elementor: direction (auto/ltr/rtl)
- Elementor: snap (none/card/page scroll snap)
- Elementor: mouse_drag (enable desktop drag-scroll)
- Elementor: show_arrows (arrow buttons on/off)
- Elementor: indicator (progress bar / dots / none; supersedes legacy show_progress toggle)
- Elementor: show_progress (legacy back-compat alias)
- Elementor: autoplay + autoplay_delay (1000-15000ms) + loop (wrap at end)
- PHP filter shopos_core/category_slider/query_args (term query args)
- PHP filter shopos_core/category_slider/render_card (per-card HTML)
- JS: window.ShopOSCategorySlider.init(scope) re-init hook

**Design controls today:**
- Elementor: shape (circle/soft/rect/pill card shape)
- Elementor: show_count (hover/always/none)
- Elementor: accent color -> --cs-accent (note: --cs-accent is declared but never consumed by any CSS rule)
- Elementor: ring_color -> --cs-ring-color (hover ring)
- Elementor: cs_bg_color / cs_ink_color / cs_mute_color / cs_line_color -> --cs-bg/--cs-ink/--cs-mute/--cs-line (Wave 4.2)
- Elementor: cs_arrow_size (24-96px), cs_arrow_radius (px/%), cs_arrow_duration (0-1000ms) -> var(--cs-arrow-*) with fallbacks
- Elementor: typography group controls for eyebrow, headline, card name + name_color
- Elementor: gap (4-48px) and card_height (180-420px) -> inline --cs-gap/--cs-card-h
- Fonts inherit from theme/Elementor by default (css line 27 comment) — Style Kit body font flows into cards/headline unless overridden
- prefers-reduced-motion honored in CSS (transitions off) and JS (autoplay never starts)
- NO --shopos-ui-* token is consumed anywhere in the module — all --cs-* defaults are hardcoded oklch values

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:13` | --cs-bg: oklch(0.985 0.005 80) | var(--shopos-ui-color-paper, oklch(0.985 0.005 80)) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:14` | --cs-ink: oklch(0.18 0.01 60) | var(--shopos-ui-color-ink, oklch(0.18 0.01 60)) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:15` | --cs-mute: oklch(0.45 0.01 60) | var(--shopos-ui-color-ink-muted, oklch(0.45 0.01 60)) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:16` | --cs-line: oklch(0.88 0.008 70) | var(--shopos-ui-color-hairline, oklch(0.88 0.008 70)) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:17` | --cs-accent: oklch(0.55 0.12 35) | var(--shopos-ui-color-accent, ...) — also note --cs-accent is dead: no rule consumes it |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:41` | padding-bottom: 28px (head) + margin-bottom: 32px at line 43, margin-top: 28px on .cs-foot at line 325 | --shopos-ui-space-lg / --shopos-ui-space-xl |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:48` | eyebrow font-size: 11px; font-weight: 500; letter-spacing: 0.14em (lines 48-50) | --shopos-ui-text-xs / --shopos-ui-weight-medium / NEW TOKEN --shopos-ui-tracking-caps (0.14em is wider than tracking-wide 0.04em) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:58` | headline font-weight: 300; font-size: clamp(28px, 3.4vw, 48px); line-height: 1.05; letter-spacing: -0.02em (lines 57-60) | --shopos-ui-text-xxl / --shopos-ui-leading-tight / --shopos-ui-tracking-tight; weight 300 = NEW TOKEN --shopos-ui-weight-light |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:65` | headline-mute font-size: 14px, font-weight: 400 (line 64) | --shopos-ui-text-sm / --shopos-ui-weight-regular |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:153` | mask-image edge fade 24px (and 4px mobile variant at lines 456-457) | NEW TOKEN --shopos-ui-rail-fade (shared with ProductSlider rail) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:233` | transition: transform .5s cubic-bezier(.2,.7,.2,1) (image hover zoom) | var(--shopos-ui-motion-slow) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:238` | placeholder stripes oklch(0.78 0.04 var(--cs-hue)) / oklch(0.74 0.05 var(--cs-hue)) (lines 238-239) | NEW TOKEN pair --shopos-ui-placeholder-tone-1/-2 (lightness/chroma are the brand decision; hue is per-term) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:247` | font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 9px; letter-spacing: .08em (placeholder label, lines 247-249) | var(--shopos-ui-font-mono) / --shopos-ui-text-xs |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:251` | color: rgba(40, 30, 20, .55); background: rgba(255, 255, 255, .55) (lines 251-252) | --shopos-ui-color-ink-muted / --shopos-ui-color-paper with opacity |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:254` | border-radius: 3px (placeholder label chip) | --shopos-ui-radius-xs |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:259` | shape radii: soft 18px (259), rect 6px (260), pill 28px (261) | rect -> --shopos-ui-radius-md; soft/pill -> NEW TOKENS or --shopos-ui-radius-lg/-xl approximations |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:273` | transition: inset .25s cubic-bezier(.2,.7,.2,1), border-color .25s (hover ring) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:276` | ring radii per shape: 24px / 12px / 34px (lines 276-278) | derive from the shape radius tokens above (radius + ring inset) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:285` | transform: scale(1.015) card image hover zoom | NEW TOKEN --shopos-ui-card-hover-scale (sibling of --shopos-ui-card-hover-shift) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:298` | card name font-size: 18px; letter-spacing: -0.01em (lines 298-299) | --shopos-ui-text-lg / --shopos-ui-tracking-tight (typography control exists but the default should be token-driven) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:303` | count font-size: 11px; letter-spacing: 0.06em (lines 303-304) | --shopos-ui-text-xs / --shopos-ui-tracking-wide |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:313` | transition: opacity .25s, transform .25s (count hover reveal) | var(--shopos-ui-motion-base) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:341` | transition: height .18s ease, background-color .18s ease (progress bar; also .18s at lines 368, 381, 419) | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:364` | progress thumb width: 12% minimum (mirrored in JS as parentW * 0.12 at js:502) | NEW TOKEN --shopos-ui-scrubber-min-thumb (or accept as constant) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:368` | transition: transform .25s cubic-bezier(.2,.7,.2,1) (progress bar movement) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:369` | border-radius: 2px (progress bar) | --shopos-ui-radius-xs |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:393` | foot label font-family: ui-monospace, "SF Mono", Menlo, monospace; font-size: 11px (lines 393-394) | var(--shopos-ui-font-mono) / --shopos-ui-text-xs |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:435` | .cs-empty border-radius: 8px, padding: 48px (editor-only empty state; color fallbacks at 433-434 are var()-covered) | --shopos-ui-radius-md / --shopos-ui-space-xl |
| `shopos-core/src/Modules/CategorySlider/assets/css/category-slider.css:443` | headline font-size overrides 36px tablet / 28px mobile (line 452) inside hardcoded 1024px/640px media queries | keep clamp() from the token scale (--shopos-ui-text-xxl is already fluid) instead of fixed per-breakpoint px |
| `shopos-core/src/Modules/CategorySlider/Widget.php:798` | arrow SVG stroke-width="1.4", 14x14 icon (also line 801; CSS 14px at css:120-121) | acceptable as icon plumbing, or NEW TOKEN --shopos-ui-icon-stroke if icon weight becomes a brand lever |

**Gaps (owner cannot change):**
- **[high]** Store-wide rebrand does not reach the slider: --cs-bg/ink/mute/line/accent defaults are hardcoded oklch, not chained to --shopos-ui-* tokens, so the Design panel (Text/Surface/Borders/Accent fields) and accent body-classes have zero effect; colors must be re-picked on every widget instance
- **[medium]** Motion is fixed except the arrow hover duration: image zoom .5s, ring/progress/count .25s, bar/dot .18s, and both cubic-bezier easings are unconfigurable and ignore --shopos-ui-motion-*/--shopos-ui-ease-*
- **[medium]** Responsive breakpoints hardcoded at 1024px/640px — do not follow Elementor's configurable breakpoints, so per-store breakpoint customization silently mismatches (per_view_tablet/mobile switch at fixed widths)
- **[medium]** Edge fade mask (24px desktop / 4px mobile) is always on and unconfigurable — cards are always clipped by a fade at rail edges regardless of design intent
- **[medium]** Typography of the count badge, foot 'NN / NN' label, and placeholder label (monospace 9-11px uppercase) has no control — only eyebrow/headline/name have typography groups
- **[low]** Shape variant radii (soft 18px, rect 6px, pill 28px, ring 24/12/34px) are fixed per shape — owner picks a shape but cannot tune its radius
- **[low]** Interaction physics baked into JS: momentum decay 0.94, drag gates (10px/80ms/1.2 axis ratio), arrow/autoplay scroll step 85% of viewport, dot page = 1 viewport — no setting or filter
- **[low]** Hover treatment fixed: image scale 1.015 and ring inset -8px→-10px are not adjustable (ring color is)
- **[low]** Placeholder stripe palette derived from crc32(slug) hue with fixed lightness/chroma — owner cannot align no-thumbnail placeholders with brand palette
- **[low]** Dots indicator styling (8px dots, gap 8px, active scale 1.15) and progress bar geometry (1/3/5px heights, 12% min thumb) unconfigurable
- **[low]** No store-wide defaults: settings_schema() is empty by design, so a multi-slider store must configure every instance separately (no global 'default shape/colors for all category sliders')

**Notes:** Structural: (1) The module predates the token vocabulary — 'ported pixel-for-pixel from the Claude Design handoff' — and runs a parallel --cs-* design system; the single highest-value PR is chaining the five .cs custom-property defaults (css:13-17) to --shopos-ui-* with the current oklch values as fallbacks (byte-identical rendering when tokens absent, per the Wave 4.2 pattern already used for --cs-arrow-*). (2) --cs-accent (css:17) and its Elementor 'accent' control (Widget.php:293-303) are dead: no CSS rule consumes --cs-accent. (3) Module.php enqueue_front_style() head-enqueues the stylesheet on EVERY front-end page whenever Elementor is loaded (deliberate FOUC fix, but site-wide CSS weight without the widget). (4) Deprecated handle aliases shopos-category-slider (style+script) scheduled for removal in 2.0.0. (5) show_progress is a legacy back-compat control superseded by indicator. (6) JS inline styles are functional only (progress-bar width/transform from scroll position) — no branded styling injected from JS; drag/momentum/autoplay constants live in JS as unfilterable literals. (7) Settings Hub unused (settings_schema empty) — everything is per-Elementor-instance, which is documented intent.

## CheapestDefaultVariation

**Behavior controls today:**
- respect_manual_defaults (checkbox, default 1) — manually chosen product-editor defaults win over the auto-pick
- pdp_only (checkbox, default 1) — suppress auto-selection on shop/archive/loop; PDP and admin/AJAX/REST still get the pick
- strategy (select: cheapest | first_in_stock, default cheapest) — which eligible variation gets pre-selected
- per-product meta _shopos_cheapest_variation_strategy — overrides the strategy setting per product (no UI; raw post meta)
- filters for devs: shopos_core/cheapest_variation/should_apply, .../chosen, .../strategy

**Design controls today:**
- (none)

**Hardcoded styling:** none found.

**Gaps (owner cannot change):**
- **[low]** Per-product strategy override exists only as raw post meta (_shopos_cheapest_variation_strategy) — no product-editor field, so a non-dev owner cannot set it
- **[low]** Per-product opt-out from auto-selection is filter-only (should_apply); no product-editor checkbox or category-level exclusion
- **[low]** Eligibility rules are baked in (must be in_stock + purchasable + have display_price); e.g. an owner cannot choose to include backorderable variations

**Notes:** Pure PHP behavior module — no storefront markup, CSS, or JS, so no design-token surface at all; it uses the standard Module_Base settings_schema (3 fields) via Settings Hub, no legacy screen. Importer is a detect-only no-op (nothing to migrate). HOOKS.md documents "Planned hooks (NOT YET SHIPPED)" — documented but unwired, flagged as do-not-rely-on. Minor edge: the strategy select value is sanitized only via sanitize_text_field at save (Settings_Hub select is not re-whitelisted); an out-of-enum saved value silently falls through to the cheapest path in dispatch_strategy (only filter-injected invalid values get the Logger warning). Files: /Users/freemansmain/Ai Projects/ShopOS/shopos-core/src/Modules/CheapestDefaultVariation/{Module.php,Importer.php,README.md,HOOKS.md,CHANGELOG.md}.

## HoverSwap (id: hover_swap, "Card Image Effects")

**Behavior controls today:**
- shopos_core_hover_swap_card_image_mode — select none/hover_swap/gallery_slider; picks the card-image behaviour (default none, module ships dark)
- shopos_core_hover_swap_slider_arrows — checkbox; show hover-reveal prev/next arrows in gallery-slider mode (default on)
- Module enable/disable toggle via ShopOS → Modules (shopos_core_modules)
- Developer filter shopos_core/hover_swap/show — per-product suppression of the hover overlay (code-only, not a settings-screen control)

**Design controls today:**
- slider_arrows checkbox is the only design-adjacent lever (arrow chrome on/off)
- NONE of the module's CSS reads any --shopos-ui-* token — zero token consumption; no color/radius/motion/shadow lever reaches this module from the Design panel
- Respects prefers-reduced-motion and hover-capability media queries (accessibility defaults, not owner controls)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/HoverSwap/assets/css/hover-swap.css:28` | transition: opacity 0.35s ease | var(--shopos-ui-motion-base) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:17` | border-radius: 1rem | var(--shopos-ui-card-radius) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:55` | inline-size: 26px (arrow button, with block-size: 26px on line 56) | NEW TOKEN --shopos-ui-card-arrow-size (button-height-sm 36px is the nearest existing but wrong scale) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:59` | border-radius: 50% | var(--shopos-ui-radius-round) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:60` | background: rgba(255, 255, 255, 0.9) | var(--shopos-ui-color-paper) at 90% (e.g. color-mix(in srgb, var(--shopos-ui-color-paper) 90%, transparent)) — a store with non-white surfaces gets a white puck today |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:61` | color: #111 | var(--shopos-ui-color-ink) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:64` | transition: opacity 0.2s ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:65` | box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18) | var(--shopos-ui-shadow-sm) |
| `shopos-core/src/Modules/HoverSwap/assets/css/card-slider.css:70` | inset-inline-start: 6px (and inset-inline-end: 6px on line 74) | var(--shopos-ui-space-sm) (8px) or NEW TOKEN --shopos-ui-card-arrow-inset if 6px is deliberate |
| `shopos-core/src/Modules/HoverSwap/Gallery_Slider.php:165` | arrow SVG geometry baked into PHP: width/height 11, stroke-width 1.4 (same on line 168) | acceptable as icon internals, but size should track the NEW arrow-size token if one is added (currently CSS 26px puck + 11px glyph are two unlinked magic numbers) |

**Gaps (owner cannot change):**
- **[high]** Gallery-slider image corner radius is a fixed 1rem that ignores --shopos-ui-card-radius — a store that sets Corner radius in the Design panel gets mismatched card images vs. the rest of the card system
- **[medium]** Hover-swap cross-fade duration/easing (0.35s ease) is not adjustable and not motion-token linked — visible on every product card when the mode is on
- **[medium]** Arrow button look (white puck, #111 icon, 26px size, shadow, 6px inset) is fully baked — no rebrand path for a dark-surface or gold-accent store
- **[low]** Hover-swap always uses the FIRST gallery image; no way to pick which image swaps in (other than reordering the gallery)
- **[low]** Image size hardcoded to 'woocommerce_thumbnail' in both modes (Frontend.php:119, Gallery_Slider.php:130) — no setting/filter to choose a larger/retina size
- **[low]** Slider infinite-loop behaviour (edge cloning) is always on — no owner toggle for a hard-stop slider
- **[low]** No dots/pagination or slide-counter option for the gallery slider — arrows-or-nothing
- **[low]** JS tuning constants DRAG_THRESHOLD=6 and SETTLE_MS=120 (card-slider.js:29-30) are unfilterable — edge-case only

**Notes:** Structurally clean: proper Module_Base schema via Settings_Hub, no legacy admin screen, no dead code, HOOKS.md documents the public API accurately. No JS-injected styling (card-slider.js only manipulates scrollLeft). The headline issue is token blindness: neither stylesheet references a single --shopos-ui-* var, so the Design panel and theme tokens have zero effect here — every finding above is a straight var(--shopos-ui-x, <current value>) retrofit with the current values kept as fallbacks. Doubled-selector specificity hack (.a.a) is deliberate (out-specifies Elementor kit) and should be preserved in any retrofit. Arrow SVG markup lives in PHP (Gallery_Slider.php arrows_html), so icon geometry changes need a PHP edit, not CSS.

## PageTransitions

**Behavior controls today:**
- module enable/disable via shopos_core_modules (off by default; sole kill-switch for both layers)
- loading_label — text under the spinner; blank falls back to locale-aware default (He 'טוען תוצאות…' / En 'Loading…')

**Design controls today:**
- overlay z-index reads var(--shopos-ui-z-max, 100000)
- overlay card background reads var(--shopos-ui-color-paper, #fff)
- overlay card text color reads var(--shopos-ui-color-ink, #1c2230)
- spinner active arc color reads var(--shopos-ui-color-ink, #1c2230)
- prefers-reduced-motion honoured (fade off, spinner static) — matches token-sheet motion policy

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:23` | animation-duration: 0.18s (cross-document fade) | var(--shopos-ui-motion-fast) — exact match at 180ms |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:24` | animation-timing-function: ease | var(--shopos-ui-ease) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:44` | background: rgba(15, 18, 26, 0.35) (scrim; bespoke navy-ink, not palette ink #1b1b1b) | NEW TOKEN --shopos-ui-overlay-scrim (QuickView/ShopFilters drawers likely need the same) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:45` | backdrop-filter: blur(1.5px) | NEW TOKEN --shopos-ui-overlay-blur (or drop; brand-feel decision) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:48` | transition: opacity 0.15s ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:59` | gap: 12px | var(--shopos-ui-space-md) (16px, nearest step) — no 12px token exists |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:62` | border-radius: 1rem (16px) | var(--shopos-ui-radius-lg) (12px, nearest) — also the only radius in the suite ignoring the owner's radius-md Design-panel knob |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:63` | padding: 22px 34px | var(--shopos-ui-space-lg) var(--shopos-ui-space-xl) (24px 40px, nearest steps) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:64` | box-shadow: 0 8px 30px rgba(15, 18, 26, 0.18) | var(--shopos-ui-shadow-md) (0 8px 24px rgba(0,0,0,0.08)) or shadow-lg if the heavier look is intended |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:71` | border: 3px solid rgba(15, 18, 26, 0.14) (spinner track) | var(--shopos-ui-color-hairline) for the track color (width 3px can stay structural) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:73` | animation: shopos-pt-spin 0.7s linear infinite (spinner speed) | NEW TOKEN --shopos-ui-motion-spin — no existing motion token covers a continuous rotation period |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:77` | font-size: 13.5px | var(--shopos-ui-text-sm) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:78` | font-weight: 600 | var(--shopos-ui-weight-semi) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:79` | line-height: 1.4 | var(--shopos-ui-leading-snug) (1.35) |
| `shopos-core/src/Modules/PageTransitions/assets/css/page-transitions.css:94` | border-top-color: rgba(15, 18, 26, 0.14) (reduced-motion static ring) | var(--shopos-ui-color-hairline) |

**Gaps (owner cannot change):**
- **[medium]** Layers A (overlay) and B (cross-fade) cannot be toggled independently — the module switch is all-or-nothing; an owner wanting only the fade, or only the overlay, has no control
- **[high]** Cross-fade duration/easing is fixed at 0.18s ease with no token or setting — this animation runs on every same-origin navigation in Chrome/Edge/Safari, the most visible motion in the suite
- **[medium]** Scrim color/opacity and backdrop blur are baked; a store with a dark theme or strong brand color cannot restyle the dim layer that covers the whole page on every filter/search/pagination click
- **[low]** Spinner size (30px), stroke (3px) and rotation speed (0.7s) are fixed; no way to swap in a brand spinner
- **[low]** 8s overlay safety timeout hardcoded in JS (page-transitions.js:71)
- **[low]** Trigger selectors (pagination/search-form list, JS lines 25-38) are hardcoded — third-party pagination markup outside the list gets no overlay; no filter/setting to extend
- **[low]** Locale fallback for the label only special-cases Hebrew vs English (Module.php:164); other locales get English unless the owner fills the setting
- **[low]** Back/forward traversals always skip the fade (pageswap/pagereveal handlers) — deliberate InfiniteScroll fix, but not overridable

**Notes:** Clean Module_Base citizen: settings_schema/get_option/asset_min_url, no legacy admin screen, no dead code. JS builds the overlay DOM but injects zero inline styles — all styling is class-based in the CSS file, so tokenizing the CSS covers everything. Two minor inconsistencies: the z-max var() fallback is 100000 while the token default is 99999 (css:40), and the ink var() fallbacks use #1c2230 while the token default is #1b1b1b (css:61,72) — fallbacks are contract-covered but drift from the real token values. The bespoke rgba(15,18,26,…) navy underlies scrim, shadow, and spinner track; a scrim/overlay token would fix all three at once and likely serve QuickView/ShopFilters drawers too. print_expect_link() emits a render-blocking <link rel=expect> on every frontend page while the module is on — behavioral, not styleable, worth remembering for perf audits.

## QuickView (shopos-core/src/Modules/QuickView)

**Behavior controls today:**
- module enable toggle (shopos_quick_view module switch — full kill switch: no markup, assets, or AJAX endpoint when off)
- label_trigger — trigger button accessible name (default 'Quick view')
- label_drawer_title — drawer title (default 'Quick view')
- label_close — close button label (default 'Close')
- label_details — full-details link text (default 'View full details')
- label_loading — loading message (default 'Loading…')
- label_error — AJAX error message (default 'Could not load this product. Please try again.')

**Design controls today:**
- drawer panel bg/text: var(--shopos-ui-color-paper, #fff) / var(--shopos-ui-color-ink, #111) — Design panel Surface/Text fields flow through (quick-view.css:103-104)
- head divider: var(--shopos-ui-color-hairline) — Design panel Borders field (quick-view.css:138)
- drawer image radius: var(--shopos-ui-radius-md, 10px) — Design panel Corner radius (quick-view.css:213)
- typography scale via tokens: --shopos-ui-text-md/lg/sm/xs and --shopos-ui-weight-semi with px fallbacks (quick-view.css:144-145, 223, 230, 236, 243, 259)
- muted/soft text colors: var(--shopos-ui-color-ink-muted), var(--shopos-ui-color-ink-soft) (quick-view.css:206, 237, 244)
- close-button hover bg: var(--shopos-ui-color-paper-soft, #f5f5f5) (quick-view.css:167)
- stacking: var(--shopos-ui-z-max, 100000) (quick-view.css:74)
- prefers-reduced-motion honored — all transitions zeroed (quick-view.css:122-128)
- template override: <theme>/shopos/quick_view/drawer-content.php via Module_Base::load_template()
- drawer gallery reuses HoverSwap card-slider CSS/JS verbatim, so whatever levers that component has apply here too
- code-level (not settings): filters shopos_core/quick_view/show_trigger and shopos_core/quick_view/drawer_html; dequeue handle shopos-core-quick-view

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:34` | top: 12px (trigger offset from card corner) | NEW TOKEN --shopos-ui-qv-trigger-offset (12px is not a spacing step; sm=8, md=16) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:35` | right: 12px | NEW TOKEN --shopos-ui-qv-trigger-offset (same value as top) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:41` | width: 36px (trigger button size) | --shopos-ui-button-height-sm (36px) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:42` | height: 36px | --shopos-ui-button-height-sm |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:46` | border-radius: 999px | --shopos-ui-radius-pill |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:47` | background: #fff (trigger circle) | var(--shopos-ui-color-paper, #fff) — or NEW TOKEN --shopos-ui-qv-trigger-bg if the solid-white-over-image look must survive dark paper |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:48` | color: #000 (trigger glyph) | var(--shopos-ui-color-ink, #000) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:50` | box-shadow: 0 2px 6px rgba(0,0,0,0.18) | --shopos-ui-shadow-sm (or NEW TOKEN if the heavier 0.18 alpha is intentional over imagery) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:51` | transition: transform .18s cubic-bezier(.2,.7,.2,1) | var(--shopos-ui-motion-fast, 180ms) var(--shopos-ui-ease-out, ...) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:56` | background: #fff (hover/focus) | var(--shopos-ui-color-paper, #fff) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:57` | color: #000 (hover/focus) | var(--shopos-ui-color-ink, #000) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:87` | background: rgba(0,0,0,0.45) (overlay scrim) | NEW TOKEN --shopos-ui-overlay-scrim (no scrim token exists; ShopFilters/other drawers likely repeat this) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:89` | transition: opacity .25s ease | var(--shopos-ui-motion-base, 280ms) var(--shopos-ui-ease, ...) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:100` | width: min(480px, 94vw) (drawer width) | NEW TOKEN --shopos-ui-drawer-width (shareable with any other slide-in drawer) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:105` | box-shadow: 0 0 32px rgba(0,0,0,0.18) (panel shadow) | --shopos-ui-shadow-lg (or NEW TOKEN --shopos-ui-drawer-shadow for the omnidirectional spread) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:108` | transition: transform .3s cubic-bezier(.2,.7,.2,1), opacity .3s ease | var(--shopos-ui-motion-base, 280ms) var(--shopos-ui-ease-out, ...) (300ms ~ base step) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:136` | gap: 12px (drawer head) | --shopos-ui-space-sm or -md (12px is off-scale) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:137` | padding: 16px 20px (drawer head) | var(--shopos-ui-space-md) for 16px; 20px is off-scale — NEW TOKEN --shopos-ui-drawer-pad or snap to space-md/lg |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:154` | width: 32px (close button) | NEW TOKEN or snap to --shopos-ui-button-height-sm (36px) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:155` | height: 32px (close button) | same as width above |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:159` | border-radius: 999px (close button) | --shopos-ui-radius-pill |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:175` | padding: 20px (drawer body) | same drawer-pad decision as line 137 (off-scale 20px) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:204` | margin: 24px 0 (loading/error message) | --shopos-ui-space-lg (24px) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:217` | margin-block-start: 16px (summary) | --shopos-ui-space-md |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:222` | margin: 0 0 8px (product title) | --shopos-ui-space-sm |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:224` | line-height: 1.25 (product title) | --shopos-ui-leading-tight (1.18) — or keep 1.25 as fallback: var(--shopos-ui-leading-tight, 1.25) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:229` | margin: 0 0 12px (price) | --shopos-ui-space-sm or -md (12px off-scale) |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:235` | margin: 0 0 16px (short description) | --shopos-ui-space-md |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:242` | margin-block-start: 16px (product meta) | --shopos-ui-space-md |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:249` | margin-block-end: 4px (meta rows) | --shopos-ui-space-xs |
| `shopos-core/src/Modules/QuickView/assets/css/quick-view.css:253` | margin: 20px 0 0 (details link block) | --shopos-ui-space-lg or the drawer-pad token (20px off-scale) |

**Gaps (owner cannot change):**
- **[high]** Trigger appearance is fully baked: white circle, black magnifier SVG (hardcoded in Frontend::trigger_html), 36px size, fixed shadow and hover pop scale(1.12) — no color/icon/size setting; on a dark-branded store the white circle cannot be rebranded without custom CSS
- **[high]** Trigger position is pinned to the physical top-right corner (top/right 12px) by deliberate decision; no setting to choose corner, offset, or show-on-hover behavior — visible on every product card site-wide
- **[medium]** No setting to suppress the trigger per category/product-type/stock-state — only the shopos_core/quick_view/show_trigger PHP filter (code required)
- **[medium]** Drawer width min(480px, 94vw) is fixed — no narrow/wide option
- **[medium]** Overlay scrim rgba(0,0,0,0.45) darkness is fixed
- **[medium]** Drawer content composition is fixed to the WC single-product summary stack + full-details link; no toggles for meta/short-description/gallery-vs-single-image short of a theme template override
- **[low]** Slide-in edge is always inline-end (left on this RTL store) — no setting to flip
- **[low]** All motion (180ms trigger pop, 250ms overlay fade, 300ms panel slide, easings) is hardcoded — token-mapping would at least let theme-level motion overrides apply
- **[low]** Drawer image size fixed to 'woocommerce_single' in the template; gallery slider engages at >=2 images with no way to force single-image mode
- **[low]** AJAX rate limit fixed at 30 req/60s per IP (Ajax.php:57)

**Notes:** Structurally clean: proper Module_Base subclass, settings via label_fields()/Settings_Hub, no legacy admin screen, no JS-injected styles (quick-view.js only toggles classes; all styling is in the CSS file plus hardcoded SVG icon markup in Frontend.php trigger_html/drawer_shell_html and templates/drawer-content.php arrows). Two doc/code drifts: (1) HOOKS.md (lines 3-12) still documents feature flag shopos_core_quick_view_frontend_enabled default false, but Module.php says the flag graduated in 1.23.0 and the module toggle is now the kill switch — HOOKS.md is stale; Frontend.php's docblock ('Only constructed when the frontend feature flag is on') and Ajax.php's are stale the same way. (2) Frontend::enqueue() loads quick-view CSS/JS plus the HoverSwap card-slider CSS/JS on every non-admin frontend page, even pages with no product cards — the trigger_rendered gate only suppresses the footer shell, not the assets (same class of issue as the recent RestockNotify asset fix, but here it is a weight concern, not a 404). Cross-module coupling: drawer gallery hard-paths into src/Modules/HoverSwap/assets/ and shares handle shopos-core-card-slider — QuickView breaks visually if HoverSwap assets move. Trigger CSS intentionally uses doubled-class specificity to beat Elementor kit button styles and physical `right` (owner decision) — both documented in comments, fine as-is.

## RestockNotify (shopos-core/src/Modules/RestockNotify)

**Behavior controls today:**
- shopos_restock_auto_inject — auto-show form on out-of-stock product pages (off = shortcode-only)
- shopos_restock_form_heading — form title text
- shopos_restock_form_description — form body text
- shopos_restock_form_button_text — subscribe button label
- shopos_restock_form_success_message — post-subscribe message
- shopos_restock_form_duplicate_message — already-subscribed message
- shopos_restock_enable_confirmation — send signup confirmation email
- shopos_restock_enable_gdpr — show consent checkbox
- shopos_restock_gdpr_text — consent checkbox label
- shopos_restock_from_name / from_email — email sender identity (falls back to site name / admin email)
- shopos_restock_confirm_subject / confirm_heading / confirm_body — confirmation email copy (placeholder vars supported)
- shopos_restock_notify_subject / notify_heading / notify_body / notify_button_text — back-in-stock email copy
- Elementor widget 'shopos_restock_notify': single product_id NUMBER control (blank = auto-detect current product); no other controls
- [restock_notify product_id="N"] shortcode for manual placement
- Admin actions (not persisted settings): manual 'notify now' per product/variation, delete / bulk-delete subscribers, filtered CSV export

**Design controls today:**
- NONE — the module consumes zero --shopos-ui-* tokens anywhere (frontend.css, inline critical CSS, email HTML, admin.css all use raw hex/px values); the Design panel has no effect on this module
- Partial accidental inheritance only: assets/css/frontend.css uses font-family:inherit on inputs/buttons (frontend.css:65,92) so the theme body font leaks in — but the inline critical CSS block hardcodes an -apple-system stack on the card (Frontend.php:471), overriding it whenever a form renders
- enable_gdpr toggles presence of the consent row (layout presence, not styling)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:17` | background: #fff (form card) | --shopos-ui-card-bg |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:18` | border: 1px solid #e5e5e5 | --shopos-ui-color-hairline |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:19` | border-radius: 12px (card) | --shopos-ui-radius-lg (or --shopos-ui-card-radius) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:20` | padding: 32px 28px (card) | --shopos-ui-space-xl / --shopos-ui-space-lg |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:21` | max-width: 480px (form width) | NEW TOKEN --shopos-ui-restock-form-max (no existing token fits) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:23` | transition: border-color 0.2s ease | --shopos-ui-motion-fast + --shopos-ui-ease |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:27` | hover border-color: #ccc | --shopos-ui-card-border-hover (hairline) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:35` | background: #000 / color: #fff (bell icon circle) | --shopos-ui-color-accent / --shopos-ui-color-accent-text |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:36` | border-radius: 50% | --shopos-ui-radius-round |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:43-46` | heading font-size:18px; font-weight:600; color:#111; line-height:1.4 | --shopos-ui-text-lg / --shopos-ui-weight-semi / --shopos-ui-color-ink / --shopos-ui-leading-snug |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:51-53` | description font-size:14px; color:#666; line-height:1.6 | --shopos-ui-text-sm / --shopos-ui-color-ink-muted / --shopos-ui-leading-base |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:56-57` | field gaps 12px / 10px | --shopos-ui-space-sm |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:63-69` | input padding:11px 14px; font-size:14px; color:#111; background:#fafafa; border:1px solid #e0e0e0; border-radius:8px | --shopos-ui-input-padding-x / --shopos-ui-text-sm / --shopos-ui-color-ink / --shopos-ui-color-paper-soft / --shopos-ui-input-border / --shopos-ui-input-radius |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:76` | placeholder color: #aaa | --shopos-ui-color-ink-muted |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:77` | focus border-color:#111; box-shadow: 0 0 0 3px rgba(0,0,0,.06) | --shopos-ui-color-accent / --shopos-ui-input-focus-ring |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:85-87` | GDPR label font-size:12.5px; color:#666; checkbox accent-color:#000 | --shopos-ui-text-xs / --shopos-ui-color-ink-muted / --shopos-ui-color-accent |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:89-94` | button padding:12px 20px; font-size:14px; font-weight:600; color:#fff; background:#111; border-radius:8px; transition 0.2s | --shopos-ui-button-padding-x / --shopos-ui-text-sm / --shopos-ui-button-font-weight / --shopos-ui-color-accent-text / --shopos-ui-color-accent / --shopos-ui-button-radius / --shopos-ui-motion-fast |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:96` | button hover background: #333 | NEW TOKEN --shopos-ui-color-accent-hover (no hover-accent token exists) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:101` | spinner animation rsnSpin 0.8s linear infinite | NEW TOKEN --shopos-ui-motion-spin (or 2x --shopos-ui-motion-slow) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:104-106` | success icon 52px circle; background:#f0f0f0; color:#111 | --shopos-ui-color-paper-dim / --shopos-ui-color-ink / --shopos-ui-radius-round |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:108` | success text color: #444 | --shopos-ui-color-ink-soft |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:111-114` | error color:#c00; background:#fff5f5; border:1px solid #fdd; border-radius:8px; font-size:13px | --shopos-ui-color-danger / --shopos-ui-color-danger-soft / --shopos-ui-radius-md / --shopos-ui-text-sm |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:11,103,110,116` | rsnFadeIn 0.4s / 0.3s ease-out entrance animations | --shopos-ui-motion-base / --shopos-ui-ease-out (also gives free prefers-reduced-motion support since motion tokens zero out) |
| `shopos-core/src/Modules/RestockNotify/assets/css/frontend.css:119` | @media (max-width: 480px) breakpoint | NEW TOKEN (media queries can't read var(); needs SCSS/PHP constant if it should be tunable) |
| `shopos-core/src/Modules/RestockNotify/Frontend.php:469-495` | entire inline 'critical CSS' block re-declares every value above as raw hex/px (bg #fff, border #e5e5e5, radius 12px/8px, #111 button, #c00 error, 0.4s/0.8s animations) | var(--shopos-ui-*, <current value>) fallback chains so the Design panel wins while offline-fallback behavior is preserved |
| `shopos-core/src/Modules/RestockNotify/Frontend.php:471` | font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,... on the form card (overrides theme font whenever a form renders) | var(--shopos-ui-font-body, <stack>) |
| `shopos-core/src/Modules/RestockNotify/Frontend.php:315` | JS-injected fallback cssText 'display:block !important; max-width:480px; margin:24px auto' | --shopos-ui-space-lg + same NEW TOKEN as form max-width |
| `shopos-core/src/Modules/RestockNotify/assets/js/frontend.js:94-96,165,168,177,199` | jQuery animation durations slideUp(250/150/200), slideDown(300), fadeIn(300/200) | values read from --shopos-ui-motion-base/-fast (e.g. via getComputedStyle or a localized settings value); currently hardcoded ms in JS |
| `shopos-core/src/Modules/RestockNotify/Email.php:241` | email CTA button background:#000; color:#fff; padding:14px 36px; border-radius:6px; font-size:15px; font-weight:600 | server-side resolved accent (emails can't use CSS vars — resolve the Design-panel accent option in PHP; NEW mechanism) |
| `shopos-core/src/Modules/RestockNotify/Email.php:254-259` | email shell: body #f7f7f7; hardcoded -apple-system font stack; card max-width 560px, radius 12px, shadow rgba(0,0,0,.06); bell circle #000; h1 22px/700/#111 | PHP-side brand values (accent + ink + radius) resolved from Design options; no token plumbing exists for email today |
| `shopos-core/src/Modules/RestockNotify/Email.php:237,247,262-267` | img radius 8px; unsubscribe/footer #999 12px; body text #444 15px/1.6; border-top #eee | same PHP-side email-brand mechanism (ink-muted / hairline equivalents) |
| `shopos-core/src/Modules/RestockNotify/assets/css/admin.css:1-69` | entire admin UI hardcoded: #111 primary buttons, #e5e5e5/#ddd borders, radii 6-10px, badge colors #2e7d32/#f57f17/#e8f5e9/#fff8e1, 'SF Mono' code block | acceptable as wp-admin styling (tokens.css is not loaded in admin), but should share one admin palette if ShopOS admin theming ever lands — not storefront-blocking |

**Gaps (owner cannot change):**
- **[high]** Zero --shopos-ui-* token consumption: the storefront form ignores the Design panel entirely — accent color, corner radius, text/surface/border colors, and brand fonts set by the owner have no effect on the most conversion-sensitive OOS surface
- **[high]** Hebrew-first RTL is baked in: dir="rtl" hardcoded in the form markup (Frontend.php:501) and direction:rtl/text-align:right throughout frontend.css (:8-10,24-25,73-74) regardless of site locale — an LTR store gets a right-to-left form and admin screens are Hebrew-only
- **[medium]** Inline critical-CSS block is printed with every form and re-declares all styling, so theme/Customizer overrides need specificity fights; owner cannot reliably restyle even with custom CSS
- **[medium]** Email visual design (black CTA button, layout, colors) is fixed — owner controls copy only, so branded stores send off-brand emails
- **[medium]** Elementor widget has no Style tab: no per-instance color, typography, spacing, radius, or alignment controls (content tab = product_id only)
- **[medium]** Form input placeholders ('Full name'/'Email address') and all client-side validation/error messages come from locales/<locale>.php files, not settings — owner can edit the heading but not these strings
- **[medium]** Auto-inject placement is not choosable: four WC hooks + stock-html filter + footer-JS-relocation all fire with fixed priorities and a fixed selector ladder; owner can only toggle auto_inject on/off, not pick where the form lands
- **[medium]** Form max-width 480px fixed (CSS :21, footer fallback Frontend.php:315)
- **[low]** Email shell strings (greeting 'Hi %s,', customer-name fallback, unsubscribe link text/suffix) are locale-file-only, not options
- **[low]** Animations (0.4s fade-in, jQuery slide/fade, 0.8s spinner) have fixed durations/easings and do not respect prefers-reduced-motion (module CSS doesn't zero them like tokens.css does)
- **[low]** Bell + success + spinner icons are hardcoded inline SVGs — not swappable
- **[low]** Mobile breakpoint 480px, subscribe rate-limit 5/hour, variation-OOS cache 15 min, notification-log cap 100, admin list 20/page — all fixed constants
- **[low]** Name field is always rendered and always optional — can't hide it or make it required

**Notes:** Structural: (1) Module bypasses Settings_Hub entirely — settings_schema() returns array() and all 18 shopos_restock_* options are managed by the legacy top-level 'Restock Notify' admin menu (legacy/includes/class-shopos-restock-admin.php) with its own nonce/save handling; legacy_settings_url() wires the dashboard button there. No color/design field exists anywhere. Legacy save also runs sanitize_text_field on form_description even though it renders as a textarea (strips newlines). (2) LIKELY LIVE BUG, verify before the styling PR: assets/js/frontend.js:81 posts action 'shopos_restock_subscribe' but the only handlers registered are wp_ajax(_nopriv)_rsn_subscribe (legacy/includes/class-shopos-restock-ajax.php:7-8); rename commit 702f792 renamed the JS action but not the PHP hook names, so admin-ajax should 400 and every subscribe attempt surfaces as 'network error'. (3) Dead code: legacy/includes/class-shopos-restock-{email,frontend,stock-monitor}.php are never require'd — Module::boot() class_aliases the modern PSR-4 classes onto those names; the files are reference-only and duplicate all the hardcoded styling (don't patch them). (4) JS-injected styling exists: footer_inject prints an inline relocation <script> and sets cssText (Frontend.php:281-317); honeypot input uses an inline style attribute (Frontend.php:523, legitimately). (5) The inline critical-CSS block (Frontend.php:467-496) is printed once per page inside body — any tokenization PR must update BOTH frontend.css and this block (and ignore the identical dead copy in legacy frontend). (6) locales/{en_US,he_IL}.php hold both option defaults and non-option shell/js strings; OPTION_KEYS in Module.php gates which get seeded.

## VariationSwatches (shopos-core/src/Modules/VariationSwatches/)

**Behavior controls today:**
- shop_enabled — master toggle for the shop/archive compact picker
- shop_max_visible — max swatches per attribute before +N (clamped 1–50)
- shop_show_price — show the picker's price line on archive cards
- shop_apply_shop — render picker on /shop
- shop_apply_category — render picker on category archives
- shop_apply_tag — render picker on tag archives
- shop_apply_search — render picker on product search results
- shop_apply_related — render picker in PDP related/upsell loops
- shop_excluded_categories — comma-separated category IDs where the picker never renders
- pdp_hide_oos — hide out-of-stock variations on single-product pages
- shop_hide_oos — hide out-of-stock swatches in the archive picker
- shop_no_preselect — render archive picker with nothing pre-selected
- shop_hide_attr_labels — hide the 'Size:' label row on archive cards
- shop_hide_selected — hide the 'selected option' text row on archive cards
- shop_names_price_only — suppress the whole card buy UI, name+price only
- Per-term: swatch color hex (wp-color-picker on every pa_* term edit screen)
- Per-term: swatch image attachment (flag image_swatches)
- Per-term: hover tooltip override text (flag tooltip)
- Feature flags (Feature_Flags, variation_swatches group): auto_color, image_swatches, tooltip, card_image_swap, bundle_compat
- [shopos_buy_box id=""] shortcode placement

**Design controls today:**
- --shopos-ui-font-body consumed (with inherit fallback) by .shopos-buy-box, .shopos-pdp-price, .shopos-shop-pick, both toast stacks — typography follows the Style Kits kit-slot mapping
- --shopos-ui-z-modal consumed by the sticky mobile bar (shopos-swatches.css:865)
- --shopos-ui-z-toast consumed by both toast stacks (shopos-swatches.css:1121, shopos-shop-swatches.css:661)
- Per-term swatch color / image / tooltip term-meta (the only owner-facing visual data inputs)
- Auto-color sampling of variation images (flag auto_color) with filter shopos_core/variation_swatches/auto_color_disagreement_fallback
- Presentation toggles: shop_show_price, shop_hide_attr_labels, shop_hide_selected, shop_max_visible, shop_names_price_only
- All four templates theme-overridable via wc_get_template (woocommerce/shopos-variation-swatches/*.php)
- prefers-reduced-motion honored; hover effects gated behind @media (hover: hover)
- Filters: shopos_core/variation_swatches/card_image_selector, card_image_payload, term_image_url, shopos_vs_checkout_url (dev-level, no UI)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:186` | --shopos-text: #111111 (local var block, lines 186-204, feeds the whole buy box) | var(--shopos-ui-color-ink) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:187` | --shopos-muted: #6b6b6b | var(--shopos-ui-color-ink-muted) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:188` | --shopos-border: #d9d9d9 | var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:189` | --shopos-border-strong: #111111 | var(--shopos-ui-color-ink) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:190` | --shopos-bg: #ffffff | var(--shopos-ui-color-paper) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:193` | --shopos-bg-soft: #e0e0e0 (Add-to-cart pill bg) | var(--shopos-ui-color-paper-dim) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:194` | --shopos-bg-soft-hover: #d1d1d1 | NEW TOKEN --shopos-ui-color-paper-dim-hover (or color-mix from paper-dim) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:195` | --shopos-bg-soft-active: #c7c7c7 | NEW TOKEN --shopos-ui-color-paper-dim-active |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:197` | --shopos-primary: #ca2b1d (Buy Now brand red — the storefront's primary CTA color) | var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:198` | --shopos-primary-hover: #a8241a | NEW TOKEN --shopos-ui-color-accent-hover |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:199` | --shopos-primary-active: #8f1e15 | NEW TOKEN --shopos-ui-color-accent-active |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:200` | --shopos-success: #0f8a4a | var(--shopos-ui-color-success) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:201` | --shopos-radius: 999px | var(--shopos-ui-radius-pill) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:202` | --shopos-radius-sm: 24px | var(--shopos-ui-radius-xl) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:203` | --shopos-gap: 10px | var(--shopos-ui-space-sm) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:204` | --shopos-transition: 160ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:48` | font-size: 1.5rem (PDP price line — hierarchy anchor) | var(--shopos-ui-text-xl) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:216` | max-width: 640px (buy-box width) | NEW TOKEN --shopos-ui-buybox-max (no existing layout token fits) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:354` | background: #e5e7eb (colorless swatch-dot placeholder) | var(--shopos-ui-color-paper-dim) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:362` | border-color: #b5b5b5 (light-swatch ring) | var(--shopos-ui-color-hairline) or NEW TOKEN --shopos-ui-swatch-ring |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:435` | tooltip: rgba(20,20,20,0.92) bg, #fff text, 12px, radius 4px | var(--shopos-ui-color-ink)/var(--shopos-ui-color-paper), var(--shopos-ui-text-xs), var(--shopos-ui-radius-sm) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:492` | border-radius: 999px (repeated literally at 621, 675, 766, 923 alongside the var) | var(--shopos-ui-radius-pill) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:518` | color: #ffffff (selected size-pill text; also 759, 927 on CTA buttons) | var(--shopos-ui-color-accent-text) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:580` | color: #b91c1c (out-of-stock message red — different red than the CTA) | var(--shopos-ui-color-danger) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:619` | height: 54px (Add-to-cart), 58px Buy Now at 754, 52px sticky at 920 | var(--shopos-ui-button-height) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:866` | sticky bar: background #ffffff, border-top rgba(0,0,0,0.06), shadow 0 -8px 24px rgba(0,0,0,0.08) | var(--shopos-ui-color-paper), var(--shopos-ui-color-hairline), var(--shopos-ui-shadow-md) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:871` | transition: transform 220ms ease, opacity 220ms ease (sticky bar reveal) | var(--shopos-ui-motion-base) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:924` | background: #ca2b1d (sticky-bar buy button — raw hex, doesn't even use the local --shopos-primary var; hover #a8241a at 942) | var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:987` | ripple: animation 620ms cubic-bezier(0.22,1,0.36,1), bg rgba(0,0,0,0.12) | var(--shopos-ui-motion-slow) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:1016` | spinner: 720ms linear, border rgba(0,0,0,0.15) | NEW TOKEN --shopos-ui-motion-spin (no existing motion token fits an infinite spin) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:1044` | checkmark morph: 360ms cubic-bezier(0.22,1,0.36,1) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:1125` | toast: bg #111111, color #fff, radius 14px, shadow 0 12px 40px rgba(0,0,0,0.25), transition 320ms cubic-bezier(0.22,1,0.36,1) (lines 1125-1135) | var(--shopos-ui-color-ink)/paper, var(--shopos-ui-radius-lg), var(--shopos-ui-shadow-lg), var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:1216` | focus ring: outline 2px solid #111 + box-shadow rgba(17,17,17,0.12) | var(--shopos-ui-input-focus-ring) / var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-swatches.css:808` | @media (max-width: 480px) and (max-width: 768px) at 858 — mobile breakpoints | NEW TOKEN (documented breakpoint constants; CSS vars can't be used in media queries but the values should be canonicalized) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:20` | --eshop-* local var block lines 20-31: text #111111, muted #6b6b6b, border #e0e0e0, border-strong #111111, bg #ffffff, bg-soft #f2f2f2, accent #111111, success #0f8a4a, danger #ca2b1d, radius 999px, gap 8px, transition 140ms ease | var(--shopos-ui-color-ink/ink-muted/hairline/paper/paper-dim/accent/success/danger), var(--shopos-ui-radius-pill), var(--shopos-ui-space-sm), var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:100` | font-size: 18px (archive picker price) | var(--shopos-ui-text-lg) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:283` | dot placeholder #e5e7eb; light-swatch ring #b5b5b5 at 290 | var(--shopos-ui-color-paper-dim) / var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:358` | tooltip: rgba(20,20,20,0.92), #fff, 11px, radius 4px | var(--shopos-ui-color-ink)/paper, var(--shopos-ui-text-xs), var(--shopos-ui-radius-sm) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:410` | selected pill color #fff (also add button #fff at 499, hover #000 at 514) | var(--shopos-ui-color-accent-text) / var(--shopos-ui-palette-black) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:478` | +N hover background #e6e6e6 | var(--shopos-ui-color-paper-dim) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:679` | shop toast: bg #111111, radius 14px at 681, shadow 0 12px 40px rgba(0,0,0,0.25) at 685, transition 320ms cubic-bezier at 691 | var(--shopos-ui-color-ink), var(--shopos-ui-radius-lg), var(--shopos-ui-shadow-lg), var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:712` | var(--shopos-success, #0f8a4a) — toast stack mounts on document.body where --shopos-success (scoped to .shopos-buy-box) never resolves, so #0f8a4a always wins | var(--shopos-ui-color-success) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:736` | var(--eshop-danger, #d14c4c) — same out-of-scope var; fallback #d14c4c always wins AND mismatches the module's danger red #ca2b1d | var(--shopos-ui-color-danger) |
| `shopos-core/src/Modules/VariationSwatches/assets/css/shopos-shop-swatches.css:834` | focus ring: outline #111 + box-shadow rgba(17,17,17,0.12) | var(--shopos-ui-input-focus-ring) / var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/VariationSwatches/assets/js/shopos-swatches.js:808` | jQuery animate scroll-to-form duration 220 (ms) hardcoded in JS | NEW TOKEN (read --shopos-ui-motion-base at runtime or a localized constant) |
| `shopos-core/src/Modules/VariationSwatches/assets/js/shopos-shop-swatches.js:612` | toast TTLs 4500ms (error) / 2600ms (info/success) hardcoded in JS; dismiss animation 340ms at 695 | NEW TOKEN --shopos-ui-toast-ttl-* or module setting (340ms should track the CSS transition token) |

**Gaps (owner cannot change):**
- **[high]** The entire buy-box + archive-picker palette lives in two module-local CSS var blocks (--shopos-*, --eshop-*) with raw hex — the Design panel's ink/paper/hairline/accent/radius settings have zero effect on the PDP buy box, archive picker, sticky bar, or toasts
- **[high]** Primary CTA red #ca2b1d (Buy Now, sticky mobile bar, error states) is baked in with no setting, token, or filter — a store whose brand isn't red cannot change its single most prominent storefront button
- **[high]** Pill shape language (999px radius) is hardcoded across every control; the Design panel's corner-radius setting (radius-md) never reaches this module, so a square/sharp brand can't be expressed
- **[medium]** All buy-box customer-facing copy (Add to cart, Buy now, Out of stock, Starting from:, Choose an option, Added to cart, …) is auto He/En via Labels.php with deliberately no admin override and no gettext — owner cannot reword any of it
- **[medium]** Sticky mobile bottom bar is always on for buyable products on mobile — no settings toggle to disable it
- **[medium]** Buy Now button is always rendered on PDPs — no setting to hide it or point it somewhere other than checkout (filter shopos_vs_checkout_url exists but no UI)
- **[low]** Add-to-cart micro-animations (ripple 620ms, spinner 720ms, success morph 360ms, toast slide 320ms) have fixed durations/easings that ignore the --shopos-ui-motion-* tokens; only prefers-reduced-motion tempers them
- **[low]** Toast position (bottom-left fixed), TTLs (4500/2600ms), and button-state revert timings (1500/2500ms) are unconfigurable
- **[low]** Archive variable-product picker always adds quantity 1 (qty stepper exists only for simple products on cards)
- **[low]** Swatch geometry is fixed (PDP 44/34px circles, archive 28/22px, mobile overrides) — no size setting for stores wanting bigger/square swatches
- **[low]** Mobile breakpoints 480px/768px hardcoded in CSS and duplicated in JS (matchMedia max-width:768px in shopos-swatches.js:720)
- **[low]** Buy-box max-width 640px fixed
- **[low]** Auto-color disagreement fallback grey #CCCCCC is filter-only (no UI); sampler batch size filter-only

**Notes:** Structural: the module is a thin Module_Base bootstrap over a verbatim legacy plugin port (legacy/includes + legacy/templates, global ShopOS_VS_* classes, class_exists conflict guard). Settings correctly use the Module_Base/Settings_Hub schema (15 keys) with Settings_Reader shimming new shopos_core_variation_swatches_* keys over never-deleted legacy shopos_vs_* keys; the old WooCommerce settings tab was fully removed in 1.23.2 and legacy_settings_url() now points back at the hub page. Both stylesheets predate the --shopos-ui-* system and define parallel private token blocks — only font-body, z-modal and z-toast tokens were retrofitted; everything else uses !important-heavy theme-defeating CSS by design. Scope bug worth fixing during tokenization: the body-mounted shop toast stack references var(--shopos-success) and var(--eshop-danger) which are scoped to .shopos-buy-box/.shopos-shop-pick, so their fallbacks always win and the error-toast red (#d14c4c) silently mismatches the module's danger red (#ca2b1d); the sticky-bar buy button likewise repeats raw #ca2b1d/#a8241a instead of the local var. JS injects styling: ripple <span> geometry inline, a 220ms jQuery scroll animation, toast timing constants, and window.ShopOSCoreVSFlags/ShopOSVS/ShopOSShopVS inline payloads. Labels.php intentionally bypasses gettext (deterministic He/En by locale prefix) — documented as owner-requested, but it forecloses per-store copy. Admin-only inline styles in class-admin.php (term-list swatch circles ~lines 241/353/364, border #d0d0d0) — not storefront, excluded from findings. Five feature flags (auto_color, image_swatches, tooltip, card_image_swap, bundle_compat) gate whole feature branches and are folded into the payload transient key. Importer is detect-only (option keys shared). No Elementor widgets registered by this module. No dead code found; class-ajax.php is small but live (Buy Now redirect + FunnelKit suppression).

## shopos-core/src/Modules/InfiniteScroll

**Behavior controls today:**
- skeleton_count — number of skeleton placeholder cards shown while loading (default 6)
- max_pages — hard cap on auto-loaded pages (default 50)
- end_message — text shown when there are no more products
- trigger_mode — select, but only 'auto' is offered (button/hybrid withdrawn per remediation B-3)
- history_mode — pushState / replaceState / disabled URL behavior on page advance (default disabled)
- hybrid_threshold — pages to auto-load before switching to Load-more button (dead unless a legacy button/hybrid value is saved)
- container_selector — CSS selector override for the product grid container (empty = built-in 11-entry fallback list)

**Design controls today:**
- shimmer_base_color setting → emitted as --shopos-ui-is-shimmer-base (Settings_Hub color field, default #eceff3)
- shimmer_highlight_color setting → --shopos-ui-is-shimmer-highlight (default #f6f8fb)
- shimmer_duration_ms setting → --shopos-ui-is-shimmer-duration (clamped 0–60000ms, default 1400)
- fade_duration_ms setting → --shopos-ui-is-fade-duration (default 550ms)
- fade_transform_px setting → --shopos-ui-is-fade-transform translateY (clamped 0–200px, default 18)
- skeleton block radius consumes var(--shopos-ui-radius-md, 6px) (css line 31)
- error-retry button radius consumes var(--shopos-ui-radius-sm, 4px) (css line 92)
- all --shopos-ui-is-* consumers in CSS carry matching var() fallbacks (lines 25–30, 57, 62) — covered

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:37` | aspect-ratio: 3 / 4 (skeleton image fallback) | var(--shopos-ui-card-aspect, 3 / 4) — card aspect token exists (default 4/5); skeleton fallback silently disagrees with the card primitive |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:57` | cubic-bezier(.2,.7,.3,1) fade-in easing | var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:71` | color: #6b7280 (end/error message text — Tailwind gray, not in the ShopOS palette at all) | var(--shopos-ui-color-ink-muted) |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:72` | font-size: 14px (end/error message) | var(--shopos-ui-text-sm) |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:73` | letter-spacing: .02em (end/error message) | var(--shopos-ui-tracking-wide) (0.04em) or --shopos-ui-tracking-normal — pick one, don't invent a third value |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:70` | padding: 28px 16px (end/error message block) | var(--shopos-ui-space-lg) var(--shopos-ui-space-md) (24px/16px) — 28px is an off-scale step |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:79-83` | end-message divider: width 48px, height 2px, margin 0 auto 14px, opacity .35 | spacing → --shopos-ui-space-* steps; the 48px/2px/opacity are a brand flourish with no token — NEW TOKEN --shopos-ui-is-divider-* only if per-store variation is wanted, otherwise map margins to space tokens |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:91` | padding: 4px 12px (error retry button) | var(--shopos-ui-space-xs) var(--shopos-ui-space-md)-ish; better: consume --shopos-ui-button-radius/--shopos-ui-button-padding-x primitives |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:18` | padding: 0 12px (skeleton card fallback padding) | var(--shopos-ui-space-sm) or space-md — off-scale 12px (JS-measured padding overrides it when a real card exists, so low stakes) |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:38` | margin-bottom: 14px (skeleton image gap; also 14px at lines 48, 81) | var(--shopos-ui-space-md) or space-sm — 14px appears 3x and is off-scale |
| `shopos-core/src/Modules/InfiniteScroll/assets/css/infinite-scroll.css:97` | opacity: .75 hover state on retry button | acceptable as-is or use motion token for the transition it lacks; minor |
| `shopos-core/src/Modules/InfiniteScroll/assets/js/infinite-scroll.js:110` | fadeStaggerMs: 40 — per-card animation-delay stagger applied inline at js:670 | NEW TOKEN/setting alongside fade_duration_ms (e.g. fade_stagger_ms setting → --shopos-ui-is-fade-stagger), or at least route through the localized payload |

**Gaps (owner cannot change):**
- **[medium]** End/error message typography and color are fully hardcoded (gray #6b7280, 14px) — a store with dark surfaces or a warm ink gets an alien gray notice at the bottom of every archive
- **[medium]** Error-state strings are not settings: errorMessage ('Could not load more.'), loadMoreLabel ('Load more'), announceTemplate are hardcoded __() strings in localized_payload() (Module.php:220-223) — end_message is editable but its siblings are not (no label_fields usage)
- **[medium]** No 'Load more' button trigger mode — button/hybrid were withdrawn from the select, so auto-scroll is the only shipped behavior; hybrid_threshold setting is dead weight in the UI
- **[low]** Fade-in stagger (40ms/card) is not configurable while fade duration and distance are — half the appear-animation is tunable, half isn't
- **[low]** Prefetch distance (rootMargin '800px 0px') and scroll fallback threshold (900px, js:113) are filter/code-only — no setting for how eager loading feels
- **[low]** Fade-in easing is baked into CSS — owner can change duration and distance via settings but never the curve
- **[medium]** Touch tap-to-navigate IIFE (js:901-940) is always-on with no setting to disable and hardcoded card/exclude selector lists — if it misfires on a store's custom card markup the owner has no off switch
- **[low]** Skeleton card internal geometry (line widths 65%/35%, heights 12/14px, image aspect fallback 3/4) is fixed — skeletons approximate a generic card, not the store's card
- **[low]** Shared /page/N/ deep-link healing (location.replace to page 1, js:288-294) is unconditional behavior with no opt-out

**Notes:** Module uses Module_Base + settings_schema correctly and pioneers the right pattern for the rest: settings → inline_token_css() emits a module-namespaced --shopos-ui-is-* token block consumed by the stylesheet with fallbacks. Oddities: (1) trigger_mode select offers only 'auto' but the JS still honors a stale saved 'button'/'hybrid' value, and hybrid_threshold remains in the schema for a mode that can't be selected — dead UI. (2) The sentinel's stylesheet rule (css:8-13, height 1px) is dead — JS overwrites it with inline cssText height:20px at js:493. (3) triggerModesEnabled is hardcoded true in the payload (graduated flag) with an unreachable legacy branch kept in applyHistoryMode. (4) An unrelated always-on 'touch tap-to-navigate' IIFE ships inside infinite-scroll.js. (5) Deprecated style/script handle aliases 'shopos-infinite-scroll' scheduled for removal in 2.0.0. (6) Skeleton markup is inline in makeSkeletonCard() with no filter (documented in HOOKS.md). (7) Importer is a no-op by design. (8) A11y live-region and sentinel inline styles in JS are plumbing, not brand — excluded from hardcoded_styling.

## shopos-core/src/Modules/MyAccount

**Behavior controls today:**
- Module enable/disable toggle (Module_Base::is_enabled via Settings Hub) — the only setting; settings_schema() returns an empty array
- CSS handle `shopos-core-my-account` is dequeueable via wp_dequeue_style (documented in HOOKS.md) to disable the restyle without disabling the module

**Design controls today:**
- Colors fully token-routed: consumes --shopos-ui-color-{paper,paper-soft,paper-dim,ink,ink-soft,ink-muted,hairline,accent,accent-text,danger} via local --fma-* aliases (my-account.css:16-24, 873)
- Radii via --shopos-ui-radius-{sm,md,lg,pill} (css:25-28) — so the Design panel's Corner radius field (radius-md) reaches this page's tables/notices
- Typography via --shopos-ui-font-{body,display,mono}, text scale xs-xxl, weights, leading, tracking (css:29-45) — Style Kits font slots apply
- Spacing via --shopos-ui-space-{xs,sm,md,lg,xl,xxl} and --shopos-ui-card-gap (css:46-52)
- Control sizing via --shopos-ui-input-height, --shopos-ui-button-height{,-sm} (css:53-55)
- Design panel color fields (ink, ink-soft, paper, paper-alt, hairline) and accent preset all cascade into this page through the token aliases
- prefers-reduced-motion honored (css:893-899); RTL handled via logical properties + mask flip

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:218` | transition: background 180ms ease, color 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:331` | transition: text-decoration-color 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:455` | transition: background 180ms ease, border-color 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:587` | transition: background 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:643` | transition: border-color 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:820` | transition: background 180ms ease, border-color 180ms ease, color 180ms ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:89` | max-width: 1180px | var(--shopos-ui-container-max) (1360px) or NEW TOKEN --shopos-ui-account-max if the narrower canvas is intentional |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:86` | grid-template-columns: 264px 1fr (sidebar width 264px) | NEW TOKEN --shopos-ui-account-sidebar-width |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:91` | padding-bottom 80px | var(--shopos-ui-space-3xl) or var(--shopos-ui-section-gap) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:108` | horizontal padding 14px (mobile page frame) | var(--shopos-ui-container-pad) / var(--shopos-ui-space-md) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:212` | padding: 10px 14px (sidebar nav links) | --shopos-ui-space tokens (sm/md) or NEW TOKEN --shopos-ui-nav-item-padding |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:253` | active accent bar: inset-inline-start -24px, width 2px, height 16px (css:253-257) | offset should track var(--shopos-ui-space-lg); bar size arguably plumbing but 2px/16px is a brand mark — NEW TOKEN candidates if reused elsewhere |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:191` | mask fade width calc(100% - 32px) (also 192, 195, 196) | var(--shopos-ui-space-xl) (40px) or leave as plumbing constant |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:328` | text-underline-offset: 3px; text-decoration-thickness: 1px (css:328-329) | acceptable plumbing, or NEW TOKEN --shopos-ui-link-underline-* if links get restyled suite-wide |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:381` | th padding: 14px var(--fma-space-md) — 14px vertical | var(--shopos-ui-space-md) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:417` | status pill padding: 3px 10px 3px var(--fma-space-sm); gap 6px (css:416) | --shopos-ui-badge-padding / --shopos-ui-badge-height primitives (badge tokens exist but are not consumed here) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:423` | status pill font-size via --fma-text-xs but not --shopos-ui-badge-font | var(--shopos-ui-badge-font) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:430` | status dot width/height 4px, border-radius: 50% (css:430-432) | border-radius should map var(--shopos-ui-radius-round); 4px dot is plumbing-adjacent |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:444` | ghost button padding 0 12px, gap 6px (css:442), margin-inline-end 4px (css:445) | --shopos-ui-space-sm/xs; height already tokenized via --shopos-ui-button-height-sm |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:471` | info callout padding: var(--fma-space-md) 20px — 20px horizontal | var(--shopos-ui-space-lg) or --shopos-ui-space-md |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:586` | edit pill padding: 5px 12px | --shopos-ui-space tokens or badge primitives |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:634` | input padding: 11px var(--fma-space-md) — 11px vertical | var(--shopos-ui-input-padding-x) for x already OK via space-md; vertical should derive from --shopos-ui-input-height instead of a magic 11px |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:653` | input focus = border-color: var(--fma-ink) with box-shadow: none (css:647-655) | var(--shopos-ui-input-focus-ring) — the focus-ring token exists and is bypassed, so the prefers-contrast 3px ring override in tokens.css never reaches these inputs |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:727` | mobile table cell padding: 12px var(--fma-space-md) | var(--shopos-ui-space-md) or sm |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:856` | notice padding: 14px 18px | --shopos-ui-space tokens (md-based) |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:808` | primary buttons use background var(--fma-ink) / border var(--fma-ink) / color var(--fma-bg) (css:808-810, hover 829-831) | var(--shopos-ui-color-accent) / var(--shopos-ui-color-accent-text) — the module defines --fma-accent/--fma-accent-tx (css:23-24) precisely for this and never uses them; accent body-class themes (is-accent-gold etc.) and the Design panel accent cannot restyle these CTAs |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:814` | button font-size text-sm, weight semi, tracking wide via --fma-* but not --shopos-ui-button-font-weight / --shopos-ui-button-tracking / --shopos-ui-button-padding-x / --shopos-ui-button-radius (css:807-816) | the button primitive tokens (--shopos-ui-button-*) — currently only button-height is consumed |
| `shopos-core/src/Modules/MyAccount/assets/css/my-account.css:99` | breakpoints 880px (css:99,155,174,220,260,294), 600px (css:106,670), 640px (css:680), minmax(280px,1fr) (css:500) | NEW TOKEN(s) — no --shopos-ui-breakpoint-* vocabulary exists; note 600 vs 640 are two different 'mobile' breakpoints inside one file |

**Gaps (owner cannot change):**
- **[medium]** Primary buttons ignore the accent system — Design-panel accent preset/override and is-accent-* body classes have zero effect on My Account CTAs (always ink-on-paper)
- **[low]** All transitions hardcode 180ms/ease, so the motion tokens (and any future motion re-theming) don't apply; only prefers-reduced-motion is honored via a local override
- **[medium]** No per-store control over the editorial styling choices: uppercase mono eyebrows/labels/buttons, serif display greeting, 64ch paragraph measure (css:317), pill-shaped status chips — all baked in with no setting or token
- **[medium]** Layout geometry fixed: 264px sidebar, 1180px canvas max-width, 880px sidebar-collapse breakpoint — an owner cannot widen the account page to match a wider storefront container (--shopos-ui-container-max is 1360px but this page caps at 1180px)
- **[low]** Input focus style bypasses --shopos-ui-input-focus-ring, so the accessibility-driven prefers-contrast focus upgrade in tokens.css doesn't reach account forms
- **[low]** Status pills are monochrome hairline chips regardless of order status (completed/cancelled/failed all look identical); semantic status color tokens exist but are unused
- **[low]** Module has no settings_schema at all — the only owner lever is on/off; no toggle for e.g. sticky sidebar, mobile pill-nav vs stacked nav, or table card-collapse behavior

**Notes:** Clean, minimal module: Module.php (enqueue-only, correctly gated on is_account_page, uses Module_Base + asset_min_url) plus one CSS file and docs (README.md, HOOKS.md, CHANGELOG.md). No JS, no templates, no legacy subtree, no Elementor widgets, no admin screens. Dead code: --fma-accent and --fma-accent-tx are defined at my-account.css:23-24 and never referenced — evidence buttons were meant to be accent-driven. Fallback drift in the local alias block: --fma-radius falls back to 8px vs token default 6px, --fma-ink to #0a0a0a vs #1b1b1b, --fma-text-* to px vs clamp() — harmless while the theme tokens load (fallbacks never fire on ShopOS themes) but misleading if the CSS is read as the source of truth. Two different mobile breakpoints (600px for form rows/page padding, 640px for table card-collapse) coexist in the same file. The badge/button/input primitive tokens exist in the contract but are only partially consumed here (heights yes; padding/radius/weight/tracking/focus-ring no) — mapping those plus the six 180ms transitions is the bulk of the PR work.

## shopos-core/src/Modules/ProductFeed

**Behavior controls today:**
- instant_update (checkbox, default 1) — rebuild feed within ~30s of any stock/price change
- hourly_fallback (checkbox, default 1) — hourly full-regeneration safety net
- Generate now button on the module page (admin-post action, nonce + manage_woocommerce)

**Design controls today:**
- None — module emits XML and wp-admin markup only; no storefront CSS/JS, so no --shopos-ui-* tokens are consumed and none are needed
- Dev-level (not owner UI): shopos_core/product_feed/query_args and /item filters can rescope or rewrite feed content; before_serve and after_generate actions for auth/CDN integration

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/ProductFeed/Module.php:306` | style="max-width:680px;" on admin status table | wp-admin screen — storefront tokens not loaded there; acceptable as-is, or move to the shared ShopOS admin stylesheet |
| `shopos-core/src/Modules/ProductFeed/Module.php:313` | color:#999 on 'Not generated yet' em | wp-admin screen — use WP admin muted class (e.g. description) instead of inline hex; no --shopos-ui-* token applies in admin |
| `shopos-core/src/Modules/ProductFeed/Module.php:334` | style="margin-top:16px;" on Generate-now form | wp-admin screen — shared ShopOS admin stylesheet; no token applies |

**Gaps (owner cannot change):**
- **[low]** Debounce window fixed at 30s and dedupe threshold at 60s (Module.php:221-225) — owner cannot tune rebuild latency vs server load
- **[low]** Hourly cadence fixed (wp_schedule_event 'hourly') — no interval choice for the fallback rebuild
- **[low]** Feed endpoint slug hardcoded to /product-feed (Server.php REWRITE_SLUG) — no owner-facing rename, and the raw uploads URL is also always public with no auth/token option
- **[low]** No settings-level product scoping (exclude category/tag/out-of-stock) — only the dev-only query_args/item filters; XML schema itself is fixed (no Google Merchant/Facebook format toggle)
- **[low]** HTTP cache lifetime hardcoded (Cache-Control: public, max-age=300, Server.php:125)
- **[low]** Batch size fixed at 100 (Generator::BATCH) — no tuning knob for very large catalogs

**Notes:** Fully Module_Base/Settings_Hub compliant (settings_schema, get_option, module_page render hook); no legacy admin screen. No frontend assets whatsoever — despite the audit brief mentioning assets/ and templates, none exist for this module, so token coverage is trivially complete. Doc drift in HOOKS.md: the 'Cron hooks' section names shopos_core_feed_hourly / shopos_core_feed_instant but the real hooks are shopos_core_product_feed_hourly / shopos_core_product_feed_async; it also lists a 'Planned hooks (NOT YET SHIPPED)' section (product_query_args, product_payload, should_include, generated, instant_queued) that is dead documentation. Deprecated BC shims (pre-1.9.0 cron/nonce/query-var names and 1.4.0 Module constants/proxies) are carried intentionally with a 2.0.0 removal target. All gaps rated low because nothing this module does is storefront-visible.

## shopos-core/src/Modules/ProductPage

**Behavior controls today:**
- coupon_code — WooCommerce coupon code advertised by the notice (notice auto-hides when coupon is deleted/expired)
- coupon_percent — discount percent used to compute the shown price (1-99)
- urgency_max — stock ceiling (1..N units) under which the urgency badge shows for a picked variation
- button_color — optional hex for the VariationSwatches buy-box CTAs + mobile sticky bar (empty = keep VS native red)
- label_coupon_intro — coupon notice wording before the code (default 'Enter coupon code')
- label_coupon_outro — coupon notice wording before the price (default 'and the product will cost you:')
- label_urgency_last_unit — badge text at qty 1 (default 'Last one in stock')
- label_urgency_units_left — badge text at qty 2..max, {count} placeholder (default 'Only {count} left in stock')
- label_trust_shipping — trust-line shipping text (empty default = item hidden)
- label_trust_returns — trust-line returns text (empty default = item hidden)
- module enable toggle — Settings Hub kill-switch for all three surfaces (flags graduated in 1.23.0)
- shortcodes [shopos_discounted_price]/[discounted_price] and [shopos_stock_urgency]/[stock_urgency] — manual placement on pre-takeover Elementor pages
- Elementor widgets 'ShopOS Coupon Price' / 'ShopOS Stock Urgency' — placement only, zero per-instance controls (info note points at global settings)
- theme template override at shopos/product_page/single-product.php — full takeover-template replacement
- code-level filters: show_coupon_notice, coupon_notice_html, show_stock_urgency, urgency_messages (per-product veto/markup)

**Design controls today:**
- button_color setting — the only true style setting; injects --shopos-primary/-hover/-active + sticky-CTA background as inline CSS (Template_Loader::button_color_css)
- All three stylesheets consume --shopos-ui-* tokens with the token's own value as literal fallback: colors (ink/paper/paper-dim/hairline/danger/success/warning{,-soft}), full type scale (text-xs..xxl), leading, tracking, weights, font-body/font-display, spacing (xs..xxl), radii (sm/md/lg/pill/round), input/button/badge primitives, shadow-md, motion-fast/base + ease/ease-out, z-card/z-sticky, container-max/pad, card-aspect — so the Design panel's 7 tokens and any theme-CSS token override flow through
- Design panel radius-md flows to notices (coupon notice uses radius-lg/sm tokens; buy-box inputs/buttons use input-radius/button-radius tokens)
- prefers-reduced-motion + Hebrew flat-tracking handling baked in (token-consistent)
- Typography deliberately inherits page font on both widgets (no font-family declared — owner request, old 'Ploni' stack removed)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:126` | border-radius: 1rem (gallery slide cards) | --shopos-ui-radius-lg (12px) — or NEW TOKEN --shopos-ui-radius-xl-soft if 16px is intentional; as a literal it escapes ALL token/panel control |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:231` | border-radius: 1rem (summary-column framed panel) | --shopos-ui-radius-lg — same literal as the gallery; the two biggest rounded surfaces on the PDP bypass the radius tokens |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:178` | block-size: 3px (gallery progress-bar thickness) | NEW TOKEN --shopos-ui-progress-size |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:191` | min-inline-size: 14% (progress-bar resting fill) | NEW TOKEN --shopos-ui-progress-rest (value duplicated as 0.14 in product-page.js:114) |
| `shopos-core/src/Modules/ProductPage/assets/js/product-page.js:114` | 0.14 resting-fill floor (JS twin of the CSS 14%) | single source with the CSS value — read from a data attribute or NEW TOKEN --shopos-ui-progress-rest |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:273` | font-size: 0.72em (sale from-price <del> scale) | NEW TOKEN --shopos-ui-price-del-scale (hierarchy-establishing size, no token fits) |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:284` | max-width: 65ch (short-description measure) | NEW TOKEN --shopos-ui-measure (75ch sibling at :587) |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:587` | max-width: 75ch (accordion body measure) | NEW TOKEN --shopos-ui-measure (inconsistent with the 65ch at :284) |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:429` | transform: translateY(-1px) (add-to-cart hover lift) | --shopos-ui-card-hover-shift (-2px) or NEW TOKEN --shopos-ui-button-hover-shift |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:65` | @media (min-width: 1024px) desktop layout breakpoint (repeated at :142, :870, :876) | NEW TOKEN — documented constant (media queries can't read var(); needs a single named breakpoint constant, also consumed by JS) |
| `shopos-core/src/Modules/ProductPage/assets/js/product-page.js:160` | matchMedia('(min-width: 1024px)') (JS twin of the CSS breakpoint) | same NEW TOKEN / shared breakpoint constant as product-page.css:65 |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:731` | @media (min-width: 768px) related-grid 2→4 column switch | NEW TOKEN — documented tablet breakpoint constant |
| `shopos-core/src/Modules/ProductPage/assets/css/product-page.css:872` | padding-block-end: calc(64px + env(safe-area-inset-bottom)) (sticky-bar space reservation) | derive from --shopos-ui-input-height + 2×--shopos-ui-space-sm (bar height is input-height 44px + 2×8px padding + border ≈ 61px; 64px is a magic twin) |
| `shopos-core/src/Modules/ProductPage/templates/single-product.php:138` | sizes attr '(max-width: 767px) 45vw, (max-width: 1360px) 23vw, 320px' (related/upsell srcset slots) | derive 1360 from --shopos-ui-container-max / the breakpoints from the shared constants — three magic numbers duplicating token values in PHP |
| `shopos-core/src/Modules/ProductPage/assets/css/coupon-notice.css:41` | column-gap: 0.4em (sentence/price gap in the notice) | --shopos-ui-space-xs (4px) or keep em-relative as NEW TOKEN — currently the one unroutable gap in an otherwise fully tokened file |
| `shopos-core/src/Modules/ProductPage/Template_Loader.php:269` | background: <hex> !important on .shopos-sticky-bar__buy — same hex reused for --shopos-primary-hover and --shopos-primary-active (line 268) | owner-controlled input (fine), but hover/active should derive a darkened variant or accept separate settings instead of flattening all three states to one hex |

**Gaps (owner cannot change):**
- **[high]** Corner-radius branding is unreachable on the PDP's two dominant rounded surfaces (gallery slides + summary panel) — the 1rem literals bypass both the radius tokens and the Design panel's radius-md control
- **[medium]** Gallery behavior is fully baked: lightbox force-removed (theme support stripped globally in add_gallery_supports, trigger hidden with !important at product-page.css:199, capture-phase click kill in JS), slider support removed, scroll-snap strip forced at every breakpoint, no thumbnail row option — an owner who wants a lightbox or thumbnails has no setting or filter
- **[medium]** Mobile sticky add-to-cart bar cannot be disabled (no setting/flag; renders on every product page unless VariationSwatches ships its own bar)
- **[medium]** Related/upsells grid pinned to 4 products / 4 columns at filter priority 9999 (template lines 145-152), deliberately overriding any site-level woocommerce_output_related_products_args — owner cannot choose 3-up or a different count
- **[medium]** Stock-urgency badge only supports variable products (badge_for_current returns '' for simple products) — low-stock simple products never show scarcity
- **[low]** Hook placements fixed: coupon notice at summary priority 31, urgency at 35, trust at 36, additional-info at 38 — no ordering control; additional-information is always relocated under the buy box as a collapsed <details> and removed from the accordion
- **[low]** First accordion section always renders open ($shopos_first in single-product.php:97); no all-collapsed option
- **[low]** Desktop layout column ratio fixed at 7fr/5fr and breakpoint at 1024px; no wide/narrow gallery option
- **[low]** uppercase text-transform baked on CTAs, sale badge, variation labels, accordion-adjacent titles — a brand that doesn't use uppercase can't turn it off without CSS
- **[low]** Trust line is exactly two items (shipping/returns) with fixed inline SVG icons — no third item, no icon choice
- **[low]** Gallery progress-bar resting fill (14%) and thickness (3px) not adjustable; breadcrumb always renders with fixed '/' delimiter
- **[low]** Urgency badge is always warning-amber and coupon price always danger-red (token-routed but no per-module color choice; semantic colors are excluded from the Design panel by design)

**Notes:** Module follows the Module_Base schema cleanly (settings_schema + Labels resolver; deliberately custom label loop instead of label_fields() — documented why in Labels.php). HOOKS.md is stale: it still lists the three feature flags as 'default off' gates, but Module.php graduated them in 1.23.0 (always-on; module toggle is the kill-switch) — and it omits the button_color setting and the two Elementor widgets. JS-injected styling exists but is behavior-only (fill.style.inlineSize scroll tracking) except the two duplicated constants cited above (0.14 fill floor, 1024px breakpoint). Template_Loader strips wc-product-gallery-lightbox/-slider theme supports GLOBALLY at after_setup_theme (acknowledged in its docblock) — a side effect on any non-PDP gallery render. Elementor widgets construct a throwaway `new Module()` at render (documented Search-widget precedent). Legacy shortcode aliases [discounted_price]/[stock_urgency] intentionally kept. button_color inline CSS relies on !important against VariationSwatches' hardcoded sticky-CTA red.

## shopos-core/src/Modules/ProductSlider

**Behavior controls today:**
- Elementor: eyebrow / headline / headline_mute (section header text)
- Elementor: limit — max products slider 1-48
- Elementor: orderby (date/price/popularity/rating/menu_order/title/rand) + order ASC/DESC
- Elementor: source (all/featured/on_sale/category/tag/manual/current_query/related)
- Elementor: categories / tags multi-select (conditional on source)
- Elementor: include_ids / exclude_ids (comma-separated product IDs)
- Elementor: hide_free switch (drop _price<=0 products)
- Elementor: hide_out_of_stock switch (force instock; WC global still applies when off)
- Elementor: display_mode — slider vs static grid
- Elementor: direction — auto/ltr/rtl
- Elementor: snap — free-scroll / per-card / per-page
- Elementor: mouse_drag on/off
- Elementor: show_arrows on/off
- Elementor: indicator — progress bar / dots / none (show_progress kept as legacy alias)
- Elementor: autoplay on/off + autoplay_delay 1000-15000ms + loop (wrap on autoplay end)
- Elementor: show_cart — add-to-cart button on/off (hook suppression)
- Elementor: show_sale_badge — WC sale flash on/off (hook suppression)
- PHP filters: shopos_core/product_slider/query_args, .../grid_max_pages, .../archive_thumbnail_size

**Design controls today:**
- Elementor: per_view / per_view_tablet / per_view_mobile (fractional peek) — feed --cs-per* CSS vars
- Elementor: gap slider 4-48px — feeds --cs-gap
- Elementor: card_height (image height) 180-480px — feeds --cs-card-h (desktop/tablet only; mobile overridden, see gaps)
- Elementor: shape — soft (1rem image radius) vs rect (6px)
- Elementor: accent color — sets --cs-accent (sale badge background)
- Elementor: ring_color — sets --cs-ring-color (hover ring)
- Elementor: typography group controls for eyebrow, headline, product name, price
- Elementor: name_color and price_color color pickers
- Inherited --cs-* vars (--cs-ink, --cs-bg, --cs-mute) used for button/price/ink colors — but these are hardcoded oklch values in CategorySlider CSS, NOT bridged to --shopos-ui-* (see gaps)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:143` | border: 1px solid rgba(0, 0, 0, 0.07) (resting card hairline ring) | var(--shopos-ui-color-hairline) / --shopos-ui-card-border |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:144` | border-radius: 14px (hover ring radius) | --shopos-ui-radius-lg (12px) or --shopos-ui-card-radius |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:146` | transition: inset .25s cubic-bezier(.2,.7,.2,1), border-color .25s | var(--shopos-ui-motion-base) + var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:183` | transition: transform .5s cubic-bezier(.2,.7,.2,1) (image hover zoom) | var(--shopos-ui-motion-slow) + var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:187` | border-radius: 1rem (soft image shape) | --shopos-ui-radius-lg or NEW TOKEN --shopos-ui-card-image-radius |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:191` | border-radius: 6px (rect image shape) | var(--shopos-ui-radius-md) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:199` | transform: scale(1.015) (image hover zoom amount) | NEW TOKEN --shopos-ui-card-hover-zoom |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:214` | top: 12px; left: 12px (sale badge corner offset, also :236 RTL right: 12px) | var(--shopos-ui-space-sm)-ish or NEW TOKEN --shopos-ui-badge-offset |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:219` | padding: 4px 10px (sale badge) | var(--shopos-ui-badge-padding) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:222` | font-size: 10px (sale badge) | var(--shopos-ui-badge-font) / --shopos-ui-text-xs |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:223` | font-weight: 600 (sale badge) | var(--shopos-ui-weight-semi) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:224` | letter-spacing: 0.1em; text-transform: uppercase (sale badge, :225) | var(--shopos-ui-tracking-wide) (0.04em; 0.1em is off-token — decide)  |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:230` | border-radius: 999px (sale badge pill) | var(--shopos-ui-badge-radius) / --shopos-ui-radius-pill |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:249` | margin: 14px 2px 0 (title spacing above meta) | var(--shopos-ui-space-md)-adjacent or NEW TOKEN --shopos-ui-card-meta-gap |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:251` | font-weight: 400 (product title) | var(--shopos-ui-weight-regular) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:252` | font-size: 16px (product title; Elementor typography control can override per-instance but the default is off-token) | var(--shopos-ui-text-md) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:253` | line-height: 1.25; letter-spacing: -0.01em (title, :254) | var(--shopos-ui-leading-tight) + var(--shopos-ui-tracking-tight) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:268` | font-size: 14px (price) | var(--shopos-ui-text-sm) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:273` | margin-right: 6px; opacity: 0.7 (struck-through del price, :275) | var(--shopos-ui-space-xs)-adjacent; opacity fine or NEW TOKEN |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:280` | font-weight: 500 (sale ins price) | var(--shopos-ui-weight-medium) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:288` | font-size: 12px (star rating) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:329` | min-height: 38px (add-to-cart button height) | var(--shopos-ui-button-height-sm) (36px) or --shopos-ui-button-height — 38px matches neither |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:331` | padding: 8px 16px (button) | var(--shopos-ui-button-padding-x) for x-axis |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:334` | font-size: 13px; font-weight: 500; letter-spacing: 0.02em (button, :335-336) | var(--shopos-ui-text-sm) + var(--shopos-ui-button-font-weight) + var(--shopos-ui-button-tracking) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:342` | border-radius: 999px (button pill — diverges from --shopos-ui-button-radius default 4px) | var(--shopos-ui-button-radius) (or deliberate pill via NEW TOKEN --shopos-ui-card-button-radius) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:347` | transition: background .18s, color .18s, border-color .18s | var(--shopos-ui-motion-fast) (exactly 180ms) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:366` | opacity: 0.7 (button .loading/.added state) | fine as-is or NEW TOKEN --shopos-ui-state-busy-opacity |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:421` | margin-top: 28px (pagination gap); gap: 6px (:426) | var(--shopos-ui-space-lg) / var(--shopos-ui-space-xs)-adjacent |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:440` | font-size: 15px (title at <=1024px) | var(--shopos-ui-text-md) fluid clamp would remove the need for the breakpoint override |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:453` | height: 220px (mobile image height — silently overrides the owner's card_height control on phones) | a mobile card_height Elementor control or NEW TOKEN --shopos-ui-card-image-h-mobile |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:456` | margin-top: 10px; font-size: 14px (mobile title, :457) | var(--shopos-ui-text-sm) / fluid type |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:461` | font-size: 12px; min-height: 34px; padding: 6px 12px (mobile button, :462-463) | var(--shopos-ui-text-xs) + button tokens |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:471` | bottom: 12px !important (WooSQ quick-view button repositioning) | var(--shopos-ui-space-sm)-adjacent (minor) |
| `shopos-core/src/Modules/ProductSlider/assets/css/product-slider.css:106` | @media (max-width: 1024px) / (max-width: 640px) breakpoints (also :111, :435, :444; mirrored in Widget.php card_sizes_attr :1001) | NEW TOKEN(S) --shopos-ui-bp-tablet / --shopos-ui-bp-mobile (or at least single-source constants) |
| `shopos-core/src/Modules/ProductSlider/Widget.php:1331` | arrow SVGs: width/height 14, stroke-width 1.4 (also :1334) | fine as-is (icon plumbing), or size via CSS if arrows ever need theming |

**Gaps (owner cannot change):**
- **[high]** The entire module bypasses the --shopos-ui-* token system: every color runs through --cs-ink/--cs-bg/--cs-mute/--cs-accent, which are defined as raw oklch literals in CategorySlider/assets/css/category-slider.css:13-17 with no --shopos-ui-* fallback chain. Design panel changes (Text, Surface, Borders, Accent, Corner radius) have zero effect on product slider cards, buttons, badges, or ring.
- **[high]** Mobile image height is hard-forced to 220px at <=640px, silently overriding the owner's card_height control on phones — no mobile height control exists.
- **[high]** Add-to-cart button styling (pill radius, ink fill, 38px height, hover invert) has no controls at all — no typography group, no color, no radius. Owner can restyle name/price/eyebrow/headline but not the most prominent CTA on every card.
- **[medium]** Sale badge look is fully baked (uppercase, 0.1em tracking, 10px, pill, accent bg) — only on/off and the shared accent color are controllable; no badge text/typography/position control.
- **[medium]** Title truncation fixed at 2 lines (-webkit-line-clamp: 2) with reserved 2-line min-height — no control for 1/3-line or no-clamp.
- **[medium]** Breakpoints 640px/1024px are hardcoded in CSS and duplicated in Widget.php card_sizes_attr(); they ignore Elementor's site-configured tablet/mobile breakpoints.
- **[low]** Hover behavior (image zoom 1.015, ring inset -8px→-10px animation, transition timings) is fixed; only the ring color is exposed.
- **[low]** Resting card hairline ring rgba(0,0,0,0.07) is invisible-to-the-owner: ring_color only affects hover state.
- **[low]** WC post-AJAX 'View cart' link is unconditionally display:none inside cards — no toggle.
- **[low]** Progress footer counter format (zero-padded '04 / 12') and pagination prev/next glyphs (&larr;/&rarr;, Widget.php:1404-1405) are fixed.
- **[medium]** No site-wide defaults: settings_schema() is empty, so every design/behavior choice must be re-made per widget instance; no Settings Hub page for cross-page consistency.

**Notes:** Module follows Module_Base correctly but settings_schema() returns array() — 100% of configuration is per-Elementor-instance. No JS of its own and no legacy/ subtree: it reuses the CategorySlider runtime (JS + base CSS), registering the shared 'shopos-core-category-slider' handles defensively so it works with CategorySlider disabled; consequently its color story inherits CategorySlider's hardcoded oklch --cs-* root palette (category-slider.css:13-17), which is the single biggest tokenization fix and lives outside this module's directory — fixing it there (map --cs-* to var(--shopos-ui-*, <oklch fallback>)) instantly retrofits both sliders. Deprecated style/script handle aliases ('shopos-product-slider', 'shopos-category-slider') are kept for the 1.9.x cycle, removal slated for 2.0.0. show_progress is a live legacy control superseded by 'indicator' via a back-compat shim in render(). Styles are head-enqueued on every front-end page when Elementor is loaded (enqueue_front_styles, anti-FOUC) — i.e. the CSS ships site-wide, not only where the widget is used. No inline/JS-injected styling from this module besides the intended --cs-* custom-property style attribute built in render() from owner controls.

## shopos-core/src/Modules/Search

**Behavior controls today:**
- module enable toggle (single kill switch; always-on internals since 1.21.0)
- min_chars — characters before the dropdown searches (default 2, also enforced server-side)
- debounce_ms — idle delay after last keystroke (default 200)
- max_results — dropdown row count, clamped 1–20 (default 8)
- show_image — thumbnail on each dropdown row (default yes)
- show_price — price on each dropdown row (default yes)
- show_sku — SKU on each dropdown row (default no)
- label_placeholder — search field placeholder text
- label_button — submit button text
- label_no_results — empty-state message
- label_see_all — see-all-results footer link text
- label_searching — searching message
- label_toggle — trigger accessible name
- label_close — close-button accessible name
- [shopos_search] shortcode atts: placeholder, button (per-placement override)
- Elementor 'ShopOS Search' widget: Placeholder + Button text controls (blank falls through to Labels)
- admin 'Reindex all products' batch tool on ShopOS → Search
- code-level filters: shopos_core/search/max_results (500 cap), shopos_core/rate_limit_defaults (search_query bucket 30/60)

**Design controls today:**
- search.css reads the token layer everywhere with fallbacks: colors --shopos-ui-color-{paper,paper-soft,paper-dim,hairline,ink,ink-muted,ink-soft,accent,accent-text}
- radii: --shopos-ui-radius-{md,lg,round} (panel, palette card, field, trigger/close circles)
- shadows: --shopos-ui-shadow-{md,xl}
- type: --shopos-ui-text-{xs,sm,md}, --shopos-ui-weight-{regular,medium,semi}, --shopos-ui-tracking-wide
- spacing: --shopos-ui-space-{sm,md,lg,xl}
- motion: --shopos-ui-motion-{fast,base} + --shopos-ui-ease-out; @media prefers-reduced-motion disables palette transitions
- z-stack: --shopos-ui-z-overlay (anchored panel), --shopos-ui-z-modal (palette)
- therefore all 7 Design-panel tokens (ink, ink-soft, paper, paper-alt→paper-soft, hairline, radius-md, accent) restyle the search UI with no code
- Elementor widget deliberately has no style controls — styling is token-driven (content controls only)

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/Search/assets/css/search.css:213` | background: rgba(17, 17, 17, 0.5) (modal scrim) | NEW TOKEN --shopos-ui-scrim (no overlay/scrim token exists; #111 is palette-black but alpha is baked in) |
| `shopos-core/src/Modules/Search/assets/css/search.css:256` | height: 3rem (palette search field) | var(--shopos-ui-input-height, 44px) — field currently ignores the input primitive (3rem = 48px vs token 44px) |
| `shopos-core/src/Modules/Search/assets/css/search.css:331` | height: 3rem (palette submit button) | var(--shopos-ui-button-height, 48px) — exact match, trivially tokenizable |
| `shopos-core/src/Modules/Search/assets/css/search.css:300` | width: 2.75rem / height: 2.75rem (close button, lines 300–301) | var(--shopos-ui-input-height, 44px) (2.75rem = 44px exactly) |
| `shopos-core/src/Modules/Search/assets/css/search.css:50` | width: 52px; height: 52px (result thumbnail, lines 50–51) | NEW TOKEN --shopos-ui-search-thumb-size (no size token fits) |
| `shopos-core/src/Modules/Search/assets/css/search.css:223` | max-width: 600px (palette card width) | NEW TOKEN --shopos-ui-modal-width (or accept as structural) |
| `shopos-core/src/Modules/Search/assets/css/search.css:198` | padding: 12vh … (palette top offset, desktop) | NEW TOKEN --shopos-ui-modal-top-offset |
| `shopos-core/src/Modules/Search/assets/css/search.css:393` | --shopos-top-offset: calc(var(--shopos-vvh) * 0.1) (palette top offset, mobile) | NEW TOKEN --shopos-ui-modal-top-offset (same lever as line 198) |
| `shopos-core/src/Modules/Search/assets/css/search.css:19` | max-height: 70vh (anchored dropdown height cap) | NEW TOKEN --shopos-ui-search-panel-max-height (or accept) |
| `shopos-core/src/Modules/Search/assets/css/search.css:224` | max-height: 76vh (palette card height cap) | same family as line 19 — NEW TOKEN or accept |
| `shopos-core/src/Modules/Search/assets/css/search.css:68` | line-height: 1.3 (result title) | var(--shopos-ui-leading-snug, 1.35) |
| `shopos-core/src/Modules/Search/assets/css/search.css:61` | gap: 3px (result body stack) | var(--shopos-ui-space-xxs, 2px) or var(--shopos-ui-space-xs, 4px) |
| `shopos-core/src/Modules/Search/assets/css/search.css:95` | margin-inline-end: 0.4em (del price gap) | var(--shopos-ui-space-xs, 4px) (or accept em-relative) |
| `shopos-core/src/Modules/Search/assets/css/search.css:108` | gap: 0.4em (see-all row icon gap) | var(--shopos-ui-space-xs, 4px) (or accept em-relative) |
| `shopos-core/src/Modules/Search/assets/css/search.css:176` | opacity: 0.65 (trigger hover) | NEW TOKEN --shopos-ui-hover-opacity (no interaction-opacity token exists) |
| `shopos-core/src/Modules/Search/assets/css/search.css:186` | transform: scale(0.92) (trigger press) | NEW TOKEN --shopos-ui-press-scale (or accept as micro-interaction) |
| `shopos-core/src/Modules/Search/assets/css/search.css:180` | outline: 2px solid currentColor; outline-offset: 3px (trigger focus, lines 180–181) | align with --shopos-ui-input-focus-ring convention, or NEW TOKEN --shopos-ui-focus-outline |
| `shopos-core/src/Modules/Search/assets/css/search.css:321` | outline: 2px solid …; outline-offset: 2px (close-button focus, lines 321–322; color IS tokened) | same focus-outline token as lines 180–181 |
| `shopos-core/src/Modules/Search/assets/css/search.css:372` | transform: translateY(-8px) scale(0.98) (palette entrance offset) | NEW TOKEN --shopos-ui-modal-enter-shift (durations/easing are already tokened; only the distances are baked) |
| `shopos-core/src/Modules/Search/assets/css/search.css:391` | @media (max-width: 768px) (mobile palette breakpoint) | NEW TOKEN (build-time constant — media queries can't read CSS vars; should at least match Elementor's mobile breakpoint convention) |
| `shopos-core/src/Modules/Search/Frontend.php:79` | inline SVG magnifier: width="22" height="22" stroke-width="2" | NEW TOKEN --shopos-ui-icon-size consumed via CSS (size the svg in search.css instead of attributes) |
| `shopos-core/src/Modules/Search/assets/js/search.js:30` | JS ICON svg width/height 22, stroke-width 2 (duplicate of Frontend.php icon) | same --shopos-ui-icon-size treatment; single source for the glyph |
| `shopos-core/src/Modules/Search/assets/js/search.js:33` | JS CLOSE_ICON svg width/height 22, stroke-width 2 | same --shopos-ui-icon-size treatment |

**Gaps (owner cannot change):**
- **[high]** Out-of-stock products are always excluded: in_stock_only=true is hardcoded at every read (Ajax.php:104, Results_Query.php:141/235/269/319/335). Ignores Woo's own hide-out-of-stock setting — a store selling backorder items silently loses them from dropdown AND results page, with no setting or filter.
- **[medium]** Modal scrim color/opacity (rgba(17,17,17,.5)) is unbrandable — the most visible non-token surface whenever the palette opens.
- **[medium]** Search trigger icon glyph and 22px size are baked into PHP + JS SVGs; owner cannot swap the magnifier icon or resize it (it sits in the header on every page).
- **[medium]** Dropdown attach selector 'input[type="search"], input[name="s"]' is hardcoded in Frontend::localized_payload() — every theme search field (including a blog-only search) gets the product dropdown; no setting/filter to scope or opt fields out.
- **[medium]** Presentation is auto-decided: shortcode/widget → command palette, any other field → anchored dropdown. Owner cannot choose 'plain visible search bar with dropdown' from the widget — the icon-trigger palette is forced.
- **[low]** Palette geometry fixed: 600px width, 12vh desktop / 10% mobile top offset, 76vh card cap, 70vh anchored-panel cap.
- **[low]** 768px mobile breakpoint fixed; may disagree with a store's Elementor custom breakpoints.
- **[low]** Relevance weights baked (title 4×, exact-SKU 1000, prefix 50, infix 500/25, is_sku_like heuristic) — no filter to tune ranking per store.
- **[low]** Dropdown thumbnail is pinned to the 'woocommerce_thumbnail' image size (Ajax.php:119-120) and a 52px box; no control.
- **[low]** Trigger hover opacity (.65) and press scale (.92) micro-interactions fixed.
- **[low]** Rate limit (30 req/60s) and 500-id result cap only tunable via code filters, no settings UI.
- **[low]** Indexer cadence fixed: 5-min sweep, 10s debounce, batch 50 — not tunable (fine for current stores).

**Notes:** Structurally clean: proper Module_Base settings_schema + label_fields, no legacy settings screen, no dead code found. Oddities: (1) Admin_Page.php:111-116 renders inline-styled progress UI (background:#ddd, #2271b1 WP-blue bar, border-radius:3px, transition 0.3s) plus inline JS — wp-admin chrome, not storefront, so excluded from findings. (2) search.js injects inline styles for the visually-hidden aria-live region (lines 51-55) and anchored-panel fixed positioning (lines 292-295) — plumbing/a11y, not brand. (3) The magnifier SVG is duplicated verbatim in Frontend.php:79 and search.js:30 and must stay in sync manually. (4) Ajax::LIMIT const 8 duplicates the max_results schema default. (5) search.css/js are enqueued on every front-end page whether or not a search field exists (deliberate, per Widget docblock). (6) Frontend field styles rely on !important resets to beat Elementor/theme input styling — token overrides still work since the values themselves are var() chains.

## shopos-core/src/Modules/ShopFilters

**Behavior controls today:**
- label_toggle — mobile filter button text
- label_panel_title — drawer title
- label_panel_aria — drawer accessible name
- label_close — close button label
- label_categories — categories heading
- label_price — price heading
- label_sort — sort heading
- label_flags_heading — availability heading
- label_onsale — on-sale filter label
- label_in_stock — in-stock filter label
- label_categories_aria — categories accessible name
- label_clear_all — clear-all button text
- label_apply — mobile apply button text
- label_clear — mobile clear button text
- label_count_singular — result-count singular template (%d)
- label_count_plural — result-count plural template (%d)
- price_bands — comma-separated upper price points; blank = auto-derive ~4 bands from catalogue
- default_sort — default catalog ordering (whitelisted Woo orderby values)
- filter_style — classic checkbox lists vs refined pills/collapsible panel
- facet matrix (shopos_core_shop_filters_facet_config, own admin_post form): per-taxonomy enabled on/off, display order, hide-on-categories multiselect
- Reindex all products — admin batch rebuild tool (action, not a setting)
- Elementor widget 'ShopOS Shop Filters' — placement only, zero per-instance controls (info note points to global settings)

**Design controls today:**
- filter_style setting — the one real visual lever (classic vs refined treatment)
- refined style consumes --shopos-ui-color-{hairline,paper,paper-soft,paper-dim,ink,ink-muted,accent-soft} with fallbacks — Design-panel color fields flow through
- both styles consume --shopos-ui-radius-pill, --shopos-ui-radius-md (radius-md is Design-panel editable)
- mobile toggle/apply consume --shopos-ui-button-font-weight, --shopos-ui-button-tracking
- drawer overlay/panel consume --shopos-ui-z-max
- toggle/apply/clear/sort carry theme classes .shopos-ui-btn{--ghost,--block,--link} and .shopos-ui-select, inheriting theme button/select styling
- swatch chip color/image come from per-term meta (shopos_swatch_color / VariationSwatches image) — owner-editable per attribute term
- price band labels formatted by wc_price — follow WooCommerce currency settings
- panel wording fully owner-editable via the 16 label fields

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:59` | accent-color: #111 (classic checkbox brand fill) | var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:585` | mobile Apply button: border/background #111, color #fff (also line 583/586) | var(--shopos-ui-color-accent) + var(--shopos-ui-color-accent-text) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:206` | selected-swatch ring: 0 0 0 2px #fff, 0 0 0 4px #111 | var(--shopos-ui-color-paper) + var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:217` | focus ring #2271b1 (WP-admin blue on the storefront) + #fff | var(--shopos-ui-input-focus-ring) or var(--shopos-ui-color-accent) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:52` | color #767676 (term/cat counts) | var(--shopos-ui-color-ink-muted) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:119` | color #767676 (result count line) | var(--shopos-ui-color-ink-muted) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:200` | background #f6f6f6 (text swatch chip) | var(--shopos-ui-color-paper-dim) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:509` | background #fff (mobile drawer panel) | var(--shopos-ui-color-paper) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:573` | background #fff (sticky actions bar) | var(--shopos-ui-color-paper) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:76` | border 1px solid rgba(0,0,0,0.12) (chip/clear) | 1px solid var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:88` | chip hover border rgba(0,0,0,0.3) + background rgba(0,0,0,0.03) | var(--shopos-ui-color-ink-soft) + var(--shopos-ui-color-paper-dim) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:138` | border 1px solid rgba(0,0,0,0.15) (sort select) | var(--shopos-ui-input-border) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:188` | border 1px solid rgba(0,0,0,0.15) (swatch chip) | 1px solid var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:480` | border 1px solid rgba(0,0,0,0.15) (mobile toggle) | 1px solid var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:598` | border 1px solid rgba(0,0,0,0.15) (mobile clear) | 1px solid var(--shopos-ui-color-hairline) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:496` | overlay scrim rgba(0,0,0,0.45) | NEW TOKEN --shopos-ui-overlay-scrim |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:512` | box-shadow 0 -4px 24px rgba(0,0,0,0.2) (drawer sheet) | var(--shopos-ui-shadow-lg) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:511` | border-radius 12px 12px 0 0 (drawer sheet) | var(--shopos-ui-radius-lg) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:198` | border-radius 999px (text swatch chip) | var(--shopos-ui-radius-pill) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:34` | font-weight 600 (facet/categories titles) | var(--shopos-ui-weight-semi) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:131` | font-weight 600 (sort label) | var(--shopos-ui-weight-semi) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:274` | font-weight 600 (refined selected pill) | var(--shopos-ui-weight-semi) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:551` | font-weight 600 + font-size 1.1rem (drawer title, line 552) | var(--shopos-ui-weight-semi) + var(--shopos-ui-text-lg) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:11` | font-size 0.95rem (panel base size) | var(--shopos-ui-text-sm) or var(--shopos-ui-text-md) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:53` | font-size 0.85em (term counts) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:80` | font-size 0.85em (chips) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:201` | font-size 0.85em (text swatch) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:301` | font-size 0.85em (refined show-more button) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:383` | font-size 0.85em (refined chip × badge) | var(--shopos-ui-text-xs) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:247` | font-size 0.88em (refined pill) | var(--shopos-ui-text-sm) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:394` | font-size 1.25rem (refined close button) | var(--shopos-ui-text-lg) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:477` | font-size 1rem (mobile toggle) | var(--shopos-ui-text-md) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:563` | font-size 1.5rem (mobile close button) | var(--shopos-ui-text-xl) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:185` | swatch chip 28px × 28px (lines 185-186, min-width 196) | NEW TOKEN --shopos-ui-swatch-size |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:63` | animation shopos-sf-pop 0.22s ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:104` | animation shopos-sf-chip-in 0.32s cubic-bezier(0.32,0.72,0,1) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:84` | transition 0.15s ease (chip hover; same value at 191, 249, 395) | var(--shopos-ui-motion-instant) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:207` | animation shopos-sf-pop 0.2s ease (swatch tick) | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:332` | chevron transition transform 0.2s ease | var(--shopos-ui-motion-fast) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:485` | press transition transform 0.08s ease (also 588, 603) | var(--shopos-ui-motion-instant) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:499` | overlay transition opacity 0.25s ease | var(--shopos-ui-motion-base) var(--shopos-ui-ease) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:514` | drawer transition transform 0.3s cubic-bezier(0.32,0.72,0,1) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:526` | content cascade animation 0.3s cubic-bezier(0.32,0.72,0,1) | var(--shopos-ui-motion-base) var(--shopos-ui-ease-out) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:529` | stagger delays 0.04s–0.32s hardcoded per nth-child (lines 529-536) | NEW TOKEN --shopos-ui-motion-stagger (or multiples of --shopos-ui-motion-instant) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:413` | @media (min-width: 769px) desktop breakpoint | NEW TOKEN --shopos-ui-breakpoint-mobile (build-time constant — CSS media queries can't read var()) |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:424` | @media (max-width: 768px) mobile breakpoint (also line 471) | NEW TOKEN --shopos-ui-breakpoint-mobile |
| `shopos-core/src/Modules/ShopFilters/assets/css/shop-filters.css:354` | refined category-list max-height 16em | NEW TOKEN --shopos-ui-facet-list-max-height (or a setting) |
| `shopos-core/src/Modules/ShopFilters/assets/js/shop-filters.js:20` | MOBILE_QUERY '(max-width: 768px)' — JS copy of the CSS breakpoint | NEW TOKEN --shopos-ui-breakpoint-mobile (single shared source; localized or read from computed style) |
| `shopos-core/src/Modules/ShopFilters/assets/js/shop-filters.js:124` | setTimeout(go, 350) drawer-close fallback, hard-coupled to the CSS 0.3s slide | derive from --shopos-ui-motion-base (getComputedStyle) so a token change can't desync |

**Gaps (owner cannot change):**
- **[high]** The classic (default) panel style is almost entirely un-tokenized — checkbox fill #111, counts #767676, all borders rgba-black — so Design-panel color changes (accent, ink, hairline, surfaces) do not reach the default filter panel at all; only the opt-in refined style consumes tokens
- **[high]** Mobile Apply button is hardcoded black-on-white (#111/#fff), ignoring the accent tokens — every store gets the same black CTA in the drawer regardless of branding, on every mobile archive page
- **[medium]** Sort option names ('Popularity', 'Price: low to high', …) are the only visible panel strings NOT overridable via the label fields — Module::orderby_label() is gettext-only, so a Hebrew store can rename every other string but not the sort dropdown entries
- **[medium]** Mobile/desktop breakpoint fixed at 768px in three places (CSS twice + JS); owner cannot decide when the panel becomes a drawer
- **[medium]** Refined show-more cap fixed at 8 terms (CSS :nth-child(n+9) + JS SHOW_MORE_CAP) — not configurable per facet or globally
- **[medium]** Facet display type is fully auto-derived: product_cat is always a navigation tree, attributes become swatches whenever term meta exists, else checkboxes — the matrix offers no per-facet type choice (e.g. force pills, make categories a filter instead of navigation)
- **[low]** Auto price bands always target 4 bands with 1/2/5 rounding (Query_Builder::auto_bands count=4); owner controls exact bounds via price_bands but not the auto count
- **[low]** Desktop filter-change debounce fixed at 400ms (js:19); no setting
- **[low]** Drawer motion identity (0.3s slide, 0.25s overlay fade, 0.04s-step content cascade, 0.94 press-scale) is fixed; no way to quiet or rebrand the motion short of reduced-motion
- **[low]** Refined desktop panel is always sticky with top:1em and 100vh-2em height containment; no off switch if a theme header overlaps
- **[low]** Filtered-URL SEO policy (noindex,follow + clean canonical) is always-on with no owner toggle — a store wanting indexable filter landing pages (e.g. ?filter_pa_color=red) cannot opt out except by code
- **[low]** Facet-response cache TTL (5 min) and rate limit (30 req/60s) are filter/code-only knobs, no UI
- **[low]** Panel config is global-only: the Elementor widget deliberately has zero per-instance controls, so two archive templates cannot show different facet sets or styles

**Notes:** Structural findings: (1) The facet-configuration matrix (Admin_Config_Page + templates/admin-facet-config.php) bypasses the Settings_Hub schema — its own admin_post action, nonce, and option (shopos_core_shop_filters_facet_config); deliberate but it is the module's second settings surface. (2) Only the refined style (added later) consumes --shopos-ui-* tokens; the classic default predates the token system — the single biggest coverage hole. (3) Z-stack misuse: overlay and drawer both use --shopos-ui-z-max (fallbacks 99998/99999 differ, but with the token defined both layers get the SAME z-index and DOM order decides) — should be --shopos-ui-z-overlay/--shopos-ui-z-modal. (4) Dead localization: Shortcode::enqueue_assets() localizes ShopOSShopFilters (ajaxUrl/nonce/action/contextId) but shop-filters.js never reads it — the frontend is pure reload transport; the public Ajax endpoint (Ajax.php) has no JS consumer in this module. (5) README.md is stale — still describes Phase 6.1 'foundation only', 'disabled by default', and the shopos_core_shop_filters_indexer_enabled flag that was removed in 1.12.26 (always-on). (6) Admin_Page::render() inline-styles its progress bar (#ddd track, #2271b1 fill, inline JS) — admin-only chrome, not storefront brand, but it bypasses any asset pipeline. (7) The panel stylesheet is head-enqueued on EVERY front-end page (Shortcode::enqueue_style), so any styling fix here ships site-wide. (8) shopos-sf__overlay z-index fallback (99998) disagrees with the token default table (z-max = 99999) — harmless today, confusing later.

## shopos-core/src/Modules/VariableStockFix

**Behavior controls today:**
- run_daily_audit (checkbox, default 1) — enable/disable the daily 48h-lookback audit cron
- Bulk audit UI runtime controls (not persisted): dry-run toggle, Start scan, Stop

**Design controls today:**
- None — the module renders nothing on the storefront and consumes no --shopos-ui-* tokens; its only UI is a wp-admin bulk-audit panel

**Hardcoded styling:**

| Location | Value | Should be |
|---|---|---|
| `shopos-core/src/Modules/VariableStockFix/Module.php:505` | margin-right:15px (inline style on dry-run label) | --shopos-ui-space-md (admin-only screen; tokens not enqueued in wp-admin — see notes) |
| `shopos-core/src/Modules/VariableStockFix/Module.php:512` | margin-top:20px (progress wrapper) | --shopos-ui-space-lg (admin-only) |
| `shopos-core/src/Modules/VariableStockFix/Module.php:514` | background:#ddd; height:20px; border-radius:3px (progress track) | --shopos-ui-color-paper-dim / --shopos-ui-radius-sm (admin-only; #ddd ~ wp-admin gray) |
| `shopos-core/src/Modules/VariableStockFix/Module.php:515` | background:#2271b1; transition:width 0.3s (progress bar fill, no easing token, no duration token) | --shopos-ui-color-info + --shopos-ui-motion-base/--shopos-ui-ease (admin-only; #2271b1 is the stock wp-admin blue) |
| `shopos-core/src/Modules/VariableStockFix/Module.php:517` | margin-top:10px; font-family:monospace (counts line) | --shopos-ui-space-sm + --shopos-ui-font-mono (admin-only) |
| `shopos-core/src/Modules/VariableStockFix/Module.php:518` | background:#fff; border:1px solid #ccd0d4; padding:10px; max-height:300px; font-size:12px (log pane) | --shopos-ui-color-paper / --shopos-ui-color-hairline / --shopos-ui-space-sm / --shopos-ui-text-xs (admin-only; #ccd0d4 is the wp-admin border gray) |

**Gaps (owner cannot change):**
- **[medium]** No per-product opt-out for the owner — excluding a product from auto-unchecking requires code via the shopos_core/variable_stock_fix/should_check filter; no meta box, taxonomy gate, or settings list
- **[low]** Daily audit lookback window hardcoded to '48 hours ago' (Module.php:469)
- **[low]** Debounce delay hardcoded to 30s after a variation stock change (Module.php:219)
- **[low]** BATCH_SIZE fixed at 50 for AJAX batches, cron chunks, and self-chaining audit (Module.php:31)
- **[low]** Cron recurrence hardcoded to 'daily' with a fixed +1h first-run offset (Module.php:119); no time-of-day or frequency control
- **[low]** Audited post statuses hardcoded to publish/draft/private/pending in all three queries

**Notes:** Module correctly extends Module_Base with a settings_schema (single checkbox) — no legacy settings screen. All styling findings are wp-admin-only: the bulk-audit UI is inline HTML/CSS/JS printed from render_bulk_audit_ui() (Module.php:499-593) with no enqueued assets; --shopos-ui-* tokens live on :root/.shopos-theme via the theme stylesheet and are NOT loaded in wp-admin, so the hex values there (#2271b1, #ccd0d4, #ddd) are deliberate wp-admin-palette matches, not storefront brand leaks — token-mapping them is optional polish, not a rebrand blocker. Zero storefront CSS/JS/templates exist, so token coverage is trivially complete. HOOKS.md documents four planned-but-unshipped hooks (shopos_core/vsf/*) plus phantom AJAX/cron names — explicitly flagged 'do not rely on'; only should_check is real. Deprecated pre-1.9.0 hook/AJAX aliases are still registered with a stated 2.0.0 removal target. Importer.php only clears the legacy vpsf_daily_audit cron (legacy plugin had no options).
