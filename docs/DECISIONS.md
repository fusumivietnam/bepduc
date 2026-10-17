# Architecture Decisions

Only durable decisions that are not obvious from code belong here.

## ADR-001 — GitHub work state, not manual status files

**Status:** Accepted

GitHub Issues, Pull Requests, Projects and Milestones represent work state. Do not maintain a handwritten `STATUS.md` or duplicate progress summaries in documentation.

## ADR-002 — Public repository, private secrets and artifacts

**Status:** Accepted

The main repository remains public. Secrets, customer/order data, production database dumps, license keys and premium plugin binaries must remain outside the repository.

## ADR-003 — Premium plugin artifacts in Cloudflare R2

**Status:** Accepted

Licensed premium plugin ZIP files are stored as immutable, versioned objects in a private R2 bucket. The repository pins the approved version, R2 object key and SHA256 checksum in `config/premium-plugins.json`.

## ADR-004 — Production does not auto-upgrade premium dependencies

**Status:** Accepted

A new premium plugin version is uploaded under a new R2 key, tested on staging, reviewed through a manifest change and then deployed. Production must not drift independently from the GitHub manifest.

## ADR-005 — WordPress/WooCommerce first

**Status:** Accepted

Use WordPress and WooCommerce native capabilities before adding a plugin or project-specific implementation. Presentation belongs to the theme; Bepduc-specific domain logic belongs to `bepduc-core`.
