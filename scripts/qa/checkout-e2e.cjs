'use strict';
/**
 * Real WooCommerce browser journey on disposable local CI database only.
 * COD is the WooCommerce core offline payment method. Never contact a provider.
 */
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');

const base = process.env.QA_BASE_URL || 'http://127.0.0.1:8017';
const out = process.env.QA_OUTPUT || 'artifacts/visual-qa';
const email = 'qa-checkout@example.test';
const viewports = [320, 375, 768, 1280];

async function captureResponsive(page, name, selectors) {
  for (const width of viewports) {
    await page.setViewportSize({ width, height: 812 });
    for (const selector of selectors) {
      const control = page.locator(selector).first();
      await control.waitFor({ state: 'visible', timeout: 15000 });
      if (!(await control.isVisible())) throw new Error(name + ': missing ' + selector + ' at ' + width + 'px');
    }
    const geometry = await page.evaluate(() => ({
      scroll: document.documentElement.scrollWidth,
      viewport: document.documentElement.clientWidth
    }));
    if (geometry.scroll > geometry.viewport + 2) {
      throw new Error(name + ': horizontal overflow at ' + width + 'px (' +
        geometry.scroll + ' > ' + geometry.viewport + ')');
    }
    await page.screenshot({
      path: path.join(out, 'checkout-' + name + '-' + width + '.png'),
      fullPage: true
    });
  }
  // Keep the single existing COD order journey at its original mobile width.
  await page.setViewportSize({ width: 375, height: 812 });
}

async function main() {
  if (!/^http:\/\/(127\.0\.0\.1|localhost)(:\d+)?$/.test(base)) {
    throw new Error('Checkout E2E is restricted to localhost');
  }
  fs.mkdirSync(out, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  try {
    const context = await browser.newContext({ viewport: { width: 375, height: 812 }, reducedMotion: 'reduce' });
    const page = await context.newPage();
    const pageErrors = [];
    page.on('pageerror', err => pageErrors.push(err.message));
    const product = await page.goto(base + '/product/qa-checkout-item/', { waitUntil: 'domcontentloaded' });
    if (!product?.ok()) throw new Error('QA product detail unavailable: ' + product?.status());
    await page.locator('button.single_add_to_cart_button').first().click();
    // WooCommerce may redirect to cart or stay on the product page.
    const cart = await page.goto(base + '/qa-cart/', { waitUntil: 'domcontentloaded' });
    if (!cart?.ok()) throw new Error('QA cart unavailable');
    await page.locator('.woocommerce-cart-form .cart_item').first().waitFor({ timeout: 20000 });
    await captureResponsive(page, 'cart', ['.woocommerce-cart-form .cart_item']);

    const checkout = await page.goto(base + '/qa-checkout/', { waitUntil: 'domcontentloaded' });
    if (!checkout?.ok()) throw new Error('QA checkout unavailable');
    const form = page.locator('form.checkout');
    await form.waitFor({ timeout: 20000 });
    await page.locator('#billing_first_name').fill('Khách');
    await page.locator('#billing_last_name').fill('Kiểm thử');
    await page.locator('#billing_address_1').fill('Số 1 Đường QA');
    await page.locator('#billing_city').fill('Hà Nội');
    await page.locator('#billing_phone').fill('0900000000');
    await page.locator('#billing_email').fill(email);

    const cod = page.locator('#payment_method_cod');
    await cod.waitFor({ state: 'attached', timeout: 15000 });
    if (!(await cod.isChecked())) await cod.check();
    await captureResponsive(page, 'form', ['form.checkout', '#billing_first_name', '#billing_email', '#place_order']);
    await page.locator('#place_order').click();
    await page.waitForURL(/order-received/, { timeout: 45000 });
    const orderKey = new URL(page.url()).searchParams.get('key');
    if (!orderKey?.startsWith('wc_order_')) throw new Error('Woo order-received URL missing valid order key');
    // Thank-you markup varies between WooCommerce classic and block templates.
    // The order-received URL/key and post-browser database assertions are authoritative.
    await page.screenshot({ path: path.join(out, 'checkout-confirmation-mobile-375.png'), fullPage: true });
    if (pageErrors.length) throw new Error('Browser errors: ' + pageErrors.join(' | '));
    console.log('WooCommerce browser flow succeeded: product -> cart -> checkout -> order received (email transport intercepted).');
    await context.close();
  } finally {
    await browser.close();
  }
}
main().catch(err => { console.error(err); process.exitCode = 1; });
