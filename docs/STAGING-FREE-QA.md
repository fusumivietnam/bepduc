# Free preview and responsive QA

## Browser-only WordPress Playground preview

Open [WordPress Playground](https://playground.wordpress.net/) and load the Blueprint at:
`https://raw.githubusercontent.com/fusumivietnam/bepduc/main/playground/blueprint.json`

Or construct a direct URL using the Playground `blueprint-url` parameter and the raw JSON URL (URL-encode the parameter). The Blueprint installs WooCommerce 11.2.1 from WordPress.org and the project-owned theme/core using `git:directory`, then uses a single `runPHP` step (no WP-CLI, avoiding a known browser-WebAssembly crash) to add four small synthetic WooCommerce products. It creates `shop`, `so-sanh`, and `tu-van-chon-bep` Pages and navigates to `/shop/`.

This **lightweight preview has four products**, not the full 24-product/variation fixture set used by GitHub CI. Browser Playground initialization is not covered by the MySQL-based GitHub Actions tests and still needs a manual launch check.

The demo is **ephemeral browser storage**, not a persistent staging server. The Git resources refer to main; restart the Playground for fresh code. Do not enter customer data, credentials, licenses or real payments. Plugin downloads require browser network access and may take time. Playground is SQLite-based, so it is not a substitute for production MySQL behavior.

## Browser visual QA in GitHub Actions

The `Browser visual QA` workflow sets up its own disposable MySQL 8, WordPress, WooCommerce and fake products. It runs Playwright Chromium at 320, 375, 768 and 1280 CSS pixels for Shop, Comparison and Buying Advisor, plus a mobile Product Detail screenshot. The job fails on HTTP errors, uncaught page JavaScript exceptions and **document-level** horizontal overflow (a comparison table's internal horizontal scroll is allowed).

Open GitHub Actions → Browser visual QA → successful run → Artifacts → `bepduc-responsive-screenshots`. These PNGs are temporary CI artifacts (retained for 7 days), not committed to Git.

Screenshot QA is a smoke gate, not a substitute for human visual review, real variation selection, checkout, responsive zoom, or accessibility audit.

CI should always use fake seed fixtures; never restore live WordPress databases or customer orders into a public PR job.
