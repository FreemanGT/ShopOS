# ShopOS — Open Bugs

Known **open** defects only. Fixed bugs are recorded in the package `CHANGELOG.md` files, not here.
Add a row when you find one; remove it (with the fixing version) when it ships.

**Last reviewed:** 2026-07-19

---

## Open

| # | Severity | Where | Symptom | Notes |
|---|----------|-------|---------|-------|
| — | — | — | **No open code bugs of record.** | The last confirmed storefront bug (RestockNotify subscribe → 400: `frontend.js` posted `shopos_restock_subscribe` vs the registered `rsn_subscribe` handler) was fixed in PR #38. |

## Watch items (not bugs yet)

- **`fonts_selfhost` flag-ON leaves useless `<link rel=preconnect>` to `fonts.googleapis.com`
  + `fonts.gstatic.com`** — NOT an Elementor-kit leftover (the 2026-07-17 note mis-attributed it).
  Root cause verified 2026-07-20 in wp-env-with-Pro: **shopos-digital's `add_preconnect()`**
  (`fe_add_preconnect`, default ON) prints both google-font hosts at `wp_head:1`, unaware the
  theme self-hosts. **Fix = config, zero code:** enable digital's existing `fe_disable_elementor_gfonts`
  setting ("Disable Elementor Google Fonts Loading — only enable if you self-host fonts") at flip
  time; that both suppresses the gfonts and skips the google preconnect (verified: preconnects gone,
  `shopos-fonts.css` intact, only the legit `cdnjs` fontawesome preconnect remains). Low.
- **Deferred live-QA on recently-shipped storefront fixes** — several 1.24.x / 1.45.x CSS/JS
  changes carry "live-store QA pending" notes in the CHANGELOG. Not confirmed regressions;
  verify on the live store and clear.

## Ops (deployment, not a code bug)

- **Stranded core stores on 1.44.3–1.45.0** lost auto-update (unbooted updater). Fixed in 1.45.1,
  but affected stores need a **one-time manual re-upload** of core to re-enter the update channel.
  Tracked in [TODO.md](TODO.md).
