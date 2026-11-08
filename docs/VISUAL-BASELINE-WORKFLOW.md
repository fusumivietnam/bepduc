# Design Registry, Component Showcase and Visual Baseline

**Approved visual direction:** Figma 03 — Premium Product Gallery.
Figma: https://www.figma.com/design/9K2qcVZCJWBeW2gM7OOl5x

## Authority and boundaries

- `theme.json` is the executable source for colors, spacing and type tokens.
- `design-system/registry.json` maps approved UI elements to the existing theme styles and states; it is **not a second source of token values**.
- WooCommerce supplies all product prices, inventory, names, images and product behavior.
- Figma remains the human-approved visual reference; a passing browser CI is not evidence of pixel-level parity.

## Contract check

Run `node scripts/qa/check-design-registry.cjs` before UI PR review. This checks required slugs against actual `theme.json` and ensures tracked selectors are backed by the referenced CSS files. It does not claim WCAG, visual, or comprehensive CSS-token coverage.

## Non-production showcase

On local/staging only, visit `/ui-kit/` after creating a published WordPress page with shortcode `[bepduc_design_showcase]`. In the browser workflow the page is provisioned automatically with a seeded Woo product and post. It demonstrates colors, buttons, fields, a native-data product card and an editorial card.

The shortcode **is not registered in production**, and its stylesheet is not enqueued there. Do not link the QA route in the customer-facing navigation.

## Visual regression maturity

`node scripts/qa/visual-qa.cjs` captures screenshot candidates for home, shop, compare, advisor, blog, article, UI Kit and product detail at widths 320, 375, 768, 1280.

Run `node scripts/qa/visual-regression.cjs` after screenshots. Without `QA_BASELINE_DIR`, it writes `visual-report.json` with status `candidate-awaiting-human-approval` — **no pixel comparison happens**.

To enable comparison after design review:

1. Review screenshot candidates against the Figma master and approve the intended appearance; check real Woo content, focus, contrast and mobile readability.
2. Create an immutable reviewed baseline directory, copying approved PNGs. Add `approval.json`:
   ```json
   {
     "approved": true,
     "figmaMaster": "https://www.figma.com/design/9K2qcVZCJWBeW2gM7OOl5x",
     "reviewedBy": "reviewer identity",
     "reviewedAt": "ISO 8601 approval timestamp"
   }
   ```
3. In a controlled CI environment, set `QA_BASELINE_DIR` to the reviewed directory. `QA_MAX_DIFF_RATIO` defaults to `0.005` (0.5% mismatched pixels). Differences beyond the threshold or missing baselines fail the run. Diff images are uploaded with QA artifacts.
4. Approval metadata and screenshot images must be reviewed via PR, never generated automatically from the current build to self-approve the same build.

Dynamic dates, fonts and images need deterministic fixtures/normalization before enforcing pixel thresholds broadly. Prefer staging test data, not customer/order information.

## Next gate

- Validate the staging checkout and transactional email flow with a payment provider's sandbox.
- Approve image rights and verified catalogue data.
- Confirm contrast and keyboard accessibility on rendered UI.
- Configure protected main branch (GitHub issue #4) and production backup/restore.
