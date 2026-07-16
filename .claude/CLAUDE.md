<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->

## Skills

Project skills live in `.claude/skills/` — the topic → skill map is in `AGENTS.md` ("Skills"). Before WordPress/WooCommerce/PHP/frontend work, invoke the matching skill: `wordpress-router` classifies WP tasks; `wp-project-triage` produces the repo report other `wp-*` skills expect as input.

**Elementor only — no Gutenberg.** Never write block-editor code (custom blocks, block.json, theme.json workflows, block-based cart/checkout).
