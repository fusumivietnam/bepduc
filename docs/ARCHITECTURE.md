# Architecture

## Platform

- WordPress for editorial/content management.
- WooCommerce for catalogue, pricing, inventory, cart, checkout, orders and commerce APIs.
- Custom block theme `bepduc` for presentation.
- Custom plugin `bepduc-core` for project-specific domain logic.

## Source-of-truth boundaries

- GitHub: code, manifests, migrations, technical decisions and project history.
- WordPress/WooCommerce database: runtime content and commerce data.
- Cloudflare R2: private premium-plugin artifacts.
- Infrastructure secret store/environment: credentials and license keys.

## Dependency policy

Order of preference:

1. WordPress core capability.
2. WooCommerce core capability.
3. Small project code in `bepduc-core`.
4. Third-party plugin only when it provides clear leverage.

Third-party plugins must be declared and reproducible. Premium binaries never live in this public repository.

## Deployment principle

Code and dependency changes flow through GitHub. Production is not a configuration authoring environment. Database changes that affect structure or stored domain data should be reproducible via code/migrations rather than undocumented manual edits.
