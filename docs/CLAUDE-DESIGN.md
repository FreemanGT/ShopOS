# ShopOS — Design & Screens Reference

**The single visual map of the suite.** For every module: how it looks in the **backend** (admin settings the operator sees) and the **front-facing** UI (what the shopper sees). Plus the shared admin screens (menu, general settings, dashboard, tools, feature flags) and the **current design rules we follow**.

- **Suite:** `shopos-core` v1.25.1 (all business logic, 15 modules) · `shopos-theme` (Hello-Elementor child: tokens, RTL, Woo overrides) · `shopos-digital` (performance)
- **Audience:** bilingual **Hebrew-first / RTL** WooCommerce fashion shoppers on Elementor, mostly mobile; secondary audience is the store operator in wp-admin.
- **Last updated:** 2026-07-14
- **Companions:** [`DESIGN.md`](DESIGN.md) (full design system) · [`Modules.md`](Modules.md) (module catalog + roadmap) · [`PRODUCT.md`](PRODUCT.md) (product framing) · [`roadmap.md`](roadmap.md) (forward plan)

> **How to read this:** each module section states **Purpose**, **Status** (always-on vs. default on/off + where the kill-switch is), **Where to configure**, a **Backend — Settings** table (real setting keys, control types, choices, defaults), and **Front-facing — what the shopper sees** (the actual on-page UI, states, and CSS classes). Front-facing UI is measured against the design rules in §1.

---

## 1. Current design rules we follow

**Creative North Star — "The Quiet Boutique."** ShopOS dresses a WooCommerce storefront like a considered boutique dresses its floor: **ink on paper, type carrying the hierarchy, color held in reserve until it means something.** Near-monochrome by default (deep ink `#1b1b1b` on white + warm-neutral papers); emphasis is built from weight, scale, and spacing, not decoration. Bilingual and **RTL-first** — Hebrew is the primary reading direction and every rule must hold mirrored.

**Three words:** restrained, precise, trustworthy. A place where a shopper makes a **confident decision** without being shouted at.

### Design principles (the "why")
- **Serve the decision, not the page.** Every surface (search, filters, card, quick view, swatches, buy box) exists to move the shopper toward a confident purchase. Reduce friction; remove anything that doesn't help the decision.
- **Tokens are the single source of truth.** Modules consume `--shopos-ui-*` design tokens; visual change happens in the token layer so the whole suite stays coherent. Never hardcode what a token already expresses.
- **RTL & bilingual are first-class.** Hebrew is primary; every layout, label, price, and animation must be correct mirrored, locale-aware on both He and En sides.
- **Truthful state over optimistic state.** Stock, availability, price, and facet counts reflect **real per-variation truth** — a sold-out size drops out, a no-match search shows an honest empty result. Never show a product the shopper can't actually buy.
- **Restraint is the brand.** Type, weight, and space before color or ornament. When in doubt, remove.

### Named rules (the "what")
- **The Ink-First Rule.** The primary accent is ink, not a hue. Reach for `--shopos-ui-color-ink` before any color. Gold/forest are per-site opt-in themes (`.is-accent-*`) — color on demand, never by default.
- **The Semantic-Only Color Rule.** Green/red/amber/blue appear **only** to report state (in-stock, error, caution, info). Forbidden as decoration. If a color isn't telling the shopper something true, it doesn't belong on the page.
- **The Hebrew Flat-Tracking Rule.** Letter-spacing is zeroed under `:lang(he)`. Latin gets the −0.01em display tightening and the 0.04em label spacing; Hebrew gets neither. Never ship a tracked Hebrew heading.
- **The One Uppercase Voice Rule.** Uppercase is reserved for the **Label** role (buttons, badges, H6 eyebrow, filter chips). Headings and body are never uppercased. No tracked all-caps eyebrow above every section.
- **The Flat-At-Rest Rule.** Surfaces rest tonally flat. Shadow appears as a reaction to hover/focus/float (panel/drawer/modal) — never as resting decoration on a static card.
- **The Hairline-Does-The-Work Rule.** Before adding a shadow to separate two surfaces, try a 1px hairline (`#e6e6e2`) or a tonal paper step. Shadows are the last resort, not the first.

### Type & color at a glance
- **Type:** Assistant (display/headings), Heebo (body/labels) — Hebrew-native humanist sans, Rubik → system fallback. Roles: **Display** 600 / clamp 2.5→4rem · **Headline** 600 / 1.9→2.75rem · **Title** 500 / 1.1→1.25rem · **Body** 400 / ~16px / line-height 1.55 (measure ≤65–75ch) · **Label** 600 / ~12px / 0.04em / UPPERCASE.
- **Color:** Ink `#1b1b1b` / Ink-soft `#3a3a3a` / Ink-muted `#6b6b6b` (text floor on paper) · Paper `#ffffff` → Paper-soft `#faf9f7` → Paper-dim `#f1efea` → Sand `#e9e4db` (depth ramp) · Hairline `#e6e6e2` · opt-in Gold `#b68a3a`. Semantic: Success `#0e7c66` · Danger `#b11226` · Warning `#a8630a` · Info `#225e8f`.
- **Depth:** tonal first (three paper steps + hairlines), shadow second — `sm` at rest on cards, `md` on hover/panels, `lg` on drawers/modals, `xl` on full-screen overlays.
- **Radius:** buttons 4px (`--shopos-ui-button-radius`), cards 6px (`--shopos-ui-card-radius`), pills 999px.

### Accessibility & motion (always on)
- Target **WCAG 2.1 AA**: body ≥4.5:1, large text ≥3:1; visible focus on every interactive control (filters, search combobox, quick-view drawer, swatches); keyboard-operable throughout; full RTL correctness.
- `prefers-reduced-motion` collapses token durations to 0 (spinners freeze to a static ring; fades/slides disable). `prefers-contrast: more` strengthens borders and drops shadows. Both wired in the token layer, so modules inherit them for free.

### The anti-references (what we never ship)
- **Generic Elementor/Woo defaults** — badge-encrusted cards, templated icon-heading-text grids, busy loop widgets, anything that reads as assembled from off-the-shelf parts.
- **Discount-bin clutter** — flashing sale callouts, urgency spam, competing CTAs, cramped grids.
- **AI-slop trend-chasing** — glassmorphism, gradient text, cream-everything, a tracked uppercase eyebrow above every section. No dark mode (the brand is ink on paper).

---

## 2. The admin (backend chrome)

Every module's settings page inherits this shared chrome; the operator's job is to keep the storefront tuned without touching code.

### 2.1 Admin menu & navigation
ShopOS is its **own top-level wp-admin menu** (not nested under WooCommerce), registered in `Settings_Hub::register_menu()`:

- **Menu:** "ShopOS", icon `dashicons-admin-generic`, position `58` (just below the WooCommerce cluster). Every page gated by the `manage_woocommerce` capability.
- **Submenus, in order:** **Dashboard** (default landing) → **one page per module** (auto-generated by looping the registry; slug `shopos-{module_id}`, alphabetized) → **Feature Flags** → **Tools**.
- Modules are **auto-discovered** from `src/Modules/*/Module.php` and `ksort`ed — adding a module needs no registry edit; it just appears.
- **RestockNotify is the exception:** it registers its own separate top-level menu ("Restock Notify" / התראות מלאי) with Dashboard / Subscribers / Email Templates / Settings sub-pages, because it predates the Settings_Hub pattern.

