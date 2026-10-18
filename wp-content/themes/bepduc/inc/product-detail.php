<?php
/**
 * Presentation-only product specifications and installation guidance blocks.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolve current product context on single product templates.
 */
function bepduc_detail_product(): ?WC_Product
{
    if (! function_exists('wc_get_product') || ! is_singular('product')) {
        return null;
    }
    $product = wc_get_product(get_queried_object_id());
    return $product instanceof WC_Product ? $product : null;
}

/**
 * Show only actual, declared WooCommerce product attributes and dimensions.
 */
function bepduc_render_product_specifications(): string
{
    $product = bepduc_detail_product();
    if (! $product) {
        return '';
    }

    $rows = [];
    $attributes = $product->get_attributes();
    foreach ($attributes as $attribute) {
        if (! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible()) {
            continue;
        }

        $label = wc_attribute_label($attribute->get_name());
        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
            $value = is_wp_error($terms) ? '' : implode(', ', $terms);
        } else {
            $value = implode(', ', $attribute->get_options());
        }

        if ($value !== '') {
            $rows[] = [$label, $value];
        }
    }

    // WooCommerce dimensions describe the product, never installation cutouts.
    foreach (['length' => 'Chiều dài sản phẩm', 'width' => 'Chiều rộng sản phẩm', 'height' => 'Chiều cao sản phẩm'] as $key => $label) {
        $value = $product->{'get_' . $key}();
        if ($value !== '') {
            $rows[] = [$label, wc_format_localized_decimal((string) $value) . ' ' . get_option('woocommerce_dimension_unit', 'cm')];
        }
    }
    if ($product->get_weight() !== '') {
        $rows[] = ['Trọng lượng sản phẩm', wc_format_localized_decimal((string) $product->get_weight()) . ' ' . get_option('woocommerce_weight_unit', 'kg')];
    }

    if (! $rows) {
        return '<p class="bd-product-detail__empty">Thông số kỹ thuật đang được cập nhật.</p>';
    }

    $html = '<div class="bd-product-specifications"><table><tbody>';
    foreach ($rows as [$label, $value]) {
        $html .= '<tr><th scope="row">' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
    }
    return $html . '</tbody></table></div>';
}

/**
 * Neutral guidance, not fabricated specifications or service commitments.
 */
function bepduc_render_installation_guidance(): string
{
    if (! bepduc_detail_product()) {
        return '';
    }

    return '<aside class="bd-product-installation" aria-label="' . esc_attr__('Lưu ý lắp đặt', 'bepduc') . '">'
        . '<strong>' . esc_html__('Kiểm tra trước khi lắp đặt', 'bepduc') . '</strong>'
        . '<p>' . esc_html__('Đối chiếu kích thước thiết bị, kích thước khoét đá/tủ, nguồn điện và khoảng thông gió với tài liệu chính hãng trước khi đặt hàng hoặc thi công.', 'bepduc') . '</p>'
        . '</aside>';
}

/**
 * Publish checked buying and installation notes only if a source is recorded.
 */
function bepduc_render_product_advice(): string
{
    $product = bepduc_detail_product();
    if (! $product || ! class_exists('Bepduc\\Core\\Advisor\\ProductFields')) {
        return '';
    }
    $items = \Bepduc\Core\Advisor\ProductFields::published($product);
    if (! $items) {
        return '';
    }

    $html = '<section class="bd-product-advice" aria-label="' . esc_attr__('Tư vấn lựa chọn và lắp đặt', 'bepduc') . '">';
    $html .= '<h2>' . esc_html__('Tư vấn sản phẩm', 'bepduc') . '</h2><dl>';
    foreach ($items as $label => $value) {
        $html .= '<div><dt>' . esc_html($label) . '</dt><dd>' . nl2br(esc_html($value)) . '</dd></div>';
    }
    return $html . '</dl></section>';
}

function bepduc_register_detail_blocks(): void
{
    foreach ([
        'bepduc/product-specifications' => ['Thông số kỹ thuật', 'bepduc_render_product_specifications'],
        'bepduc/installation-guidance' => ['Lưu ý lắp đặt', 'bepduc_render_installation_guidance'],
        'bepduc/product-advice' => ['Tư vấn sản phẩm', 'bepduc_render_product_advice'],
    ] as $name => [$title, $callback]) {
        register_block_type($name, [
            'api_version' => 3,
            'title' => $title,
            'category' => 'woocommerce',
            'supports' => ['html' => false],
            'render_callback' => $callback,
        ]);
    }
}
add_action('init', 'bepduc_register_detail_blocks');
