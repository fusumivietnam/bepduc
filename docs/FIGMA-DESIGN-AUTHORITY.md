# Bếp Đức — Approved UI Design Authority

**Approved direction:** 03 — Premium Product Gallery (approved by project owner, 2026-10-19).

**Figma master:** https://www.figma.com/design/9K2qcVZCJWBeW2gM7OOl5x

## Design precedence

1. The approved Figma Design Master controls page architecture, component hierarchy and visual direction.
2. WordPress `theme.json` remains the executable source for semantic color, typography and spacing tokens until the variable/component library is completed and reconciled.
3. WooCommerce is authoritative for prices, stock, variations, product images and attributes. Do not copy fictitious products/prices/brand logos from concept mockups.
4. Where the Figma file contains image placeholders, display a clear neutral placeholder or native WooCommerce product-image placeholder, never a generated image presented as a real product.
5. Figma V1 is an editable **review baseline** (not yet a complete bound component library or production spec). Any deviations require approval, and a linked PR with screenshots.

## Direction and current delivery

Product-first warm premium gallery. Canvas #F7F4EE, surface #FFFFFF, ink #181714, muted #6F6B63, border #DED9D0, accent red #9D1F24 (all from theme.json). Typographic direction is Inter/system sans. Page anatomy: editorial hero → four live categories → live product gallery → Compare + Buying Advisor. Articles/footer remain an upcoming alignment task.

The previous unapproved CSS kitchen-drawing hero is retired. The hero deliberately shows a neutral, labeled image slot until approved licensed editorial photography is supplied.

## Design-to-code acceptance gate

- Match approved Figma page structure and typography; no unexplained section additions.
- Responsive screenshots at 320, 375, 768, 1280px; no document-level overflow.
- Browser smoke testing, focus visibility and source-backed text.
- Verify category labels and product prices use real Woo data.
- Human comparison against Figma desktop and mobile frames is required before considering pixel accuracy complete.
- CI success alone is **not** signoff on visual fidelity.

### Open gaps

Figma asset placeholders remain pending licensing; mobile screens for all secondary pages, design variables/component variants and accessibility review are not yet complete. This PR implements a first homepage-alignment increment, not full website parity.
