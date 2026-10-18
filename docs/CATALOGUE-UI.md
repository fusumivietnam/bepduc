# Catalogue UI and Product Card

The catalogue is rendered with **WooCommerce Product Collection blocks** and a single reusable theme pattern; there is no second PHP product query, new product CPT or copied WooCommerce PHP template.

## Source files

- `wp-content/themes/bepduc/templates/archive-product.html`: native product archive.
- `wp-content/themes/bepduc/templates/page-shop.html`: fallback for a WooCommerce shop configured with page slug `shop` (some WP/Woo configurations route it as a Page).
- `wp-content/themes/bepduc/templates/taxonomy-product_cat.html`: category archives, inheriting Woo taxonomy queries.
- `wp-content/themes/bepduc/templates/taxonomy-product_brand.html`: native brand archives.
- `wp-content/themes/bepduc/patterns/catalogue-results.php`: shared price/stock/brand filtering, native sorting, Product Collection query, pagination and empty state.
- `wp-content/themes/bepduc/patterns/product-card.php`: one Product Card composition reused through nested `wp:pattern` inside the shared results pattern.
- `wp-content/themes/bepduc/inc/product-brand.php`: presentation-only dynamic block reading native `product_brand` terms from the product context; no separate brand storage.
- `wp-content/themes/bepduc/inc/catalogue-query.php`: narrow `render_block_data` adjustment so Woo Product Collection uses a product query on Page-routed `shop`; taxonomy archives continue inheriting their native Woo queries.
- `wp-content/themes/bepduc/assets/css/catalogue.css`: responsive layout, tokens and accessible focus styles.

## Rendering contract

All price, sale badges, product images/placeholder, add-to-cart behavior and out-of-stock messages come from the installed WooCommerce blocks, not custom HTML with duplicated business rules.

The Product Collection inherits archive query/taxonomy context. The `shop` page fallback is intended for the conventional WooCommerce shop slug and should be verified against the actual configured shop page before launch. WooCommerce's Product Results Count and Catalog Sorting are rendered beside the collection. The sidebar uses **native Product Filters** for applied filters, price, availability and native brand taxonomy. Do not invent numeric `attributeId` references in static templates: configurable attribute filters will be added when they can be bound reliably to deployed attribute IDs.

The CSS targets the WooCommerce Product Collection's list children, uses a responsive 3/2/2-column display and respects reduced-motion and keyboard focus. Actual media may be absent in the demo fixture; WooCommerce's normal missing-image placeholder is used.

## Test locally

After WooCommerce is installed, and sample products have been seeded in a non-production WordPress environment:

```bash
wp theme activate bepduc
wp plugin activate woocommerce bepduc-core
wp bepduc catalog sync
wp bepduc seed products
```

Open the configured WooCommerce shop page or a product category page. The Site Editor can still modify block templates; those changes live in the WordPress DB and must be reviewed/exported before considering them the repository's source of truth.

CI starts a disposable MySQL + WordPress + WooCommerce runtime to verify the actual template and product brand block rendering. It does not substitute for visual QA across browser sizes.
