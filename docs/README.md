# ShopOS — Docs Index

Docs for the ShopOS plugin suite (`shopos-core`, `shopos-theme`, `shopos-digital`).
Active docs live at the top level; completed/superseded material lives in [`archive/`](archive/).

## Planning — start here

| Doc | What it is |
|---|---|
| [roadmap.md](roadmap.md) | **The hub.** Current state, what's shipped, what's left, what's next. |
| [Modules.md](Modules.md) | Module catalog — what's built, what's planned, and the build order. |
| [TODO.md](TODO.md) | The actionable work queue (build modules, flip the ShopOS Line, audit follow-ups, ops). |
| [BUGS.md](BUGS.md) | Open bugs only. Fixed bugs live in the CHANGELOGs. |

## Operating rules

| Doc | What it is |
|---|---|
| [CLAUDE.md](CLAUDE.md) | The operational brain — coding rules, hard rules, per-PR contract, and the live **infrastructure state** (current versions, test counts). Read first. |
| [AGENTS.md](AGENTS.md) | Binding release policy (version bump + CHANGELOG + zip rebuild + preflight). |
| [decisions-2026-04-28.md](decisions-2026-04-28.md) | Strategic decisions (§4.x / §5 / §6 / §8 / §9 / §10 / §11). Referenced from code — canonical. |
| [pr-template.md](pr-template.md) | Required structure for every PR description. |
| [feature-flags.md](feature-flags.md) | The `shopos_core_<module>_<feature>_enabled` naming convention and defaults. |

## Product & design

| Doc | What it is |
|---|---|
| [PRODUCT.md](PRODUCT.md) | Product framing — who it's for and what it's for. |
| [DESIGN.md](DESIGN.md) | The design system — `--shopos-ui-*` tokens (color, typography, spacing, motion). |
| [CLAUDE-DESIGN.md](CLAUDE-DESIGN.md) | Per-module design & screens reference. Companion to DESIGN.md and Modules.md. |

## Other

- [`prototypes/`](prototypes/) — standalone UI prototypes (My Account screen). Not shipped code.
- [`archive/`](archive/) — completed wave logs, executed audits/plans, superseded reviews.

### Archive contents

| Doc | Why archived |
|---|---|
| [archive/wave-log-0-9.md](archive/wave-log-0-9.md) | The Waves 0–9 execution log (frozen at core 1.24.x). |
| [archive/expansion-roadmap-2026-07.md](archive/expansion-roadmap-2026-07.md) | Expansion Phases 0–4 plan — executed; forward view now in roadmap.md. |
| [archive/design-control-audit-2026-07-19.md](archive/design-control-audit-2026-07-19.md) | The pivot's per-module token/behavior control audit — PR-01…13 shipped (1.45.x). |
| [archive/audit-2026-07-03.md](archive/audit-2026-07-03.md) | Maturity audit (security/perf/hardcoding/loose-ends) — remediated through 1.44.x. |
| [archive/remediation-plan-2026-07-03.md](archive/remediation-plan-2026-07-03.md) | Sequenced remediation for the audit above — shipped. |
| [archive/optional-future-features.md](archive/optional-future-features.md) | Waves 9–14 strategy proposal — the ShopOS Line was approved from it; rest superseded by the pivot. |
| [archive/audit-2026-04-28.md](archive/audit-2026-04-28.md) | Feature-gap audit at v1.10.12; open questions resolved in decisions-2026-04-28.md. |
| [archive/AUDIT-2026-04.md](archive/AUDIT-2026-04.md) | April 2026 plugin audit — findings resolved by 1.9.x. |
| [archive/REVIEW.md](archive/REVIEW.md) | April 2026 full codebase review. |
| [archive/grid-parity-audit-2026-06-11.md](archive/grid-parity-audit-2026-06-11.md) | Wave 7.1 ProductSlider grid-parity audit — findings shipped. |
| [archive/wave-2.2-master-plan.md](archive/wave-2.2-master-plan.md) | Wave 2.2 (VariationSwatches migration) — shipped. |
| [archive/wave-3.1-master-plan.md](archive/wave-3.1-master-plan.md) | Wave 3.1 (InfiniteScroll trigger modes) — shipped. |
