# Buying Advisor Wizard

The published WordPress Page with slug `tu-van-chon-bep` loads `page-tu-van-chon-bep.html` automatically. Create it explicitly in staging/production; the theme never provisions a production page.

The anonymous GET form filters WooCommerce *published, visible, in-stock* products by native `product_cat`, `pa_installation`, `pa_width` and maximum Woo current price in VND. It returns up to 12 matches and displays exact matching criteria, with no secret scoring system or unsupported "best" rankings.

## Boundaries

- Only use manufacturer-backed attributes and actual WooCommerce pricing/stock.
- The width attribute is a **nominal product width**, not a guaranteed cutout size.
- Prices for variable products may refer to the minimum variation price; examine the variation before purchase.
- No accounts, cookies, user tracking, writes, external AI API, ACF Pro or additional runtime dependencies.
- Empty criteria prompt the visitor to choose a filter; no matching product is clearly identified.
- The first implementation scans up to 100 product records. For a larger published inventory, implement query-level pagination and more scalable tax/price queries.
- The recommendation is a *data-driven shortlist*, not proof of installation compatibility, quality or suitability.

## QA

The GitHub WordPress/Woo smoke test provisions a temporary page and verifies anonymous HTML responses with no criteria, category selection and an impossible budget. Full browser QA of screen-reader announcement, filters, mobile layouts, and performance on production-sized datasets remains a prerequisite to launch.

The theme follows `docs/DESIGN-SYSTEM.md`, with selected practices from `ux-ui-agent-skills`.