**Module enable/disable UI** lives on the **Dashboard**, not a settings screen:
- **Per-card toggle** — each module card has a small form posting to `admin-post.php?action=shopos_toggle_module` (nonce-protected). Enabled → a **"Disable"** `button-secondary`; disabled → an **"Enable"** `button-primary`. Flipping the `shopos_core_modules` option calls `on_activate()`/`on_deactivate()` and flushes rewrite rules.
- **Onboarding wizard** (until onboarded) — a checkbox checklist of every module saved via the WP Settings API.
- Opening a **disabled** module's settings page shows an inline `notice-warning`: *"This module is disabled. Enable it from the Dashboard."*

### 2.2 General settings framework (`Settings_Hub`)
Modules never write their own admin page — they return a **declarative schema** from `settings_schema()` and the hub renders it. This is the shared look every settings page has.

- **Field/control types** (`render_field()`): `text` (regular-text input, the default) · `textarea` (large-text) · `checkbox` (single box + optional inline label) · `select` (built from a `choices` value⇒label map — **always `choices`, never `options`**) · `color` (a text input expecting a hex value + `#000000` placeholder, *not* a native color picker) · `number` (small-text). Sanitizers additionally recognize `email` and `url`.
- **Page layout** — single-column, **not tabbed**: `<div class="wrap shopos-wrap">` → `<h1>` module label → `<p class="description">` → optional disabled notice → one `<form action="options.php">` with `settings_fields('shopos_' + id)`. Fields group by their `section` value; **each section is an `<h2>` heading + a `<table class="form-table">`** (classic WP two-column label/field layout) → standard `submit_button()` → a `do_action('shopos_core/module_page/{id}')` extension hook for custom UI (used by Search, ShopFilters, ProductFeed, VariableStockFix for their index/audit tools).
- **Save flow** — pure WordPress Settings API; posts to `options.php`, persists, redirects with the native "Settings saved" notice.
- **Admin CSS** (`assets/css/admin.css`, scoped under `.shopos-wrap`, enqueued only on `shopos` screens) rides mostly on native WP form-table chrome. Flat status palette: emerald `#10b981`, amber `#f59e0b`, red `#ef4444`; card border `#e5e5e5`, muted text `#555`/`#666`, 8px radii. **No custom iOS-style toggle switch** — module on/off is a standard WP button; settings checkboxes are native.

