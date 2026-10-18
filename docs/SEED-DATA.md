# Synthetic product fixtures

Canonical fixture data: `wp-content/plugins/bepduc-core/config/demo-products.json`. **The fixture manifest is not the real supplier product catalogue.**

## Purpose and boundaries

- 24 clearly labelled `[DEMO]` products over all 9 catalogue categories, 23 simple + 1 variable product with 2 variations.
- WooCommerce-native fields for SKU, categories, brand, global attributes, pricing, sale pricing, stock, visibility and featured status.
- Test cases include long title, on-sale, out of stock, hidden catalogue visibility, and products with **no image**. No vendor photography or dummy remote image links are distributed. Image-present/gallery test cases will follow only after suitable project-owned/licensed assets are available.
- All brand names and product specifications are invented demo values; **never represent them as actual inventory/offers**.
- Fixture SKUs use reserved prefix `BD-DEMO-` and both parent and variation products are tagged with protected metadata `_bd_demo_fixture` and `_bd_demo_fixture_hash`. Existing, unmanaged SKUs are never overwritten.
- Real WooCommerce products, orders, clients and premium plugins are outside this seed tool. A production environment can never invoke the seed writer, even with an override.

## Local installation

With WordPress, WooCommerce and `bepduc-core` installed and active, and `WP_ENVIRONMENT_TYPE=local` or `development` in the WordPress runtime:

```bash
wp bepduc catalog sync
wp bepduc catalog verify
wp bepduc seed products
wp bepduc seed verify
```

`catalog sync` should finish in a **previous WP-CLI invocation**, as it registers global `pa_*` taxonomies for the following request. Running `seed products` again must not create duplicate products/variations, nor silently update edited fixtures. Re-run of `verify` is read-only.

On a staging environment only, adding `--allow-staging` to `wp bepduc seed products` permits staging fixture setup after explicit review. Production is categorically rejected.

**Set WooCommerce shop currency to VND** before reviewing sample prices. The manifest prices are arbitrary test numbers in VND; the seed command never changes production-level WooCommerce settings.

## Changes and reset policy

The seed command only creates missing tagged products and canonical taxonomy terms; it is **not** a destructive full product synchronizer. If a fixture SKU already exists but lacks the marker, has the wrong type, has a changed hash or has edited inventory/price/attributes, the command fails. This is intentional, to avoid replacing editors' work.

Fixture revisions that must change preexisting demo records require a dedicated, reviewed local-only reset/migration, **not** a hidden `--force` flag. No automatic deletion command is provided.

Native product brands live in `product_brand`; the seed creates only two explicitly marked fictional brand terms. Canonical attribute terms use Woo global `pa_*` taxonomies and the declared term slugs in the fixture manifest. The manifest does not modify `config/catalog.json` or register new taxonomy/attributes.

## Production supplier import is separate

This seed command is **not** a supplier CSV/ERP importer. When real product data is approved, implement an importer via WooCommerce CSV/REST/CRUD with its own source format, SKU ownership rules, dry-run, validation, error reporting, media licensing, update behavior and rollback plan. Do not use fictional seed rows or generic demo brands in a customer-facing production catalogue.
