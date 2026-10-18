# Bếp Đức Design System — Working Rules

## Source and boundaries

Design references: [UX/UI Agent Skills by plugin87](https://github.com/plugin87/ux-ui-agent-skills) (MIT). We adopt **selected principles**, not the repository's generated examples, React components, runtime scripts, or vendor dependencies. The canonical rendered tokens remain in `wp-content/themes/bepduc/theme.json`.

Inspired by its token-first architecture, accessibility checks, objective quality gates and component anatomy. Do **not** copy its 52 gates into our CI as though they test WooCommerce automatically. Any gate must run against actual rendered Bepduc HTML.

## Visual direction

Premium built-in kitchen appliances: warm canvas, generous but purposeful whitespace, ink-dark typography, restrained red accent, authentic product photography only when licensed, clear technical specifications and installation advice.

No gratuitous gradients, animation-heavy carousels, unsupported brand/logos or faux product photography. Product images can be missing; render the WooCommerce placeholder rather than fetching arbitrary stock images.

## Tokens

Defined in `theme.json` and consumed through CSS custom properties such as:
- `--wp--preset--color--canvas` warm off-white;
- `--wp--preset--color--surface` white surfaces;
- `--wp--preset--color--ink` foreground;
- `--wp--preset--color--muted` supporting text;
- `--wp--preset--color--border` subtle separators;
- `--wp--preset--color--accent` restrained brand red;
- `--wp--preset--spacing--1..9` spacing scale.

New tokens must be added to `theme.json`; avoid scattering new colors and spacing literals throughout CSS. Apply semantic intent: sale/danger accent, subdued supplementary labels, focus outline visible against both canvas and surface.

## Product Card contract

A Product Card must show these states without exceptions:

| State | Required behavior |
| --- | --- |
| Regular | Product image/placeholder, name, brand when assigned, formatted Woo price, accessible link |
| Sale | Regular price struck through, sale price legible, native Woo sale badge |
| Out of stock | Availability clearly conveyed, no false buy-now promise |
| Variable | Native WooCommerce select-options behavior; no fake instant add-to-cart |
| Missing image | Consistent aspect ratio and Woo placeholder |
| Long title | Wrap safely; don't cut into price or CTA |
| Featured | Preserve Woo featured status without inventing scarcity |

Never reconstruct price calculation, cart actions or stock logic with theme PHP; use WooCommerce blocks and APIs.

## Accessibility / QA gates

P0 blocking requirements for UI PRs:
- No uncaught PHP error or missing products in the real WooCommerce shop HTTP response.
- All interactive controls are keyboard operable and have visible focus outlines.
- Responsive layout avoids horizontal overflow at 320px, 375px, 768px, 1280px.
- Minimum WCAG AA text contrast target 4.5:1 (3:1 for large text). Validate *rendered* colors, not just tokens.
- Images have suitable alternative text or are decorative; no fake alt text.
- Respect `prefers-reduced-motion`.
- Controls and links describe their actions; no placeholder `href="#"` actions.

P1 review requirements:
- Visual hierarchy from category heading → product content → price → CTA.
- Consistent card height, accessible sorting and filters, clear empty/no-results state.
- Mobile filters reachable without blocking the product list.
- No third-party JS for features that native Woo blocks already supply.

## Definition of Done

A UI PR is ready only after PHP/JSON lint, WordPress/WooCommerce activation, actual HTTP shop response containing product data, and a human visual review at mobile + desktop. CI passing structural checks alone is insufficient. Link screenshots or preview when available; do not claim screenshot QA without running a browser.

## Upstream attribution

Reference: plugin87/ux-ui-agent-skills, MIT; principles adapted for a WordPress block theme. We don't vendor the upstream repository or claim compatibility with its framework-specific generated code.
