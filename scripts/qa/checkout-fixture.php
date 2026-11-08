<?php
/**
 * Disposable CI checkout fixture. Run ONLY against a WP_ENVIRONMENT_TYPE=local
 * database created by browser-visual-qa.yml. It does not alter production.
 */
if (! defined('ABSPATH') || wp_get_environment_type() !== 'local' || ! function_exists('wc_get_product')) {
    throw new RuntimeException('Checkout fixture may run only on local WooCommerce');
}
if (get_option('bepduc_checkout_e2e_fixture') !== false) {
    throw new RuntimeException('Checkout fixture already installed');
}
$pages = ['cart' => ['qa-cart', '[woocommerce_cart]'], 'checkout' => ['qa-checkout', '[woocommerce_checkout]']];
foreach ($pages as $type => [$slug, $shortcode]) {
    $id = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish',
        'post_name' => $slug, 'post_title' => 'QA ' . ucfirst($type),
        'post_content' => $shortcode,
    ], true);
    if (is_wp_error($id) || ! $id) {
        throw new RuntimeException('Could not create ' . $type . ' checkout fixture page');
    }
    update_option('woocommerce_' . $type . '_page_id', (int) $id);
}
update_option('woocommerce_enable_guest_checkout', 'yes');
update_option('woocommerce_currency', 'VND');
update_option('woocommerce_default_country', 'VN');
update_option('woocommerce_cod_settings', [
    'enabled' => 'yes',
    'title' => 'Thanh toán khi nhận hàng',
    'description' => 'Chỉ áp dụng cho bộ kiểm thử.',
    'instructions' => '',
    'enable_for_methods' => [],
    'enable_for_virtual' => 'yes',
]);
$product = new WC_Product_Simple();
$product->set_name('[DEMO QA] Sản phẩm kiểm thử thanh toán');
$product->set_slug('qa-checkout-item');
$product->set_status('publish');
$product->set_catalog_visibility('visible');
$product->set_regular_price('100000');
$product->set_virtual(true);
$product->set_manage_stock(false);
$product->set_stock_status('instock');
$productId = $product->save();
if (! $productId) {
    throw new RuntimeException('Cannot create checkout fixture product');
}
update_option('bepduc_checkout_e2e_fixture', (int) $productId);
flush_rewrite_rules(false);
echo "Disposable checkout fixture installed, product ID: {$productId}\n";
