# WooCommerce Product Data Model

**Canonical schema:** wp-content/plugins/bepduc-core/config/catalog.json. Never duplicate its category/attribute inventory in other docs.

## Ownership

| Data | Owner |
| --- | --- |
| Products, SKU, descriptions, price/sales, gallery, inventory, variations, reviews | WooCommerce product API and database |
| Main categories | Native WooCommerce product_cat |
| Brands | Native WooCommerce product_brand; never register a competing brand taxonomy |
| Facetable/comparable shared specs | WooCommerce global pa_* attributes |
| Outer dimensions and shipping weight | Native WooCommerce weight/length/width/height fields |
| Expert verdict, pros/cons, exact installation cutout, utility clearances, diagrams | Future project bd_* metadata (ACF Local JSON) |
| Live product/brand/attribute terms and values | WordPress database, not Git |
| Category and attribute definitions | config/catalog.json in bepduc-core, versioned in Git |

WooCommerce owns products and product variations. Do not create a parallel product CPT or pa_brand.

## Schema specification

The manifest currently declares nine categories and twelve global attributes with stable ASCII slugs and Vietnamese labels. Attribute slugs do not include WooCommerce's automatic pa_ prefix. Attributes use select, menu_order, and disabled public attribute archives.

Do not pre-create arbitrary term values. An approved importer must map standardized value slugs (e.g. 600-mm for a nominal width), rather than generating duplicates such as 60cm, 600mm, 60 cm. Actual measurements needing numeric range filtering belong in typed product metadata rather than thousands of unique terms.

WooCommerce's dimensions describe product/shipping dimensions, **not** kitchen cutout/clearance requirements. Installation metadata will use bd_ fields from a separate issue; the ACF plugin is not required for this schema-only task.

## Explicit WP-CLI setup

After WooCommerce and bepduc-core are active:

    wp bepduc catalog verify
    wp bepduc catalog sync
    wp bepduc catalog verify

- verify is read-only and fails if any declared category/attribute is missing or drifted.
- sync adds only missing categories and global attributes after detecting existing drift. It does not modify existing category labels, attribute definitions, brand terms, product data, inventory, prices or orders.
- Repeated sync is idempotent. Newly created pa_* taxonomies may require the next PHP request to become registered before assigning terms.
- On production, sync requires --allow-production and must follow a reviewed PR and staging verification.
- Unrelated existing Woo categories or attributes are never deleted.

## Schema changes

1. Edit catalog.json on an Issue/PR branch; validate and review the diff.
2. Run sync and verify on staging. Production is a separate, explicitly approved execution.
3. Additive changes are safe; renames, deletions, reparenting, or datatype changes to populated fields require a dedicated reversible migration, not a silent manifest edit.
4. Supplier mappings, seed data, product/brand terms and real SKUs are separate implementation tasks. Never store customer/order/payment data in Git.

## Future product importer contract

Use WooCommerce CSV importer or REST APIs. Maintain deterministic SKU values, native product categories and brands, and canonical global attribute term slugs. Use Woo-native fields wherever possible; only project-specific expert and installation metadata belongs in bepduc-core.
