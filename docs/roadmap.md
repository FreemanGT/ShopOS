# ShopOS — Roadmap

**Last updated:** 2026-07-19 · **Owner:** Yiftach

The single forward-looking plan for the suite. History lives in the CHANGELOGs and
[`archive/`](archive/); this file is **status + what's shipped + what's left + what's next**.

- **What's built, module by module** → [Modules.md](Modules.md)
- **Actionable work queue** → [TODO.md](TODO.md)
- **Open bugs** → [BUGS.md](BUGS.md)
- **Per-release detail** → package `CHANGELOG.md` files + [CLAUDE.md](CLAUDE.md) "Current infrastructure state"
- **Strategic decisions (canonical, code-referenced)** → [decisions-2026-04-28.md](decisions-2026-04-28.md)

---

## Current state (2026-07-19)

| Package | Version |
|---|---|
| shopos-core | 1.47.0 |
| shopos-theme | 1.16.0 |
| shopos-digital | 1.7.8 |

- **15 storefront modules** live (see [Modules.md](Modules.md)). Suite tests green on php@8.3.
- The committed backlog is **drained**: Waves 0–9, Expansion Phases 0–4, the 2026-07-03
  maturity audit + remediation, and the 2026-07-19 design-control audit are all shipped.
- **Direction pivot (2026-07-19):** the owner designs everything; ShopOS ships **skin-light,
  token-driven, fully toggleable** modules — not opinionated looks. Every brand-visible value
  routes through `--shopos-ui-*` tokens or a Design-panel / Settings-Hub control. The control
  audit that drove this is [archive/design-control-audit-2026-07-19.md](archive/design-control-audit-2026-07-19.md).

## What's shipped (epic-level)

Detail is in the CHANGELOGs and [archive/wave-log-0-9.md](archive/wave-log-0-9.md); the arc:

- **Waves 0–9** — foundations (flags, migrations, settings hub, snapshot/baseline harness) →
  the storefront modules: VariationSwatches, RestockNotify, InfiniteScroll, sliders,
  ShopFilters, QuickView, HoverSwap, Search, ProductPage, PageTransitions.
- **Expansion Phases 0–3** — shared `Widget_Base` + `shopos` Elementor category, settings
  field API, `Labels_Base`, per-module Elementor widgets, `Core/Cache`, Design panel (§9),
  `wp shopos` CLI, perf-budget tooling + un-masked digital CI, Store Blueprint (§10),
  dashboard search.
- **Expansion Phase 4 — the ShopOS Line (§11):** theme-owned PDP + PLP + self-hosted fonts,
  each behind a **permanent, default-OFF** plugin-side kill-switch. Built; **not flipped on** —
  gated by the §11-B staging preconditions (see What's left).
- **Maturity audit + remediation (2026-07-03)** — PRs 1–21 + B-1/B-3 shipped through 1.44.x.
- **Design-control audit (2026-07-19)** — PR-01…PR-13 (token vocabulary, per-module token
  coverage, owner-control settings bundle) shipped through 1.45.x.

## What's left

### 1. Unbuilt modules — the real forward roadmap
Eight net-new + one consolidation, to close the gap to a complete storefront OS (~22 modules).
Full spec + build order in [Modules.md](Modules.md#build-roadmap-to-20-modules). Lead path is
**Side Cart → Checkout** (the revenue path). Bundle Deals shipped 1.46.0 (2026-07-19, default OFF).

Side Cart · Checkout · Product Reviews · Product Badges · Flash Sale Banner ·
Fortune Wheel · Bulk Price Editor · Custom Email Templates · Advanced Add-to-Cart *(consolidation)*.

### 2. ShopOS Line — flip the built flags, then §11-B
- **Flip gates for the three built-but-OFF theme flags** (`fonts_selfhost`, `template_pdp`,
  `template_plp`): staging render-diff identity with Elementor Pro, kit font parity, perf-budget,
  RTL pass, owner screenshots, then ≥30 days flag-on across a theme release. Details in [TODO.md](TODO.md).
- **§11-B deferred surfaces** (theme-owned classic PHP): ~~header/footer chrome~~ (**shipped
  1.47.0/1.16.0, flag `theme.template_chrome`**) · ~~cart~~ (**shipped 1.48.0/1.17.0, flag
  `theme.template_cart`**) · checkout · account · search-results template · transactional emails.
  Owner **overrode the §11-B calendar gate 2026-07-20** to start these (the 30-day-flag-on
  + store-#2 conditions are not met); building **one plan-first PR per surface**. The theme-CI-lane
  precondition **is genuinely satisfied** (`@group theme` in core's suite + named CI step — decisions
  §11-B Ruling 7.8). Checkout still needs the Ruling 9 tech-pin resolved first; emails stay Core-side.
  See decisions [§11 Ruling 2](decisions-2026-04-28.md).

### 3. Open audit follow-ups (B-5)
Two sweeps the 2026-07-03 audit deferred: the **uninstall created-vs-cleaned matrix** and the
**dead-code cross-check** (asset↔disk, options-written-never-read, CSS/JS orphans). In [TODO.md](TODO.md).

## Next up

Two independent tracks, owner's call which leads:
- **Repeatability:** stand up **store #2** (different vertical/locale, no Elementor Pro) — the
  permanent generalization test that also unlocks §11-B, plus flip the ShopOS Line flags on staging.
- **Revenue:** build **Side Cart**, then **Checkout** — the first surfaces ShopOS doesn't own
  today and where conversion is actually won.

Every module/wave still follows the [CLAUDE.md](CLAUDE.md) contract: pre-flight → plan →
owner approval → flag-gated (unless additive) → tests → ledger update in the same PR.
