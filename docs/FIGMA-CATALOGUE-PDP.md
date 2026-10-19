# Figma 03 — Catalogue & Product Detail alignment (increment 02)

Approved design master: https://www.figma.com/design/9K2qcVZCJWBeW2gM7OOl5x

This PR applies the **Premium Product Gallery** direction to the existing WooCommerce archive and product-detail templates without replacing WooCommerce native business logic.

## Delivered

- Catalogue: clearer editorial introduction, surfaced filter panel, neutral image gallery backgrounds, four-to-two-to-two product grid behaviors inherited from the existing theme, price emphasis and accessible native Woo add-to-cart actions.
- Product detail: clear division between image gallery and purchase panel; product title, native rating, price, options and cart remain WooCommerce blocks; technical specifications and guidance remain source-backed.
- Styles use existing semantic tokens from `theme.json` instead of literal gradients, fabricated photography, inventory badges or checkout promises.
- Playwright now captures **Product Detail at all four widths** (320, 375, 768, 1280) rather than only one mobile screenshot, checking document-level overflow and uncaught browser errors.

## Review contract / limits

These are targeted theme-level refinements, not a pixel-perfect replica. Figma frames presently use image placeholders and editable editorial text; there is no approved manufacturer-licensed photo set or fully bound variant component library. Do not paste simulated products or prices from mockups into WooCommerce. CI establishes runtime and responsive health, while human side-by-side screenshot comparison and keyboard/contrast review remain required before visual signoff.

**Next increment:** bind reusable design-system components and tokens in Figma, followed by Blog/Article pages and mobile secondary screens. Theme's `theme.json` remains executable token source until the Figma variables are reconciled.
