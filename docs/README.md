# ShopOS — Docs Index

The `/docs` folder for the ShopOS plugin suite (`shopos-core`, `shopos-theme`, `shopos-digital`).
Active, canonical documents live at the top level; completed and superseded documents live in [`archive/`](archive/).

## Start here

| Doc | What it is |
|---|---|
| [CLAUDE.md](CLAUDE.md) | The operational brain — coding rules, hard rules, per-PR contract, and the live **infrastructure state** (current versions, test counts). Read first. |
| [AGENTS.md](AGENTS.md) | Binding release policy for any agent working in the repo (version bump + CHANGELOG + zip rebuild + preflight). |
| [roadmap.md](roadmap.md) | The execution plan and the shipped-to-date log. The source of truth for what is done and what is next. |
| [decisions-2026-04-28.md](decisions-2026-04-28.md) | Strategic decisions (§4.x / §6 / §8) that resolve open questions so roadmap work can proceed. Referenced from code — canonical. |
| [pr-template.md](pr-template.md) | The required structure for every PR description. |
| [feature-flags.md](feature-flags.md) | The `shopos_core_<module>_<feature>_enabled` naming convention and defaults. |

## Product & design

| Doc | What it is |
|---|---|
| [PRODUCT.md](PRODUCT.md) | Product framing — who it's for and what it's for. |
| [Modules.md](Modules.md) | Module catalog: what is built, partial, and planned. |
| [DESIGN.md](DESIGN.md) | The design system — tokens (color, typography, spacing, motion). |
| [CLAUDE-DESIGN.md](CLAUDE-DESIGN.md) | Per-module design & screens reference (backend settings + front-facing UI). Companion to DESIGN.md and Modules.md. |

## Audits & forward planning

| Doc | What it is |
|---|---|
| [audit-2026-07-03.md](audit-2026-07-03.md) | Latest maturity audit — security, performance, hardcoding, loose ends. |
| [remediation-plan-2026-07-03.md](remediation-plan-2026-07-03.md) | The sequenced, per-PR remediation plan for the audit above (its companion). |
| [optional-future-features.md](optional-future-features.md) | Forward strategy audit — **proposal only, nothing committed**. |
| [expansion-roadmap-2026-07.md](expansion-roadmap-2026-07.md) | Sequenced next-phase plan (theme+templates, settings, widgets, mechanisms) — **proposal only**. Foundations → fan-out → own the buy path. |

## Other

- [`prototypes/`](prototypes/) — standalone UI prototypes (currently the My Account screen: HTML + JSX). Not shipped code.
- [`archive/`](archive/) — completed wave plans, superseded audits, and historical reviews. Kept for reference; not current.

### Archive contents

| Doc | Why archived |
|---|---|
| [archive/wave-2.2-master-plan.md](archive/wave-2.2-master-plan.md) | Wave 2.2 (VariationSwatches migration) — shipped. |
| [archive/wave-3.1-master-plan.md](archive/wave-3.1-master-plan.md) | Wave 3.1 (InfiniteScroll trigger modes) — shipped. |
| [archive/grid-parity-audit-2026-06-11.md](archive/grid-parity-audit-2026-06-11.md) | Wave 7.1 ProductSlider grid-parity audit — findings shipped. |
| [archive/audit-2026-04-28.md](archive/audit-2026-04-28.md) | Feature-gap audit at v1.10.12; its open questions were resolved in `decisions-2026-04-28.md`. Superseded in scope by the 2026-07-03 audit. |
| [archive/AUDIT-2026-04.md](archive/AUDIT-2026-04.md) | April 2026 plugin audit — all findings resolved by 1.9.x. |
| [archive/Audit.md](archive/Audit.md) | The audit prompt used to run the April review. |
| [archive/REVIEW.md](archive/REVIEW.md) | April 2026 full codebase review. |
