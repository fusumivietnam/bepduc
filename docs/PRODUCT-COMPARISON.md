# Product Comparison and Buying Advisor — First release

This feature compares **2–3 published WooCommerce products** using only live product data: Woo price display, stock availability, native brands and visible global attributes. No invented measurements, hidden brand terms, AI rankings, “best” scores, or stored tracking.

## Setup (staging, then production under normal content approval)

Create a **published WordPress page** whose slug is `so-sanh` (title e.g. “So sánh”). The Block Theme automatically loads `templates/page-so-sanh.html`; its `bepduc/product-compare` block renders the form/table. Creating this page is a normal explicit editorial step — the theme does **not** create pages as a side effect on activation.

Once the page exists, Product Detail automatically shows “So sánh sản phẩm này” linking to that page with a selected product ID. The visitor can then select up to two more products. The form uses GET URLs and an HTTP 303 redirect to a canonical `bd_compare=ID,ID` form; the comparison can be bookmarked/shared. No cookies, user account, sessions, database writes or third-party services are used.

## Boundaries / review

- Compare only WooCommerce published, non-hidden parent products. Variations are not compared as independent parent products; the variable parent’s displayed price may be a range.
- Input IDs are strictly numeric, deduplicated, capped at 3. Invalid/unpublished/hidden IDs are ignored.
- Attribute cells show actual visible Woo attribute terms. A missing value is labelled “Chưa có dữ liệu” and cannot be treated as a negative property.
- Stock and price are time-sensitive. Confirm at purchase; this view is not a quote.
- Product-specific installation guidance remains source-backed under `bd_*` metadata and **is not a ranking signal**. Technical sizing advice requires manufacturer documentation.
- Initial selector lists up to 100 published products by title; large catalogues should use a reviewed async search or category-scoped lookup in a later issue.

## Testing

CI creates a `so-sanh` Page on disposable WP + Woo, seeds 24 fake products and makes real HTTP requests for 2 products, a variable parent, invalid IDs, zero/one selection, canonical form redirect, detail-to-compare link and CSS. A browser visual review (mobile horizontal table scrolling, keyboard, headings and color contrast) is still required before launch.

Design guidance is in `docs/DESIGN-SYSTEM.md` and uses principles from `ux-ui-agent-skills` without bundling the upstream runtime.
