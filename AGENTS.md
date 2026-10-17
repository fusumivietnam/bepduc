# Bepduc Agent & Contributor Rules

## Read first

1. `README.md`
2. `docs/ARCHITECTURE.md`
3. `docs/BOUNDARIES.md`
4. Relevant GitHub Issue / Pull Request

## Source of truth

- Code and dependency manifests: GitHub.
- Work status: GitHub Issues / Pull Requests / Projects / Milestones.
- Architecture knowledge: `/docs`.
- Runtime content, orders and customers: WordPress/WooCommerce database.
- Secrets and licensed binaries: infrastructure/private storage, never this public repository.

## Development rules

- Prefer WordPress and WooCommerce native capabilities before adding dependencies.
- Theme owns presentation only.
- Project-specific business/domain logic belongs in `bepduc-core`.
- Do not hard-code product/catalog data in templates.
- Do not install an undeclared plugin on production.
- Do not commit secrets, production data, uploads or premium plugin source/binaries.
- Reuse existing components and design tokens.
- Keep custom frontend JavaScript minimal.
- Every feature/fix should map to a GitHub Issue and Pull Request once the initial repository bootstrap is complete.

## Documentation rule

Update Markdown only when durable knowledge changes (architecture, schema, dependencies, operations, decisions). Do not maintain manual status/progress files.
