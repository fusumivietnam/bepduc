const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.QA_BASE_URL || 'http://127.0.0.1:8017';
const out = process.env.QA_OUTPUT || 'artifacts/visual-qa';
const viewports = [
  { name: 'mobile-320', width: 320, height: 740 },
  { name: 'mobile-375', width: 375, height: 812 },
  { name: 'tablet-768', width: 768, height: 1024 },
  { name: 'desktop-1280', width: 1280, height: 900 },
];
const urls = [
  { name: 'home', route: '/', selector: '.bd-home' },
  { name: 'shop', route: '/shop/', selector: '.bd-catalogue-page' },
  { name: 'compare', route: '/so-sanh/', selector: '.bd-compare' },
  { name: 'advisor', route: '/tu-van-chon-bep/', selector: '.bd-advisor' },
];
async function main() {
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  let failures = [];
  try {
    for (const viewport of viewports) {
      const context = await browser.newContext({ viewport, reducedMotion: 'reduce' });
      const page = await context.newPage();
      for (const target of urls) {
        const errors = [];
        page.on('pageerror', err => errors.push(err.message));
        const response = await page.goto(base + target.route, { waitUntil: 'domcontentloaded', timeout: 45000 });
        await page.locator(target.selector).first().waitFor();
        await page.screenshot({ path: path.join(out, target.name + '-' + viewport.name + '.png'), fullPage: true });
        const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 3);
        // Comparison is allowed to scroll horizontally *inside* its labeled table region, not body.
        if (!response?.ok() || horizontalOverflow || errors.length) {
          failures.push({ route: target.route, viewport: viewport.name, status: response?.status(), horizontalOverflow, errors });
        }
      }
      await context.close();
    }
    // One real product detail page from seeded fixtures, with no hardcoded SKU slug.
    const context = await browser.newContext({ viewport: viewports[1] });
    const page = await context.newPage();
    await page.goto(base + '/shop/', { waitUntil: 'domcontentloaded' });
    const url = await page.locator('.bd-product-card__title a').first().getAttribute('href');
    if (!url) throw new Error('No product detail link found');
    await page.goto(new URL(url, base).href, { waitUntil: 'domcontentloaded' });
    await page.locator('.bd-product-detail').first().waitFor();
    await page.screenshot({ path: path.join(out, 'product-detail-mobile-375.png'), fullPage: true });
    await context.close();
  } finally {
    await browser.close();
  }
  if (failures.length) {
    throw new Error('Browser QA failures: ' + JSON.stringify(failures, null, 2));
  }
  console.log('Responsive browser QA passed. Screenshots: ' + out);
}
main().catch(err => { console.error(err); process.exitCode = 1; });
