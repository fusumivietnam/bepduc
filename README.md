# bepduc.com

Repository for the bepduc.com WordPress + WooCommerce platform.

## Architecture

- WordPress owns content.
- WooCommerce owns commerce.
- `bepduc` custom block theme owns presentation.
- `bepduc-core` owns project-specific domain logic.
- GitHub is the source of truth for code, dependency manifests, technical decisions and project history.
- Runtime/business data stays in WordPress/WooCommerce databases and infrastructure storage.

## Application source

- Theme: `wp-content/themes/bepduc/`
- Domain plugin: `wp-content/plugins/bepduc-core/`
- Premium dependency manifest: `config/premium-plugins.json`

WordPress core and third-party plugin/theme sources are intentionally not committed.

See `docs/DEVELOPMENT.md` for local/staging setup assumptions.

## Premium plugins

Premium plugin binaries are **not committed to this public repository**. Approved artifacts are stored in a private Cloudflare R2 bucket and referenced by version + SHA256 in `config/premium-plugins.json`.

See `docs/PREMIUM-PLUGINS.md`.

## Working rules

Read `AGENTS.md` before making changes. Work progress belongs in GitHub Issues / Pull Requests / Projects; Markdown documents contain durable project knowledge, not hand-maintained progress status.
