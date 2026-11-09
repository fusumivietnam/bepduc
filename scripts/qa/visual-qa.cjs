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
  { name: 'journal', route: '/cam-nang/', selector: '.bd-journal' },
  { name: 'article', route: '/huong-dan-chon-thiet-bi-demo/', selector: '.bd-article' },
  { name: 'ui-kit', route: '/ui-kit/', selector: '.bd-ui-kit' },
];

/**
 * Focused keyboard + accessible-name smoke test (not a full WCAG audit).
 * Native labels/ARIA naming and visible keyboard focus are checked on
 * project-owned forms; WooCommerce internals are audited separately.
 */
async function auditFormAccessibility(page, scope, fieldSelector) {
  const controls = page.locator(scope + ' ' + fieldSelector);
  const submit = page.locator(scope + ' button[type="submit"], ' + scope + ' button:not([type])').first();
  const count = await controls.count();
  if (!count) throw new Error(scope + ': missing form controls');
  if (!(await submit.count())) throw new Error(scope + ': missing submit button');
  for (let i = 0; i < count; i++) {
    const field = controls.nth(i);
    const name = await field.evaluate(el => {
      const labels = Array.from(el.labels || []).map(x => x.textContent.trim()).join(' ').trim();
      const aria = el.getAttribute('aria-label') || '';
      const labelledby = (el.getAttribute('aria-labelledby') || '').split(/\\s+/).filter(Boolean)
        .map(id => document.getElementById(id)?.textContent?.trim() || '').join(' ');
      return (labels || aria || labelledby).trim();
    });
    if (!name) throw new Error(scope + ': unlabeled field #' + (i + 1));
  }
  // Start at the top of the document and follow actual Tab navigation.
  await page.evaluate(() => { document.activeElement?.blur?.(); document.body.focus(); });
  const expected = count + 1;
  const seen = new Set();
  for (let attempt = 0; attempt < 120 && seen.size < expected; attempt++) {
    await page.keyboard.press('Tab');
    const state = await page.evaluate(({ scope, fieldSelector }) => {
      const active = document.activeElement;
      const root = document.querySelector(scope);
      if (!active || !root?.contains(active)) return null;
      const controls = [...root.querySelectorAll(fieldSelector)];
      const submit = root.querySelector('button[type="submit"], button:not([type])');
      const index = controls.indexOf(active);
      if (index < 0 && active !== submit) return null;
      const style = getComputedStyle(active);
      const visibleFocus = (style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0) ||
        (style.boxShadow !== 'none');
      return { id: index >= 0 ? 'field-' + index : 'submit', visibleFocus };
    }, { scope, fieldSelector });
    if (!state) continue;
    if (!state.visibleFocus) throw new Error(scope + ': keyboard focus is not visibly indicated on ' + state.id);
    seen.add(state.id);
  }
  if (seen.size !== expected) {
    throw new Error(scope + ': only ' + seen.size + '/' + expected + ' controls reached by Tab');
  }
}

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
        if (target.name === 'ui-kit') {
          if (await page.locator('.bd-ui-kit__swatch').count() !== 8) errors.push('Expected 8 theme colors');
          if (await page.locator('.bd-ui-kit__product').count() !== 1) errors.push('QA showcase must use a seeded WooCommerce product');
          if (await page.locator('.bd-ui-kit__article').count() !== 1) errors.push('QA showcase must use a real WordPress post');
        }
        if (target.name === 'compare') {
          const fields = await page.locator('.bd-compare__field').count();
          if (fields !== 3) errors.push('Expected three grouped comparison selectors, found ' + fields);
          await auditFormAccessibility(page, '.bd-compare__form', '.bd-compare__field select');
        }
        if (target.name === 'advisor') {
          const fields = await page.locator('.bd-advisor__field').count();
          if (fields !== 4) errors.push('Expected four grouped advisor fields, found ' + fields);
          await auditFormAccessibility(page, '.bd-advisor__form', '.bd-advisor__field select, .bd-advisor__field input');
        }
        const horizontalOverflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 3);
        // Comparison is allowed to scroll horizontally *inside* its labeled table region, not body.
        if (!response?.ok() || horizontalOverflow || errors.length) {
          failures.push({ route: target.route, viewport: viewport.name, status: response?.status(), horizontalOverflow, errors });
        }
      }
      await context.close();
    }
    // Validate and capture real single-product UI at every supported viewport.
    for (const viewport of viewports) {
      const context = await browser.newContext({ viewport, reducedMotion: 'reduce' });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', err => errors.push(err.message));
      await page.goto(base + '/shop/', { waitUntil: 'domcontentloaded' });
      const url = await page.locator('.bd-product-card__title a').first().getAttribute('href');
      if (!url) throw new Error('No seeded product link on shop archive');
      const response = await page.goto(new URL(url, base).href, { waitUntil: 'domcontentloaded' });
      await page.locator('.bd-product-detail__gallery').first().waitFor();
      await page.locator('.bd-product-detail__purchase').first().waitFor();
      await page.screenshot({ path: path.join(out, 'product-detail-' + viewport.name + '.png'), fullPage: true });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 3);
      if (!response?.ok() || overflow || errors.length) {
        failures.push({ route: 'product-detail', viewport: viewport.name, status: response?.status(), horizontalOverflow: overflow, errors });
      }
      await context.close();
    }
  } finally {
    await browser.close();
  }
  if (failures.length) {
    throw new Error('Browser QA failures: ' + JSON.stringify(failures, null, 2));
  }
  console.log('Responsive browser QA passed. Screenshots: ' + out);
}
main().catch(err => { console.error(err); process.exitCode = 1; });