### 2.3 Dashboard (`Admin/Dashboard.php`)
The operator's home. Top to bottom, inside `.shopos-wrap.shopos-dashboard`:
1. **Title bar** — `<h1>` "ShopOS" + a small greyed `v1.25.1` version tag.
2. **Environment health bar** — a wrapping row of rounded **pill badges** (five checks: PHP ≥7.4, WordPress ≥6.0, WooCommerce present+version, Elementor optional, DB migration state). Pass = green pill, fail = red, optional-missing = amber, neutral = grey.
3. **Onboarding card** (until onboarded) — white rounded card, "Welcome to ShopOS", a checkbox list of all modules, "Save & continue" + "Skip onboarding".
4. **Module grid** (`.shopos-modules-grid`, `repeat(auto-fill, minmax(280px, 1fr))`, 16px gap) — one **card per module**: header row with module title + a **10px status dot** (green/amber/red from the module's own `health()`, message as a tooltip), a 13px muted description, and an actions row (Enable/Disable button + a **Settings** button deep-linking to the module page).
- **Admin notices** the dashboard injects: boot-failure `notice-error` (modules that threw on boot), legacy-plugin conflict errors (e.g. a module disabled because a legacy plugin of the same class is still active, with remediation), and an off-dashboard dismissible "pick which modules to activate" nudge.

### 2.4 Tools (`Admin/views/tools.php`)
- **Legacy plugin import** — a `widefat striped` table (Module / Legacy plugin / Status / Imported) with inline status dots, a non-destructive **"Run legacy import"** button, and a confirm-gated **"Deactivate & delete legacy plugins"** button (disabled when nothing is detected).
- **Settings export / import / backups** (`Settings_Tools`) — **Export** streams a versioned JSON envelope of every `shopos_core_*` / `shopos_digital_*` option (excluding the log buffer, boot diagnostics, and the backup store). **Import** takes a JSON file (envelope v1 only, keys whitelisted to the two prefixes) and **auto-backs-up current state after validation, before the first write**; best-effort, halt-on-first-failure. **Backups** is a `widefat striped` table of the 5 most recent auto-backups (Timestamp UTC / Source / From site / Options count / Restore) — each **Restore** confirms and itself snapshots first, so any restore is undoable.
- **Recent log** — `Logger::entries()` in a dark terminal-style `<pre class="shopos-log">` (`#1d2327` bg, mono, newest first, `[time] LEVEL: message`, 320px scroll).
- *(No reindex button here — per-module reindex tools live on the module pages via the extension hook.)*

### 2.5 Feature Flags (`Admin/views/feature-flags.php`)
Roll-out switches for features that ship off, meant to be flipped once per site. Flags group by module — each group an `<h2>` + `form-table`; each row is a label, an **"Enabled"/"Disabled"** checkbox, a description, and a `<code>` line with the equivalent **WP-CLI command** (`wp option update <option> 0|1`) and "since \<version\>". A single "Save feature flags" button writes the whole set. A flag pinned by the `shopos_core/feature_flag/{module}/{feature}` PHP filter renders **disabled** with an amber "a code-level filter is forcing this flag" warning.

**Current registry — only 5 flags remain, all in VariationSwatches** (every other module graduated to always-on; the module-enable toggle is now the single kill-switch):

| Module | Feature | What it turns on | Since |
|---|---|---|---|
| variation_swatches | `card_image_swap` | Swatch click swaps the product-card image on shop/archive (no navigation) | 1.11.23 |
| variation_swatches | `image_swatches` | Term uses an uploaded image instead of a colour (upload UI + thumbnails) | 1.11.24 |
| variation_swatches | `tooltip` | CSS hover tooltip on colour/image swatches | 1.11.25 |
| variation_swatches | `auto_color` | Auto colour sampler — samples a variation image to a hex, cached as post-meta (shared switch) | 1.11.27 |
| variation_swatches | `bundle_compat` | WPC Bundles / FBT compatibility (forwards buy-box fields to add-to-cart) | 1.11.40 |

Option names: `shopos_core_{module}_{feature}_enabled`, strict-boolean parsed (`'false'`/`'no'`/`'off'`/`0`/`''` all read off).

---

## 3. Modules — backend settings & front-facing look

15 built modules across the storefront lifecycle. **Status legend:** *Always-on* = feature flags graduated, boots whenever the module is enabled · *Default ON* = seeded enabled on activation · *Default OFF* = absent from the seeded module set. Every module's on/off lives on the Dashboard.

## 3.1 Storefront & Discovery

### Search — `src/Modules/Search/`
**Purpose:** In-house full-text product search that powers a live results dropdown / command-palette on any theme search field plus a `[shopos_search]` box, backed by a background `shopos_search_index` FULLTEXT table (replaced Advanced Woo Search).
**Status:** Default ON; all internal surfaces (indexer, dropdown, shortcode, results page) always-on since 1.21.0.
**Where to configure:** ShopOS → Search (`shopos-search`).

**Backend — Settings**

| Setting | Control | Options / Range | Default |
|---|---|---|---|
| Minimum characters (`min_chars`) | number | chars before the dropdown searches | `2` |
| Debounce (`debounce_ms`) | number | idle delay after last keystroke (ms) | `200` |
| Max dropdown results (`max_results`) | number | clamped 1–20 | `8` |
| Show product image (`show_image`) | checkbox | thumbnail on each result | on |
| Show price (`show_price`) | checkbox | price on each result | on |
| Show SKU (`show_sku`) | checkbox | SKU on each result | off |
| **Label overrides** (`label_*`, He/En, blank = En default) | text ×7 | `placeholder` "Search products…" · `button` "Search" · `no_results` "No products found" · `see_all` "See all results" · `searching` "Searching…" · `toggle` "Search" · `close` "Close search" | all blank |
| Reindex tool (`shopos_core/module_page/search`) | button + progress | "Reindex all products" / "Stop"; batches of 50; live "Indexed: N · auto-reindex scheduled" status | — |

**Front-facing — what the shopper sees**
- Two presentations, one row style (`search.css`): an **anchored dropdown** (`.shopos-search-panel`, body-appended, JS-positioned under any matched `input[type="search"], input[name="s"]`) and, for the shortcode, a **centered command palette** (`.shopos-search-modal`) over a dimmed scrim.
- The shortcode renders a magnifier-icon button (`.shopos-search-trigger`); clicking opens the modal (max-width 600px, rounded, slides down + scales in) with the field, a circular close "X", and live results.
- Each result row (`.shopos-search-item`): optional 52×52 rounded thumbnail, bold single-line title (ellipsis), price (struck-through `del` on sale), optional muted SKU. Hover and keyboard focus both paint `--shopos-ui-color-paper-soft` via `[aria-selected="true"]`.
- Footer "See all results" (uppercase, tracked); empty state a centered muted "No products found".
- **Interaction:** debounced fetch past the min-char threshold, `AbortController`-cancelled; combobox ARIA with ↑/↓ navigation, Enter opens/submits, Esc closes; a visually-hidden `aria-live` region announces the result count.
- **Mobile (≤768px):** a brand-primary "Search" button appears and the palette floats above the software keyboard (mirrors `window.visualViewport` into `--shopos-vvh/--shopos-vvt`). Desktop hides the button (Enter carries it).
- RTL: price sits inside the body stack so it right-aligns; all wording overridable; a `<noscript>` FOUC guard keeps the native form visible if JS is off.

### ShopFilters — `src/Modules/ShopFilters/`
**Purpose:** Faceted, context-aware filters (categories, price bands, attributes/swatches, on-sale/in-stock flags, sort) for shop & category pages, with **per-variation in-stock truth**, surfaced via `[shopos_shop_filters]`.
**Status:** Default ON; storefront surfaces always-on since 1.12.25. Constrains both the main query and Elementor ProductSlider grids.
**Where to configure:** ShopOS → Shop Filters (`shopos-shop_filters`).

**Backend — Settings**

| Setting | Control | Options / Range | Default |
|---|---|---|---|
| Price bands (`price_bands`) | text | comma-separated upper points, e.g. `50, 100, 200, 500`; blank = auto-derive | blank |
| Default sort (`default_sort`) | select | — Woo default — · menu_order · popularity · rating · date · price · price-desc | `''` (Woo default) |
| Filter panel style (`filter_style`) | select | `classic` (checkbox lists) · `refined` (size pills, collapsible facets, compact) | `classic` |
| **Label overrides** (`label_*`, He/En, blank = En) | text ×16 | toggle "Filter sizes & prices" · panel_title "Filter" · close · categories · price · sort · flags_heading "Availability" · onsale · in_stock · clear_all · apply · clear · count_singular "%d product" · count_plural "%d products" (+ aria labels) | all blank |
| Reindex tool | button + progress | "Reindex now" + index status | — |
| Facet-configuration matrix (`Admin_Config_Page`) | table per taxonomy | per-facet **Show** (checkbox) · **Order** (number) · **Hide on categories** (multi-select of the indented category tree); writes `shopos_core_shop_filters_facet_config` | all shown, tree order |

**Front-facing — what the shopper sees**
- **Desktop:** an inline sidebar panel (`.shopos-sf`). **Mobile (≤768px):** collapsed behind a full-width pill toggle that opens a **bottom-sheet drawer** (`.shopos-sf__panel`, slides up over a `rgba(0,0,0,.45)` overlay, rounded top, staggered section rise) with a sticky dark-pill "Apply filters" + ghost "Clear" bar.
- Panel order: active-filter **chips** (`.shopos-sf__chip`, pill with `×`) + "Clear all" → result-count line → **Sort** select → **Price** bands (checkboxes with `wc_price` ranges, open-ended "$500+", counts) → **Availability** flags (On sale / In stock + counts) → **Category tree** (nested nav of links to term archives, not filter params) → attribute facets.
- Attribute facets render as **checkbox lists** (`accent-color:#111`, tick-pop animation, per-term counts) or **colour/image swatches** (`.shopos-sf__swatch-chip`, 28px circles from term image or hex; selected = double ring; text-only fallback pills).
- **Refined style** (`.shopos-sf--refined`): attribute values become **pill buttons** (selected = soft fill + inset ink ring + bold), long lists cap at 8 behind a "+N / show more", facets are collapsible via a CSS chevron, chips get a circular `×` badge, and the panel is a sticky, internally-scrolling column. **Classic** keeps the plain checkbox layout byte-for-byte.
- **Transport is reload-based:** desktop navigates on every change; mobile defers until "Apply". `.shopos-sf--loading` dims the panel during navigation. RTL-safe (drawer slides from the bottom; nesting uses `padding-inline-start`). Assets: `shop-filters.css/js`.

### InfiniteScroll — `src/Modules/InfiniteScroll/`
**Purpose:** Auto-loads the next page of a product grid (stock Woo, Elementor widgets, block grids) as the shopper nears the bottom, with real-card-measured skeletons and back/forward grid restoration.
**Status:** Default ON; trigger-modes always-on since 1.23.0. No Labels editor (its one string is the `end_message` setting).
**Where to configure:** ShopOS → Infinite Scroll (`shopos-infinite_scroll`).

**Backend — Settings**

| Setting | Control | Options / Range | Default |
|---|---|---|---|
| Skeleton cards (`skeleton_count`) | number | placeholders while loading | `6` |
| Max pages (`max_pages`) | number | hard safety cap on auto-loaded pages | `50` |
| End-of-list message (`end_message`) | text | shown at the end | "You have reached the end." |
| Trigger mode (`trigger_mode`) | select | `auto` (scroll/observer) · `button` (halt auto-load) · `hybrid` (auto for first N, then halt) | `auto` |
| URL update on advance (`history_mode`) | select | `pushState` · `replaceState` · `disabled` (leave URL unchanged) | `disabled` |
| Hybrid threshold (`hybrid_threshold`) | number | pages to auto-load before halting (hybrid) | `2` |
| Container selector override (`container_selector`) | text | CSS selector(s); blank = built-in 11-entry list | blank |
| Shimmer base / highlight color (`shimmer_base_color` / `shimmer_highlight_color`) | color | hex | `#eceff3` / `#f6f8fb` |
| Shimmer duration (`shimmer_duration_ms`) | number | 0–60000 (ms) | `1400` |
| Fade-in duration / distance (`fade_duration_ms` / `fade_transform_px`) | number | 0–60000 ms / 0–200 px | `550` / `18` |

**Front-facing — what the shopper sees**
- **Auto mode:** native pagination hidden; the next `/page/N/` loads automatically as an invisible sentinel nears the viewport (IntersectionObserver, 800px prefetch `rootMargin`, scroll fallback + iOS poll).
- While fetching, N **skeleton cards** (`.shopos-skeleton`) append into the grid — shimmering image block + short/full/price lines with a sweeping gradient (~1.4s). The JS **measures a real card** so skeletons clone its exact width/height/padding/image-height (avoiding Woo float-width slivers).
- New products **fade + rise in** (`.shopos-new-product`, opacity 0→1 with `translateY(18px)`→0 over ~550ms, 40ms per-card stagger).
- **End state:** centered muted "You have reached the end." with a hairline rule above. **Failure:** "Could not load more." + an inline "Load more" retry button.
- **Continuity:** Back/forward replays a sessionStorage snapshot of the grid HTML and re-anchors scroll to the card that was on-screen; shared `/page/N/` deep-links reset to page 1 so recipients see the full grid.
- **A11y:** `aria-live="polite"` announces "Loaded N more products."; skeletons `aria-hidden`; reduced-motion disables shimmer + fade. Assets: `infinite-scroll.css/js`.

### CategorySlider — `src/Modules/CategorySlider/`  *(Elementor widget)*
**Purpose:** An editorial horizontal carousel of product categories (thumbnail cards, drag-scroll, hover ring, progress/dots).
**Status:** Default ON module; requires WooCommerce + Elementor. Appears in the Elementor panel under **WooCommerce**/**General** as **"ShopOS Category Slider"**.
**Where to configure:** Elementor editor → ShopOS Category Slider → controls.

**Backend — Settings** *(Elementor controls)*

| Section | Setting | Control | Options / Range | Default |
|---|---|---|---|---|
| Content | `eyebrow` / `headline` / `headline_mute` | Text | free text | "Shop by category" / "The Spring Edit." / "Curated essentials, season-led." |
| Content | `limit` (Max categories) | Slider | 1–50 | 12 |
| Content | `orderby` / `order` | Select / Choose | name·count·slug·menu_order / ASC·DESC | name / ASC |
| Content | `hide_empty` / `parent_only` | Switcher | yes/no | yes / yes |
| Content | `child_of` / `include` / `exclude` | Select / Select2 | product_cat terms | empty |
| Layout | `per_view` / `_tablet` / `_mobile` | Slider | 2–8 / 2–8 / 1–4 | 5 / 4 / 2 |
| Layout | `gap` / `card_height` | Slider (px) | 4–48 / 180–420 | 20 / 280 |
| Behavior | `direction` | Choose | auto (follow site) · ltr · rtl | auto |
| Behavior | `snap` | Choose | none (free scroll) · card · page | none |
| Behavior | `mouse_drag` / `show_arrows` | Switcher | yes/no | yes / yes |
| Behavior | `indicator` | Choose | progress · dots · none | progress |
| Behavior | `autoplay` / `autoplay_delay` / `loop` | Switcher / Slider | 1000–15000 ms | off / 5000 / off |
| Style | `shape` | Choose | circle · soft · rect · pill | soft |
| Style | `show_count` | Choose | hover · always · none | hover |
| Style | `accent` / `ring_color` / bg·ink·mute·line colors | Color | → `--cs-*` | empty (built-in oklch tokens) |
| Style | arrow size / radius / duration | Slider | 24–96 px / 0–50% / 0–1000 ms | 40 / 50% / 180 |
| Style | eyebrow / headline / name typography | Typography group | full Elementor | theme inherit |

**Front-facing — what the shopper sees**
- A header row (`.cs-head`): uppercase eyebrow, a large light-weight headline (`clamp(28→48px)`) + muted subtext, prev/next arrows, over a hairline bottom border.
- A horizontal flex track (`.cs-track`) of linked category cards; each card is a thumbnail with the category name + item count (`.cs-meta`). Free-scroll by default (optional per-card/per-page scroll-snap); soft mask fade at the edges; `overscroll-behavior-x: contain` clamps at the last card.
- Desktop **mouse drag-to-scroll** (grab/grabbing cursor); clicks still navigate. Touch lets the browser own scrolling.
- **Hover:** a growing outline ring (`.cs-ring`), image scale to 1.015, product count fading in when `show_count=hover`.
- Four card shapes (circle/soft/rect/pill). Missing thumbnails get a **diagonal-stripe placeholder** tinted by a stable hue derived from the slug + a lowercase mono label.
- Footer indicator: a draggable 1px **progress bar** doubling as a scrubber (`NN / NN` mono counter) or centered dots.
- RTL-aware (`dir` flips arrows/drag/mask/progress); ≤640px hides arrows and collapses the head. Respects reduced-motion. Assets: `category-slider.css/js`.

### ProductSlider — `src/Modules/ProductSlider/`  *(Elementor widget)*
**Purpose:** A **slider or grid** of standard WooCommerce shop-loop product cards (rendered via `wc_get_template_part('content','product')`, so every card plugin lights up here too).
**Status:** Default ON module; requires WooCommerce + Elementor. Elementor panel **"ShopOS Product Slider"**. Reuses CategorySlider's `.cs-*` chrome/JS + its own `product-slider.css`.
**Where to configure:** Elementor editor → ShopOS Product Slider → controls.

**Backend — Settings** *(Elementor controls)*

| Section | Setting | Control | Options / Range | Default |
|---|---|---|---|---|
| Content | `eyebrow` / `headline` / `headline_mute` | Text | free text | "Featured" / "New arrivals." / "Hand-picked for the season." |
| Query | `limit` (Max products) | Slider | 1–48 | 12 |
| Query | `orderby` | Select | date · price · popularity · rating · menu_order · title · rand | date |
| Query | `order` | Choose | ASC · DESC | DESC |
| Query | `source` | Select | All · Featured · On-sale · By category · By tag · Manual selection · **Current query (archive)** · Related | all |
| Query | `categories` / `tags` / `include_ids` / `exclude_ids` | Select2 / Text | terms / comma IDs | empty |
| Query | `hide_free` / `hide_out_of_stock` | Switcher | yes/no | off / off |
| Layout | `per_view` / `_tablet` / `_mobile` | Slider | 2–6 / 2–6 / 1–3 (0.1 step → mobile peek) | 4 / 3 / 1.4 |
| Layout | `gap` / `card_height` | Slider (px) | 4–48 / 180–480 | 20 / 320 |
| Behavior | `display_mode` | Choose | **slider** (draggable + progress) · **grid** (static, paginated) | slider |
| Behavior | `direction` / `snap` / `mouse_drag` / `show_arrows` / `indicator` | (slider only) | as CategorySlider | auto / none / yes / yes / progress |
| Behavior | `autoplay` / `autoplay_delay` / `loop` | (slider only) | 1000–15000 ms | off / 5000 / off |
| Style | `shape` | Choose | soft · rect | soft |
| Style | `show_cart` / `show_sale_badge` | Switcher | yes/no | yes / yes |
| Style | `accent` / `ring_color` / `name_color` / `price_color` | Color | → card | empty |
| Style | eyebrow / headline / name / **price** typography | Typography group | full Elementor | theme inherit |

**Front-facing — what the shopper sees**
- The same editorial `.cs-head` as CategorySlider, then either a draggable `<ul class="cs-track products">` (slider) or a static CSS grid `<ul class="cs-grid products">` (grid, `repeat(--cs-per, 1fr)`).
- Each item is a **real WC product `<li class="product">`** (`.cs-card` via `post_class`): image, 2-line-clamped left-aligned title, price recolored to ink (regular strikethrough muted), star rating, and a pill add-to-cart button in the ink color that inverts on hover.
- **Slider mode:** horizontal drag-scroll with momentum, optional card/page snap, edge mask, arrows, and a progress/dots indicator; mobile shows a fractional **"peek"** of the next card (default 1.4). **Grid mode:** drops all drag/arrow chrome and (source = Current query) renders full archive pages with `paginate_links()` pagination.
- **Sale flash** is restyled from WC's big green circle into a small accent uppercase pill top-left (flips right in RTL). Images use `object-fit: contain` pinned to `--cs-card-h`, requesting the `large` source with a computed `sizes` attr.
- Because cards are verbatim WC loop markup, third-party card plugins (QuickView, HoverSwap, VariationSwatches picker) apply unchanged; InfiniteScroll skeletons height-match the real cards. RTL-aware, responsive columns, reduced-motion respected.

### HoverSwap — `src/Modules/HoverSwap/`
**Purpose:** Enhances the product-card image on loops — a cross-fade to the second image on hover, or a small swipeable gallery slider of all images.
**Status:** Default ON module (requires WooCommerce), but **ships "dark"** — `card_image_mode` defaults to `none`, so there's no storefront output until a mode is chosen. Admin label **"Card Image Effects"**. Hooks `woocommerce_before_shop_loop_item_title`, so it covers WC loops and the ProductSlider grid alike.
**Where to configure:** ShopOS → Card Image Effects (`shopos-hover_swap`).

**Backend — Settings**

| Setting | Control | Options / Range | Default |
|---|---|---|---|
| Card image mode (`card_image_mode`) | select | **none** (leave alone) · **hover_swap** (fade to 2nd image on hover) · **gallery_slider** (swipeable slider of all images) | `none` |
| Slider arrows (`slider_arrows`) | checkbox | show prev/next (fade in on hover); off = swipe-only. *Gallery-slider mode only.* | on |

**Front-facing — what the shopper sees**
- **`none`:** unchanged WC thumbnail.
- **`hover_swap`:** an overlay `<img>` (the product's first gallery image) injected as a sibling of the primary thumbnail; hover cross-fades it in over 0.35s and out on leave. No gallery image → renders nothing. Pure CSS — infinite-scroll-loaded cards work with no JS.
- **`gallery_slider`:** WC's single thumbnail becomes `.shopos-card-slider` — a horizontal viewport of all images (primary first, deduped), each slide `flex: 0 0 100%` with `scroll-snap-align: start / stop: always`. Single-image products fall back to a plain image, no chrome.
- Swipe is native CSS scroll-snap (touch + trackpad, RTL-correct without JS); `card-slider.js` adds progressive-enhancement mouse drag + optional circular prev/next arrows (`.shopos-card-slider__arrow`, 26px white, hidden until card hover, next-chevron mirrored in RTL). Slider corners `border-radius: 1rem`.
- Hover reveal is gated to `(hover: hover) and (pointer: fine)` so a tap never sticks the overlay/arrows; both modes honor reduced-motion. Assets: `hover-swap.css`, `card-slider.css/js`.

## 3.2 Product & Conversion

### ProductPage — `src/Modules/ProductPage/`
**Purpose:** Takes over the WooCommerce single-product page (PDP) with an editorial "Quiet Boutique" layout + a coupon-price notice and low-stock urgency badge in the buy box.
**Status:** Always-on since 1.23.0 (layout / coupon-notice / stock-urgency flags graduated). Module-off registers nothing and the existing Elementor page renders untouched.
**Where to configure:** ShopOS → Product Page (`shopos-product_page`).

**Backend — Settings**

| Setting | Control | Options / Range | Default |
|---|---|---|---|
| Coupon code | text | any live WC coupon code; notice shows only while an unexpired coupon with this exact code exists | `''` |
| Discount percent | number | 1–99 (computes the shown price) | `0` |
| Show urgency up to (units) | number | badge shows while the picked variation has 1..N units in managed stock | `5` |
| Buy button colour | color (hex) | optional hex into the VS buy box + mobile sticky CTA; empty keeps VS's native red | `''` |
| **Editable labels** (`label_*`, He/En, blank = En) | text | `coupon_intro` "Enter coupon code" · `coupon_outro` "and the product will cost you:" · `urgency_last_unit` "Last one in stock" · `urgency_units_left` "Only {count} left in stock" · `trust_shipping` (blank → item hidden) · `trust_returns` (blank → item hidden) | as noted |

**Front-facing — what the shopper sees**
- An editorial **gallery** (`.shopos-ui-pdp__gallery`) — one image at a time, a horizontal scroll-snap strip at every breakpoint (swipe on mobile, mouse click-drag on desktop `.is-grabbing`), with a slim scroll-progress bar below that never fully empties. **No lightbox** (the corner expand trigger is `display:none`; hover-magnify zoom kept); a quiet ink `span.onsale` pill in the corner.
- A framed, paper-dim **sticky summary column** (`.shopos-ui-pdp__summary-col`, sticky ≥1024px) holding title / rating / price / buy box; sale prices show a red `<del>` from-price.
- The **buy box** renders the standard WC add-to-cart stack, so VariationSwatches' own buy box lights up unaided (red pill CTAs unless a Buy button colour is set); variation selects restyled full-width; a variation image-swap fades the main gallery image.
- A **coupon-notice card** (`.shopos-ui-coupon-notice`) under the buy box: white card, dashed-border code chip, and a bold red discounted price that live-swaps per picked variation (JS reads a `data-shopos-ui-coupon-prices` map on `found_variation`/`reset_data`).
- A **stock-urgency badge** (`.shopos-ui-stock-urgency`) — amber warning-tone pill with a flame icon — revealed only when the picked variation is inside the 1..N band ("Last one in stock" / "Only 3 left in stock").
- A **trust line** (`.shopos-ui-pdp__trust`, truck + returns icons), a collapsed **additional-information** `<details>` (attribute table), then product meta.
- Below: an **accordion** of description/reviews/plugin tabs (`.shopos-ui-pdp__accordion`, rotating chevron), then restyled **upsells/related** as a 2-up (mobile) / 4-up (desktop) grid.
- A **mobile sticky add-to-cart bar** (`.shopos-ui-pdp__sticky-bar`, hidden ≥1024px) that slides up once the summary scrolls out of view (IntersectionObserver); thumb + title + price + CTA that scrolls back to the buy box, deferring entirely if VS's own sticky bar is present.
- Fully RTL-first (CSS logical properties), Hebrew flat-tracking on titles/buttons, tokenized via `--shopos-ui-*`. Assets: `product-page.css/js`, `coupon-notice.css/js`, `stock-urgency.css/js`.

### QuickView — `src/Modules/QuickView/`
**Purpose:** Puts a magnifier icon on every product-loop card that opens a slide-in drawer previewing the product (gallery, price, description, add-to-cart) without leaving the listing.
**Status:** Always-on since 1.23.0. Module-off boots no markup, assets, or AJAX endpoint.
**Where to configure:** ShopOS → Quick View (`shopos-quick_view`).

**Backend — Settings** *(label editor only — no numeric/toggle settings)*

| Setting | Control | Default |
|---|---|---|
| Trigger accessible name / Drawer title | text | "Quick view" |
| Close button label | text | "Close" |
| Full-details link | text | "View full details" |
| Loading / Error message | text | "Loading…" / "Could not load this product. Please try again." |

**Front-facing — what the shopper sees**
- A small circular **trigger** (`.shopos-qv-trigger`) — black magnifier glyph on a white circle with a soft shadow — pinned to the top **physical-right** corner of every card (opposite the sale flash; deliberately `right`, not logical), on Elementor archive grids and ProductSlider cards. Hover is a subtle `scale(1.12)`.
- Clicking fetches drawer content over admin-AJAX and slides in a **side drawer** (`.shopos-quick-view__panel`, `width: min(480px, 94vw)`, full-height, anchored to the inline-end edge — the **left** edge on the RTL store) with a dark overlay fade and a `translateX + scale(.98)` slide (RTL flips direction). *Note: one edge-anchored drawer at all breakpoints — not a centered desktop modal / bottom mobile sheet.*
- The body renders the **standard single-product summary** stack (title / rating / price / short description / add-to-cart / meta), rendered in the **site locale** so VS's buy box swaps in and the price de-dupes exactly as on the PDP.
- The gallery **reuses the HoverSwap card-slider verbatim** (`.shopos-card-slider` — arrows, snap, drag, loop) when ≥2 images; a single featured image otherwise (same `card-slider.css/js` handle).
- A bottom "View full details" link deep-links to the real PDP. Opening locks body scroll, focuses + traps Tab in the panel; Esc / overlay / close-X closes and returns focus to the trigger. `aria-modal`, `aria-live`, reduced-motion-safe. Assets: `quick-view.css/js` (+ shared card-slider).

### VariationSwatches — `src/Modules/VariationSwatches/`
**Purpose:** Replaces WooCommerce's variation UI with live colour/label/image swatches + a modern quick-add buy box — a compact picker on shop/archive cards and a full swatch buy box (colour chips, size pills, qty stepper, Add-to-cart / Buy now) on the PDP.
**Status:** Default ON module. Five internal **feature flags** remain (card image swap, image swatches, tooltip, auto-colour sampler, bundle compat — see §2.5).
**Where to configure:** ShopOS → Variation Swatches (`shopos-variation_swatches`). *Settings were relocated out of the retired WooCommerce → Products "Shop swatches" tab (Wave 2.2/4g); stored under `shopos_core_variation_swatches_*`, legacy `shopos_vs_*` fallback.*

**Backend — Settings** *(15 modern keys)*

| Setting | Control | Range | Default |
|---|---|---|---|
| Enable shop-grid picker (`shop_enabled`) | checkbox | — | on |
| Max visible swatches/attribute (`shop_max_visible`) | number | 1–50 | 5 |
| Show price in archive picker (`shop_show_price`) | checkbox | — | off |
| Apply on shop / category / tag / search / related (`shop_apply_*`) | checkbox ×5 | — | all on |
| Excluded category IDs (`shop_excluded_categories`) | text | comma-separated term IDs | empty |
| PDP: hide out-of-stock variations (`pdp_hide_oos`) | checkbox | — | off |
| Shop: hide out-of-stock swatches (`shop_hide_oos`) | checkbox | — | on |
| Shop: skip pre-selecting any variation (`shop_no_preselect`) | checkbox | force an explicit choice | on |
| Shop: hide attribute labels (`shop_hide_attr_labels`) | checkbox | hide the "Size:" row | on |
| Shop: hide selected-option text (`shop_hide_selected`) | checkbox | — | on |
| Shop: show name & price only (`shop_names_price_only`) | checkbox | hide picker + add-to-cart on cards; name + price only | off |

Buy-box copy is **not** admin-editable — `Labels.php` auto-switches He/En by site locale: Add to cart / הוספה לעגלה · Buy now / קנה עכשיו · Choose an option / בחר/י אפשרות · Starting from: / החל מ: · Out of stock / אזל מהמלאי · "This combination is not available" / הצירוף הזה אינו זמין · Quantity / כמות.

**Front-facing — what the shopper sees**
- **Shop/archive cards:** a compact picker (`.shopos-shop-pick`) replaces WC's "Select options" — one row per attribute. Colour attrs render as round colour chips (near-white gets an `is-light` outline), image attrs as thumbnail tiles, else text pills. Overflow past *Max visible* collapses behind a **"+N"** reveal pill.
- Each row can show a label ("Color:") + a live "selected value" line (both hideable). An optional "Starting from: ₪X" price line swaps to the chosen variation's exact price on selection.
- Picking a full variation enables **Add to cart** and adds via AJAX; feedback is a fixed body-level **toast stack**, so card geometry never shifts. With `card_image_swap` on, the card image swaps to the variation image (`src` + `srcset` + `sizes`).
- **Out-of-stock preview:** OOS swatches greyed + struck through (`is-out-of-stock`); no-matching-variation combinations get `is-unavailable` (dimmed ~0.35 + diagonal `::after` line). Full variation JSON always embedded so OOS greying stays correct past WC's 30-variation AJAX threshold.
- **"Name & price only" mode:** picker + add-to-cart removed from cards — only name + price show, click through to the PDP to buy.
- **PDP buy box** (`.shopos-buy-box`): large colour swatches + size/label pills + a qty stepper, **Add to cart** + **Buy now**. Colour/image swatches show a hover **tooltip**. A hidden native `<select>` drives WC's own `variations.js`, so price/availability behave exactly like core.
- **Mobile:** a sticky bottom bar (`.shopos-sticky-bar`) with live price + primary CTA slides in once the buy box scrolls out of view. RTL/LTR-aware (`dir` from `is_rtl()`), Hebrew-first.

### CheapestDefaultVariation — `src/Modules/CheapestDefaultVariation/`
**Purpose:** Auto-selects the lowest-priced in-stock variation of every variable product as the default, so "Add to cart" is active on load without the shopper picking options.
**Status:** Governed by the module toggle (no separate flag). Registers two WC `*_get_default_attributes` filters — **no front-end CSS/JS**.
**Where to configure:** ShopOS → Cheapest Default Variation (`shopos-cheapest_default_variation`).

**Backend — Settings**

| Setting | Control | Options | Default |
|---|---|---|---|
| Respect manual defaults ("Leave defaults set in the product editor alone") | checkbox | on = editor default wins over auto-selection | on |
| Apply on product pages only ("Skip on shop/archive/loop") | checkbox | on = loop swatches render with nothing pre-selected; PDP still auto-selects | on |
| Default selection strategy | select | `cheapest` ("Cheapest in-stock variation") · `first_in_stock` ("First in-stock variation, WC order") | `cheapest` |

*Strategy resolves: setting → per-product meta `_shopos_cheapest_variation_strategy` → `shopos_core/cheapest_variation/strategy` filter; out-of-enum logs a warning and falls back.*

**Front-facing — what the shopper sees**
- On a variable product's **buy box at load**, an option is **already selected** (cheapest in-stock/purchasable by WC `display_price`, so sale prices count — or the first purchasable in-stock under `first_in_stock`).
- The **price line shows that variation's price** immediately (not the "$X–$Y from" range) and **"Add to cart" is enabled right away** — no "please select options" gate.
- With **Apply on product pages only** on (default), loop cards show **no** pre-selection (swatches render blank). With **Respect manual defaults** on (default), any product with an editor-set default is left untouched.
- Purely a server-side default-attributes filter — no markup/classes/assets of its own; WC's `add-to-cart-variation.js` reads the resolved default, so the visual result inherits whatever the theme / VariationSwatches draws.

### RestockNotify — `src/Modules/RestockNotify/`
**Purpose:** Hebrew-first "notify me when back in stock" — shoppers subscribe on OOS products and get emailed the moment stock returns, backed by a custom `{prefix}shopos_restock_subscribers` table.
**Status:** Default ON module. The WP privacy exporter/eraser is registered **unconditionally** (even when disabled), so persisted subscriber PII stays covered.
**Where to configure:** its **own** top-level menu **"Restock Notify" / התראות מלאי** (`restock-notify`) — Dashboard (waitlist stats + top-demanded + manual "send now") / Subscribers (searchable, bulk delete, CSV export) / Email Templates / Settings. *(Does not use Settings_Hub.)* Module enable toggle is on the ShopOS dashboard.

**Backend — Settings** *(18 seeded `shopos_restock_*` keys across Settings + Email Templates)*

| Setting | Control | Default |
|---|---|---|
| Auto-inject form on OOS product pages (`auto_inject`) | toggle | on (off → place with `[restock_notify]`) |
| Send confirmation email on subscribe (`enable_confirmation`) | toggle | on |
| Show GDPR consent checkbox (`enable_gdpr`) | toggle | off |
| From name / From email (`from_name` / `from_email`) | text / email | blank (fall back to site name / admin email) |
| Form + email copy (heading, description, button, success, duplicate, GDPR, subjects, bodies, CTA) | text / textarea | per-locale (see below) |

Editable copy (en_US default; `he_IL.php` supplies Hebrew; never overwritten on re-seed): `form_heading` "Notify me when back in stock" · `form_description` · `form_button_text` "Subscribe" · `form_success_message` · `form_duplicate_message` · `gdpr_text` · `confirm_subject`/`confirm_heading`/`confirm_body` · `notify_subject` "Good news - {product_name} is back in stock!" / `notify_heading` "It's back!" / `notify_body` / `notify_button_text` "Buy now". Placeholders: `{product_name}` `{customer_name}` `{product_url}` `{shop_url}` `{site_name}` `{unsubscribe_url}`.

**Front-facing — what the shopper sees**
- On an OOS product, a rounded white card (`.shopos-restock-form-card`) with a **bell icon in a black circle**, heading + description, side-by-side **name** and **email** fields, optional GDPR checkbox, and a black submit button. Injected four ways (WC hooks / `woocommerce_get_stock_html` filter / a guaranteed `wp_footer` fallback JS relocates next to the price / the shortcode).
- **Variable products:** the form stays hidden and slides down only when the shopper picks a variation that is *truly* OOS (a 6-case deep stock check, not WC's `is_in_stock`); slides back up on an in-stock pick; shows immediately if every variation is OOS.
- **Success:** fields + description slide up, a check-circle icon fades in with the success message; a duplicate shows the "already on the waiting list" message in the same style. Inline red errors cover invalid email / missing consent / network; a hidden honeypot blocks bots.
- **Mobile (≤480px):** inputs stack, padding tightens. The card forces `direction:rtl; text-align:right` (Hebrew-first), copy follows site locale.
- **The email** (`Email::build_html`): a black-circle bell header, heading, "Hi {name}," greeting, body with product name, a product thumbnail, and (back-in-stock only) a black **Buy now** CTA + footer with site name + unsubscribe link. The shell is bilingual — `<html lang>`/`dir` + inline `direction`/`text-align` derive from site locale (RTL on `he_IL`, LTR on English).

## 3.3 Customer Account

### MyAccount — `src/Modules/MyAccount/`
**Purpose:** A purely visual editorial restyle of the classic `[woocommerce_my_account]` page — sidebar layout, serif headings, mono eyebrows, hairline tables, status pills. **No markup, endpoint, or JS changes.**
**Status:** Default ON module. Enqueues one stylesheet (`my-account.css`) only where `is_account_page()`.
**Where to configure:** **Nothing to configure** — `settings_schema()` is empty, no admin page, no Labels editor. Only the enable/disable checkbox on the ShopOS dashboard.

**Front-facing — what the shopper sees**
- The whole account area (scoped `body.woocommerce-account`) becomes a two-column grid, max-width 1180px: a **264px sticky sidebar nav** (hairline divider on the inline edge) beside the content canvas. It styles WC's **real endpoints only** — no invented tiles/tracking-timeline/favorites.
- **Dashboard:** the first paragraph is promoted to a large **serif display greeting** (`--fma-display`, up to ~44px); the rest is muted body.
- **Orders / Downloads / Payment tables:** hairline-bordered, rounded, **mono uppercase column headers**, tabular-nums, subtle striped header. Order **status** → a hairline pill with a small dot. Row actions → mono-uppercase **ghost buttons**. Empty state = an accented info callout.
- **Addresses & View-Order:** billing/shipping reflow into a responsive card grid (`auto-fit minmax(280px,1fr)`), each a rounded bordered card with a **mono uppercase eyebrow title** + an "Edit" pill on the trailing edge.
- **Forms** (edit account/address, login, lost password): mono uppercase labels, full-width hairline inputs with an ink-colored focus border (no glow), two-up first/last-name rows.
- Resolves through `--shopos-ui-*` tokens (system-font fallbacks), so it inherits the store palette; RTL-aware via logical properties. **Mobile:** ≤880px the sidebar becomes a horizontally-scrolling pill row with an edge-fade mask; ≤640px tables restack as per-row cards.

## 3.4 Merchandising & Ops

*(Backend/ops modules — little or no shopper-facing UI; the "front-facing" surface is the artifact they produce.)*

### ProductFeed — `src/Modules/ProductFeed/`
**Purpose:** Generates a gzipped XML feed of every product (variations, stock, pricing, attributes), rebuilt hourly and within ~30s of any stock/price change, served at `/product-feed`.
**Status:** Default ON module; requires WooCommerce.
**Where to configure:** ShopOS → Product Feed (`shopos-product_feed`).

**Backend — Settings**

| Setting | Control | Default |
|---|---|---|
| Instant updates (`instant_update`) — rebuild within ~30s of a stock/price change | checkbox | on |
| Hourly fallback (`hourly_fallback`) — full regeneration every hour (safety net) | checkbox | on |

The module page also shows a read-only **Feed status** panel: Feed URL (linked when the file exists), Last generated, File size (gzipped), Next hourly run, Instant rebuild queued, plus a nonce-protected **"Generate now"** button.

**Front-facing — what the shopper sees:** *Nothing* — pure ops. The artifact is a gzipped XML doc at `uploads/shopos-product-feed/products.xml.gz` served via a `/product-feed` rewrite (`application/xml`, `X-Robots-Tag: noindex`, `Cache-Control: max-age=300`, `X-Feed-Generated` header). Generation pages products in batches of 100 under an exclusive lock, promoting a `.tmp` atomically so the last good feed keeps serving on failure. Each `<product>` carries sku/name/url/status/description/dates/dimensions/tax/categories/tags/images/attributes; simple products add stock + price fields; variable products emit price/regular min-max, total_stock, any_in_stock, and a `<variations>` list. A `shopos_core/product_feed/after_generate` action lets integrations ping Google Merchant Center / Facebook Catalog after each build.

### VariableStockFix — `src/Modules/VariableStockFix/`
**Purpose:** When every visible variation of a variable product is OOS, unchecks the parent's "Manage stock" and clears the parent qty so WC's native "Hide out of stock items" hides the product.
**Status:** Default ON module; requires WooCommerce.
**Where to configure:** ShopOS → Variable Stock Fix (`shopos-variable_stock_fix`).

**Backend — Settings**

| Setting | Control | Default |
|---|---|---|
| Daily audit (`run_daily_audit`) — once/day scan of products modified in the last 48h, fix matches | checkbox | on |

The module page also has a **Bulk audit** tool: a "Dry run (report only)" checkbox (on by default), Start / Stop buttons, a progress bar, a live counts line, and a scrollable log `<pre>` — two nonce-guarded AJAX endpoints paging all variable products in batches of 50. Live mode confirms before modifying.

**Front-facing — what the shopper sees:** *No dedicated UI* — a backend correction module. Its visible effect is **indirect**: fully-OOS variable products stop appearing on shop/category grids (via Woo's own "Hide out of stock items") instead of showing a dead, unbuyable card. Corrections fire on admin save, REST save, variation stock changes (debounced ~30s), the daily cron audit, and the bulk tool. A `shopos_core/variable_stock_fix/should_check` filter allows per-product opt-out. No storefront CSS/JS shipped.

## 3.5 Experience & Platform

### PageTransitions — `src/Modules/PageTransitions/`
**Purpose:** Smooths the storefront's full-page **reload** navigations (ShopFilters uses reload transport) with a loading overlay on filter/search/pagination interactions + a cross-document cross-fade in supporting browsers.
**Status:** **Default OFF** — absent from the seeded module set (existing installs never seeded it); the Dashboard toggle is the switch. Declares **no dependencies**, so it runs without WooCommerce.
**Where to configure:** ShopOS → Page Transitions (`shopos-page_transitions`) — once enabled.

**Backend — Settings**

| Setting | Control | Default |
|---|---|---|
| Loading overlay text (`loading_label`) | text | blank → locale-aware default (He `טוען תוצאות…`, else `Loading…`) |

*One text field — no overlay color/duration or separate on/off (the module toggle is the only switch).*

**Front-facing — what the shopper sees** *(two composable layers, front-end only)*
- **Loading overlay (JS+CSS).** On a qualifying navigation, `page-transitions.js` appends a fixed full-viewport scrim (`.shopos-pt-overlay`, `rgba(15,18,26,.35)` + `backdrop-filter: blur(1.5px)`, `z-index: var(--shopos-ui-z-max,100000)`) that fades in over two rAFs, centering a rounded white card (`.shopos-pt-box`) with a spinning ring (`.shopos-pt-spinner`) + the localized label. **Triggers:** pagination clicks (ShopOS `.cs-pagination`, WooCommerce/Elementor/block/`page-numbers`, ShopFilters panel links `.shopos-sf a`), product-search submits (`form.shopos-search-form, form[role="search"]`), and any caller of `window.ShopOSPageTransitions.show()` (ShopFilters' `navigate()` calls it as a soft dependency). Only plain same-origin left-clicks trigger it. A `pageshow` bfcache restore + an 8s timeout tear the overlay down so an aborted navigation can't strand a dead scrim.
- **Cross-document fade (pure CSS).** `@view-transition { navigation: auto }` cross-fades old→new (0.18s) on same-origin navigations (Chrome/Edge/Safari; Firefox ignores). To avoid landing on a half-rendered white frame, the module prints a render-blocking `<link rel="expect" href="#shopos-pt-ready" blocking="render">` in `<head>` + a matching end-of-body `<div id="shopos-pt-ready" hidden>` marker, so first paint waits for real content.
- **Reduced motion:** `@view-transition { navigation: none }` (fade off) + a frozen static ring, but keeps the overlay (it's feedback, not decoration). **Back/forward:** `pageswap`/`pagereveal` call `skipTransition()` on `traverse`/`back_forward` so the fade doesn't freeze while scroll/grid state restores — only fresh navigations animate. Assets: `page-transitions.css/js`.

---

## 4. Design token reference (`--shopos-ui-*`)

The theme (`shopos-theme/assets/css/shopos-tokens.css`) exposes one token layer every module reads. Change a value here and it propagates suite-wide — never hardcode what a token expresses.

| Group | Tokens |
|---|---|
| **Color** | `--shopos-ui-color-ink` · `-ink-soft` · `-ink-muted` · `-paper` · `-paper-soft` · `-paper-dim` · `-hairline` · `-accent` (+ `-soft`/`-text`) · `-success`/`-danger`/`-warning`/`-info` (+ `-soft`). Raw palette under `--shopos-ui-palette-*` (ink, sand, gold, green, red, amber, info…). |
| **Type** | `--shopos-ui-font-display` · `-body` · `-mono`; leading `--shopos-ui-leading-tight/snug/base/loose`; button `--shopos-ui-button-font-weight` / `-tracking`. |
| **Card** | `--shopos-ui-card-bg` · `-border` · `-border-hover` · `-radius` · `-aspect` · `-gap` · `-shadow` · `-shadow-hover` · `-hover-shift`. |
| **Button** | `--shopos-ui-button-height` (+ `-sm`) · `-radius` · `-padding-x`. |
| **Input** | `--shopos-ui-input-bg` · `-border` · `-border-hover` · `-focus-ring` · `-radius` · `-height` (+ `-sm`) · `-padding-x`. |
| **Badge** | `--shopos-ui-badge-font` · `-height` · `-padding` · `-radius`. |
| **Motion** | `--shopos-ui-motion-instant/fast/base/slow` · easing `--shopos-ui-ease` / `-in` / `-out` / `-bounce` (all collapse under reduced-motion). |
| **Layout** | `--shopos-ui-container-max` · `-container-pad` · radius `--shopos-ui-radius-md/lg/pill`. |
| **Z-index ladder** | `--shopos-ui-z-*` (…`-modal`, `-toast`, `-max`) — the shared stacking order for swatch sticky bars, filter drawers, QuickView, and the PageTransitions overlay. |

---

## 5. Planned modules (design targets, not yet built)

From [`Modules.md`](Modules.md) / [`roadmap.md`](roadmap.md) — the gap to a complete storefront OS. These have **no code yet**; each needs its own decisions-doc addendum + owner approval before build. Front-facing intent, so the design language is ready when they land:

- **Side Cart** *(Cart)* — slide-out drawer: line items w/ images/qty, remove-with-undo, live subtotal/coupon/total, free-shipping progress meter, recommended products, quick-checkout. Reuses the QuickView/ShopFilters drawer primitives.
- **Checkout** *(Checkout)* — WC checkout skin: trimmed fields, Israeli city/settlement autocomplete, real-time RTL-correct validation, phone-first, express-pay placement, trust elements at the payment step.
- **Product Reviews** *(Trust)* — restyle + own Woo's native reviews: rating summary, verified-purchase badge, customer photos, helpful voting, store replies, live submission form.
- **Advanced Add-to-Cart** *(consolidation)* — a unified rich buy button composing VariationSwatches + CheapestDefaultVariation + RestockNotify + ProductPage urgency; adds buy-now, AJAX add-to-cart, sticky mobile buy bar.
- **Bundle Deals · Fortune Wheel · Product Badges · Flash Sale Banner** *(Sales/Promotion — light modules; must respect the no-discount-bin, semantic-color-only rules)*.
- **Bulk Price Editor** *(admin ops)* — % or fixed change, smart rounding, targeting, preview + rollback, batch processing.
- **Custom Email Templates** *(retention)* — branded per-status WC emails; generalizes RestockNotify's bilingual email infra.

> The discipline list (things we deliberately **don't** build): no page builder, no headless/REST platform, no marketplace, no subscriptions engine until a client pays, no AI/semantic search, no payment/shipping/tax rebuilding, no dark mode, no white-labeling. If it's not on the funnel scorecard, it waits.
