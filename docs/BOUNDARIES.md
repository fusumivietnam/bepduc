# System Boundaries

Use this document to decide where a new feature belongs.

| Boundary | Owns |
| --- | --- |
| WordPress | Editorial content and publishing |
| WooCommerce | Products, prices, stock, cart, checkout, orders, coupons, reviews and commerce APIs |
| `bepduc` theme | Presentation, templates, patterns, design tokens and frontend interactions |
| `bepduc-core` | Bepduc-specific domain logic, metadata, migrations, advisor/combo logic and integrations |
| GitHub | Code, manifests, technical decisions and work history |
| WordPress DB | Runtime content and business data |
| Cloudflare R2 | Licensed premium-plugin artifacts |
| Infrastructure | Secrets, backups, runtime configuration and deployment credentials |

## Rule

Do not duplicate ownership. For example, do not implement a second inventory system in `bepduc-core`, and do not put advisor business rules in the theme.
