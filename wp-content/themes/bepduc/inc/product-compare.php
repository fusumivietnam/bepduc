<?php
/**
 * Read-only WooCommerce comparison page. No cookies, mutations or AI scores.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @return int[] */
function bepduc_compare_ids(): array
{
    $raw = isset($_GET['bd_compare']) && is_string($_GET['bd_compare'])
        ? wp_unslash($_GET['bd_compare']) : '';
    if (! preg_match('/^[0-9,]{0,70}$/D', $raw)) {
        return [];
    }
    $ids = array_values(array_unique(array_filter(array_map('absint', explode(',', $raw)))));
    return array_slice($ids, 0, 3);
}

/** @return WC_Product[] */
function bepduc_compare_products(): array
{
    if (! function_exists('wc_get_product')) {
        return [];
    }
    $products = [];
    foreach (bepduc_compare_ids() as $id) {
        $product = wc_get_product($id);
        if (! $product || $product->get_status() !== 'publish'
            || ! in_array($product->get_type(), ['simple', 'variable', 'grouped', 'external'], true)
            || $product->get_catalog_visibility() === 'hidden') {
            continue;
        }
        $products[] = $product;
    }
    return $products;
}

function bepduc_compare_page_url(): string
{
    $page = get_page_by_path('so-sanh', OBJECT, 'page');
    return $page instanceof WP_Post && $page->post_status === 'publish'
        ? get_permalink($page) : '';
}

/**
 * Compare link, only shown when a published comparison page exists.
 */
function bepduc_render_compare_action(): string
{
    if (! function_exists('wc_get_product') || ! is_singular('product')) {
        return '';
    }
    $product = wc_get_product(get_queried_object_id());
    $url = bepduc_compare_page_url();
    if (! $product || ! $url || $product->get_status() !== 'publish') {
        return '';
    }
    return '<p class="bd-compare-action"><a href="'
        . esc_url(add_query_arg('bd_compare', (string) $product->get_id(), $url))
        . '">' . esc_html__('So sánh sản phẩm này', 'bepduc') . '</a></p>';
}

/** @return array<string, string> */
function bepduc_compare_specs(WC_Product $product): array
{
    $rows = [];
    foreach ($product->get_attributes() as $attribute) {
        if (! $attribute instanceof WC_Product_Attribute || ! $attribute->get_visible()) {
            continue;
        }
        if ($attribute->is_taxonomy()) {
            $terms = wc_get_product_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
            $value = is_wp_error($terms) ? '' : implode(', ', $terms);
        } else {
            $value = implode(', ', $attribute->get_options());
        }
        if ($value !== '') {
            $rows[wc_attribute_label($attribute->get_name())] = $value;
        }
    }
    return $rows;
}

/**
 * Form and comparison table sourced from published WooCommerce records only.
 */
