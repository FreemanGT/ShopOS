<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->

## Skills

**At the start of every task, check the project skills and invoke the best-fitting one(s)** — the topic → skill map is in `AGENTS.md` ("Skills"). Prefer the most specific skill; combine when a task spans areas (e.g. `wordpress-elementor-dev` + `wp-security-review` for a widget handling user input). `wordpress-router` classifies ambiguous WP tasks; `wp-project-triage` produces the repo report other `wp-*` skills expect as input. Skipping the skill check is not an option for WordPress/WooCommerce/Elementor/PHP/frontend work.

**Elementor only — no Gutenberg.** Never write block-editor code (custom blocks, block.json, theme.json workflows, block-based cart/checkout).
