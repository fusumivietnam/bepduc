<?php
/**
 * Transparent, anonymous buying advisor using only WooCommerce data.
 * No model-generated product claims, tracking, cookies or writes.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @return array<string,string> */
function bepduc_advisor_inputs(): array
{
    $values = [];
    foreach (['bd_category', 'bd_installation', 'bd_width'] as $field) {
        $raw = isset($_GET[$field]) && is_string($_GET[$field]) ? wp_unslash($_GET[$field]) : '';
        $values[$field] = sanitize_title($raw);
    }
    $budget = isset($_GET['bd_budget']) && is_string($_GET['bd_budget']) ? trim(wp_unslash($_GET['bd_budget'])) : '';
    $values['bd_budget'] = preg_match('/^[0-9]{1,12}$/D', $budget) ? $budget : '';
    return $values;
}

/**
 * Collect product category and attribute choices from the canonical Woo taxonomies.
 */
function bepduc_render_advisor(): string
{
    if (! function_exists('wc_get_products')) {
        return '<p>' . esc_html__('WooCommerce chưa sẵn sàng.', 'bepduc') . '</p>';
    }
    $values = bepduc_advisor_inputs();
    $html = '<section class="bd-advisor"><h1>' . esc_html__('Tư vấn chọn thiết bị bếp', 'bepduc') . '</h1>';
    $html .= '<p>' . esc_html__('Chọn tiêu chí quan trọng. Kết quả chỉ dựa trên thông số sản phẩm đã nhập trong WooCommerce, không phải đánh giá chất lượng.', 'bepduc') . '</p>';
    $html .= '<form method="get" class="bd-advisor__form">';
    $groups = [
        'bd_category' => ['Danh mục thiết bị', 'product_cat'],
        'bd_installation' => ['Kiểu lắp đặt', 'pa_installation'],
        'bd_width' => ['Chiều rộng danh định', 'pa_width'],
    ];
    foreach ($groups as $field => [$label, $taxonomy]) {
        $html .= '<div class="bd-advisor__field"><label for="' . esc_attr($field) . '">' . esc_html($label) . '</label>';
        $html .= '<select id="' . esc_attr($field) . '" name="' . esc_attr($field) . '"><option value="">'
            . esc_html__('Không giới hạn', 'bepduc') . '</option>';
        $terms = taxonomy_exists($taxonomy) ? get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]) : [];
        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $html .= '<option value="' . esc_attr($term->slug) . '"'
                    . selected($values[$field], $term->slug, false) . '>' . esc_html($term->name) . '</option>';
            }
        }
        $html .= '</select></div>';
    }
    $html .= '<div class="bd-advisor__field"><label for="bd_budget">' . esc_html__('Ngân sách tối đa (₫)', 'bepduc')
        . '</label><input id="bd_budget" type="number" min="0" step="1" inputmode="numeric" name="bd_budget" value="'
        . esc_attr($values['bd_budget']) . '"></div>';
    $html .= '<button type="submit">' . esc_html__('Tìm sản phẩm phù hợp', 'bepduc') . '</button></form>';

    // Avoid claiming a recommendation before visitors provide meaningful constraints.
    $active = array_filter($values, static fn (string $v): bool => $v !== '');
    if (! $active) {
        return $html . '<p role="status">' . esc_html__('Chọn ít nhất một tiêu chí để xem gợi ý.', 'bepduc') . '</p></section>';
    }

    foreach ($groups as $field => [, $taxonomy]) {
        if ($values[$field] !== '' && (! taxonomy_exists($taxonomy)
            || ! get_term_by('slug', $values[$field], $taxonomy))) {
            return $html . '<p role="status">' . esc_html__('Tiêu chí đã chọn không có trong danh mục hiện tại.', 'bepduc') . '</p></section>';
        }
    }

    $args = ['status' => 'publish', 'limit' => 100, 'orderby' => 'title', 'order' => 'ASC'];
    $tax = [];
    foreach ($groups as $field => [, $taxonomy]) {
        if ($values[$field] !== '') {
            $tax[] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => [$values[$field]]];
        }
    }
    if ($tax) {
        $args['tax_query'] = $tax;
    }
    $products = wc_get_products($args);
    $matches = [];
    foreach ($products as $product) {
        if (! $product instanceof WC_Product || $product->get_catalog_visibility() === 'hidden'
            || ! $product->is_in_stock()) {
            continue;
        }
        // Variable prices must be evaluated using the lowest purchasable variation price.
        $price = $product->get_price();
        if ($price === '' || ! is_numeric($price)) {
            continue;
        }
        if ($values['bd_budget'] !== '' && (float) $price > (float) $values['bd_budget']) {
            continue;
        }
        $matches[] = $product;
        if (count($matches) >= 12) {
            break;
        }
    }
    $html .= '<h2>' . esc_html__('Kết quả phù hợp tiêu chí', 'bepduc') . '</h2>';
    if (! $matches) {
        return $html . '<p role="status">' . esc_html__('Không tìm thấy sản phẩm đáp ứng đầy đủ các tiêu chí. Hãy thử tăng ngân sách hoặc bỏ bớt điều kiện.', 'bepduc') . '</p></section>';
    }

    $html .= '<p>' . esc_html(sprintf('Tìm thấy %d sản phẩm để tham khảo.', count($matches))) . '</p><div class="bd-advisor__results">';
    foreach ($matches as $p) {
        $reasons = [];
        foreach ($groups as $field => [$label, $taxonomy]) {
            if ($values[$field] !== '') {
                $term = get_term_by('slug', $values[$field], $taxonomy);
                $reasons[] = $label . ': ' . ($term ? $term->name : $values[$field]);
            }
        }
        if ($values['bd_budget'] !== '') {
            $reasons[] = 'Giá hiện tại không vượt ngân sách đã chọn';
        }
        $reasons[] = 'Có hàng theo dữ liệu WooCommerce';
        $html .= '<article class="bd-advisor__result"><h3><a href="' . esc_url(get_permalink($p->get_id())) . '">'
            . esc_html($p->get_name()) . '</a></h3><div class="bd-advisor__price">'
            . wp_kses_post($p->get_price_html()) . '</div><p>'
            . esc_html(implode(' · ', $reasons)) . '</p></article>';
    }
    return $html . '</div><p class="bd-advisor__disclaimer">'
        . esc_html__('Kết quả chỉ lọc theo dữ liệu hiện có, không xác nhận kích thước khoét tủ, tính phù hợp lắp đặt hoặc mức giá tại thời điểm thanh toán.', 'bepduc')
        . '</p></section>';
}

function bepduc_register_advisor_block(): void
{
    register_block_type('bepduc/buying-advisor', [
        'api_version' => 3,
        'title' => 'Tư vấn chọn thiết bị',
        'category' => 'woocommerce',
        'supports' => ['html' => false],
        'render_callback' => 'bepduc_render_advisor',
    ]);
}
add_action('init', 'bepduc_register_advisor_block');
