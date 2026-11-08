<?php
/** Post-browser assertions against isolated WooCommerce database and mail sink. */
declare(strict_types=1);
if (! defined('ABSPATH') || wp_get_environment_type() !== 'local' || ! function_exists('wc_get_orders')) {
    throw new RuntimeException('Checkout assertions are restricted to local WooCommerce');
}
$productId = (int) get_option('bepduc_checkout_e2e_fixture');
if (! $productId) {
    throw new RuntimeException('Missing checkout fixture');
}
$orders = wc_get_orders([
    'billing_email' => 'qa-checkout@example.test',
    'limit' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
]);
$matched = [];
foreach ($orders as $order) {
    if (! $order instanceof WC_Order || $order->get_payment_method() !== 'cod') {
        continue;
    }
    foreach ($order->get_items() as $item) {
        if ($item instanceof WC_Order_Item_Product && $item->get_product_id() === $productId) {
            $matched[] = $order;
            break;
        }
    }
}
if (count($matched) !== 1) {
    throw new RuntimeException('Expected exactly one COD order for QA product, got ' . count($matched));
}
$order = $matched[0];
if (! $order->get_id() || (float) $order->get_total() !== 100000.0
    || in_array($order->get_status(), ['failed', 'cancelled', 'trash'], true)) {
    throw new RuntimeException('QA order has invalid total, status or ID');
}
$mails = get_option('bepduc_checkout_e2e_mail_events', []);
$customerEmailFound = false;
foreach (is_array($mails) ? $mails : [] as $mail) {
    if (str_contains(strtolower((string) ($mail['to'] ?? '')), 'qa-checkout@example.test')) {
        $customerEmailFound = true;
        break;
    }
}
if (! $customerEmailFound) {
    throw new RuntimeException('No customer transactional wp_mail call captured. Check Woo email triggers.');
}
echo 'Verified WooCommerce COD order #' . $order->get_id()
    . ', exact fixture total, product line item, and intercepted customer email attempt.' . PHP_EOL;