function bepduc_render_product_compare(): string
{
    if (! function_exists('wc_get_products')) {
        return '<p>' . esc_html__('Vui lòng kích hoạt WooCommerce.', 'bepduc') . '</p>';
    }
    $url = bepduc_compare_page_url();
    if (! $url) {
        return '';
    }

    $all = wc_get_products([
        'status' => 'publish',
        'limit' => 100,
        'orderby' => 'title',
        'order' => 'ASC',
        'visibility' => 'visible',
    ]);
    $selected = bepduc_compare_products();
    $ids = array_map(static fn (WC_Product $p): int => $p->get_id(), $selected);
    $html = '<section class="bd-compare"><h1>' . esc_html__('So sánh thiết bị bếp', 'bepduc')
        . '</h1><p>' . esc_html__('Chọn từ 2 đến 3 sản phẩm để đối chiếu thông tin thực tế. Không có điểm số hoặc đề xuất tự động.', 'bepduc')
        . '</p><form method="get" action="' . esc_url($url) . '" class="bd-compare__form">';
    // Separate inputs make keyboard selection accessible; names are encoded into URL by JS-free form handling.
    foreach ([0, 1, 2] as $index) {
        $html .= '<div class="bd-compare__field"><label for="bd-compare-' . $index . '">'
            . esc_html(sprintf('Sản phẩm %d', $index + 1)) . '</label>';
        $html .= '<select id="bd-compare-' . $index . '" name="bd_product[]"><option value="">'
            . esc_html__('Chưa chọn', 'bepduc') . '</option>';
        foreach ($all as $item) {
            if (! $item instanceof WC_Product || $item->get_catalog_visibility() === 'hidden') {
                continue;
            }
            $html .= '<option value="' . esc_attr((string) $item->get_id()) . '"'
                . selected($ids[$index] ?? 0, $item->get_id(), false)
                . '>' . esc_html($item->get_name()) . '</option>';
        }
        $html .= '</select></div>';
    }
    $html .= '<button type="submit">' . esc_html__('So sánh', 'bepduc') . '</button></form>';

    if (count($selected) < 2) {
        return $html . '<p role="status">' . esc_html__('Chọn ít nhất hai sản phẩm để hiển thị bảng so sánh.', 'bepduc') . '</p></section>';
    }

    $specs = [];
    foreach ($selected as $product) {
        $specs[$product->get_id()] = bepduc_compare_specs($product);
    }
    $labels = [];
    foreach ($specs as $values) {
        $labels = array_unique(array_merge($labels, array_keys($values)));
    }
    $html .= '<div class="bd-compare__scroll" tabindex="0" role="region" aria-label="'
        . esc_attr__('Bảng so sánh thiết bị', 'bepduc') . '"><table><thead><tr><th scope="col">'
        . esc_html__('Tiêu chí', 'bepduc') . '</th>';
    foreach ($selected as $product) {
        $html .= '<th scope="col"><a href="' . esc_url(get_permalink($product->get_id())) . '">'
            . esc_html($product->get_name()) . '</a></th>';
    }
    $html .= '</tr></thead><tbody>';
    $rows = [
        'Giá hiển thị' => static fn (WC_Product $p): string => wp_strip_all_tags($p->get_price_html()),
        'Tình trạng' => static fn (WC_Product $p): string => $p->is_in_stock() ? 'Còn hàng' : 'Hết hàng',
        'Thương hiệu' => static function (WC_Product $p): string {
            $terms = get_the_terms($p->get_id(), 'product_brand');
            return is_array($terms) ? implode(', ', wp_list_pluck($terms, 'name')) : '';
        },
    ];
    foreach ($rows as $label => $getter) {
        $html .= '<tr><th scope="row">' . esc_html($label) . '</th>';
        foreach ($selected as $p) {
            $html .= '<td>' . esc_html($getter($p) ?: 'Chưa có dữ liệu') . '</td>';
        }
        $html .= '</tr>';
    }
    foreach ($labels as $label) {
        $html .= '<tr><th scope="row">' . esc_html($label) . '</th>';
        foreach ($selected as $p) {
            $html .= '<td>' . esc_html($specs[$p->get_id()][$label] ?? 'Chưa có dữ liệu') . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table></div><p class="bd-compare__note">'
        . esc_html__('Đối chiếu yêu cầu lắp đặt với tài liệu chính hãng. Giá và tồn kho có thể thay đổi.', 'bepduc')
        . '</p></section>';
    return $html;
}

/** Convert GET select values to a stable URL without scripts/cookies. */
function bepduc_compare_form_redirect(): void
{
    if (! is_page('so-sanh') || ! isset($_GET['bd_product'])
        || ! is_array($_GET['bd_product'])) {
        return;
    }
    $ids = [];
    foreach (array_slice($_GET['bd_product'], 0, 3) as $raw) {
        if (is_string($raw) && ctype_digit($raw)) {
            $ids[] = (int) $raw;
        }
    }
    $ids = array_values(array_unique(array_filter($ids)));
    $url = bepduc_compare_page_url();
    if ($url) {
        wp_safe_redirect(add_query_arg('bd_compare', implode(',', $ids), $url), 303);
        exit;
    }
}
add_action('template_redirect', 'bepduc_compare_form_redirect');

function bepduc_register_compare_blocks(): void
{
    register_block_type('bepduc/product-compare', [
        'api_version' => 3, 'title' => 'So sánh sản phẩm', 'category' => 'woocommerce',
        'supports' => ['html' => false], 'render_callback' => 'bepduc_render_product_compare',
    ]);
    register_block_type('bepduc/compare-action', [
        'api_version' => 3, 'title' => 'Liên kết so sánh', 'category' => 'woocommerce',
        'supports' => ['html' => false], 'render_callback' => 'bepduc_render_compare_action',
    ]);
}
add_action('init', 'bepduc_register_compare_blocks');
