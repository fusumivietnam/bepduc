# Product Detail (WooCommerce block theme)

Bepduc uses the native WooCommerce `single-product.html` template and blocks for gallery, title, price, ratings, variation selection, add-to-cart, product metadata, details and related products. The actual Woo product remains the source of truth. Product type and cart logic are not implemented in the theme.

## Theme files

- `templates/single-product.html`: two-column product hero, specification section, native Woo details and related products.
- `inc/product-detail.php`: presentation-only server-rendered specification and installation guidance blocks.
- `assets/css/product-detail.css`: responsive detail, focus-visible, typography and tables.
- `functions.php`: registers the detail block definitions and loads the stylesheet on product pages.

WooCommerce `product-image-gallery` is deliberately paired with `add-to-cart-form` to preserve traditional variable-product gallery behaviour. Changing either requires retesting variation image switching and cart interactions. Do not copy WooCommerce PHP templates into the theme.

## Specifications and installation

Specifications show actual visible Woo global `pa_*` terms and Woo-native physical dimensions/weight only. Blank fields are omitted. Shipping/product dimensions are **not** cutout dimensions.

The installation note is deliberately general and makes no promise about available installers or product-specific wiring/cabinet requirements. Product-specific installation documentation and `bd_*` metadata are separate scoped work, subject to approved source documentation.

## QA contract

Real Woo+WordPress CI checks block registration and renders the default simple and variable fixture detail URLs over HTTP. It verifies the presence of the add-to-cart markup, specifications and guidance. Browser QA must still cover variation selection, gallery interactions, stock messaging, mobile breakpoints, keyboard focus and accessibility before publishing to production.

Demo data has no licensed photos, so the WooCommerce gallery must fall back gracefully to its placeholder.
