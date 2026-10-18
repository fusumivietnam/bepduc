<?php
/**
 * Query-aware catalogue filters. Does not own product data.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, string>
 */
function bepduc_catalogue_filter_values(): array
{
    $allowed_sorts = ['recent', 'title', 'price-asc', 'price-desc'];
    $sort = isset($_GET['bd_sort']) && is_string($_GET['bd_sort']) ? sanitize_key(wp_unslash($_GET['bd_sort'])) : 'recent';
    if (! in_array($sort, $allowed_sorts, true)) {
        $sort = 'recent';
    }

    $stock = isset($_GET['bd_stock']) && is_string($_GET['bd_stock']) ? sanitize_key(wp_unslash($_GET['bd_stock'])) : '';
    if (! in_array($stock, ['instock', 'outofstock', ''], true)) {
        $stock = '';
    }

    $brand = isset($_GET['bd_brand']) && is_string($_GET['bd_brand']) ? sanitize_title(wp_unslash($_GET['bd_brand'])) : '';
    if ($brand !== '' && ! get_term_by('slug', $brand, 'product_brand')) {
        $brand = '';
    }

    $prices = [];
    foreach (['bd_min', 'bd_max'] as $key) {
        $raw = isset($_GET[$key]) && is_string($_GET[$key]) ? trim(wp_unslash($_GET[$key])) : '';
        // Only ASCII integer VND amounts. Disallow scientific notation, minus and fractions.
        $prices[$key] = preg_match('/^[0-9]{1,12}$/D', $raw) ? $raw : '';
    }

    return [
        'bd_sort' => $sort,
        'bd_stock' => $stock,
        'bd_brand' => $brand,
        'bd_min' => $prices['bd_min'],
        'bd_max' => $prices['bd_max'],
    ];
}

/**
 * Only modify Bepduc's native query loop when it targets the Woo product type
 * on a shop or Woo taxonomy archive. Never alter a global product query.
 *
 * @param array<string, mixed> $query WP_Query arguments.
 * @param WP_Block $block Post template context.
 * @return array<string, mixed>
 */
function bepduc_catalogue_apply_filters(array $query, WP_Block $block): array
{
    if (! function_exists('wc_get_page_id')) {
        return $query;
    }

    $shop_id = wc_get_page_id('shop');
    $on_catalogue = ($shop_id > 0 && is_page($shop_id))
        || is_post_type_archive('product')
        || is_tax(['product_cat', 'product_brand']);
    if (! $on_catalogue || ($block->context['query']['postType'] ?? '') !== 'product') {
        return $query;
    }

    $values = bepduc_catalogue_filter_values();
    $query['post_type'] = 'product';
    $query['post_status'] = 'publish';
    $query['posts_per_page'] = 12;
    // Honour archive taxonomy context rather than changing the main query.
    if (is_tax(['product_cat', 'product_brand'])) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $tax_query = isset($query['tax_query']) && is_array($query['tax_query']) ? $query['tax_query'] : [];
            $tax_query[] = ['taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => [$term->term_id]];
            $query['tax_query'] = $tax_query;
        }
    }

    if ($values['bd_brand'] !== '') {
        $tax_query = isset($query['tax_query']) && is_array($query['tax_query']) ? $query['tax_query'] : [];
        $tax_query[] = [
            'taxonomy' => 'product_brand',
            'field' => 'slug',
            'terms' => [$values['bd_brand']],
        ];
        $query['tax_query'] = $tax_query;
    }

    $meta = isset($query['meta_query']) && is_array($query['meta_query']) ? $query['meta_query'] : [];
    if ($values['bd_stock'] !== '') {
        $meta[] = [
            'key' => '_stock_status',
            'value' => $values['bd_stock'],
        ];
    }
    if ($values['bd_min'] !== '' || $values['bd_max'] !== '') {
        $min = $values['bd_min'] !== '' ? (int) $values['bd_min'] : 0;
        $max = $values['bd_max'] !== '' ? (int) $values['bd_max'] : PHP_INT_MAX;
        if ($max < $min) {
            $max = $min;
        }
        $meta[] = [
            'key' => '_price',
            'value' => [$min, $max],
            'compare' => 'BETWEEN',
            'type' => 'DECIMAL(20,2)',
        ];
    }
    if ($meta) {
        $query['meta_query'] = $meta;
    }

    switch ($values['bd_sort']) {
        case 'price-asc':
        case 'price-desc':
            $query['meta_key'] = '_price';
            $query['orderby'] = 'meta_value_num';
            $query['order'] = $values['bd_sort'] === 'price-asc' ? 'ASC' : 'DESC';
            break;
        case 'title':
            $query['orderby'] = 'title';
            $query['order'] = 'ASC';
            break;
        default:
            $query['orderby'] = 'date';
            $query['order'] = 'DESC';
    }

    return $query;
}
add_filter('query_loop_block_query_vars', 'bepduc_catalogue_apply_filters', 10, 2);

/**
 * Render a keyboard-friendly GET form. All values are encoded and escaped.
 *
 * @return string
 */
function bepduc_render_catalogue_controls(): string
{
    if (! taxonomy_exists('product_brand')) {
        return '';
    }

    $values = bepduc_catalogue_filter_values();
    $brands = get_terms(['taxonomy' => 'product_brand', 'hide_empty' => true]);
    if (is_wp_error($brands)) {
        $brands = [];
    }

    $action = get_pagenum_link(1);
    $html = '<form class="bd-catalogue-form" role="search" method="get" action="' . esc_url($action) . '">';
    $html .= '<div class="bd-catalogue-fields">';
    $html .= '<label for="bd-brand">Thương hiệu</label><select id="bd-brand" name="bd_brand"><option value="">Tất cả</option>';
    foreach ($brands as $brand) {
        $html .= '<option value="' . esc_attr($brand->slug) . '"' . selected($values['bd_brand'], $brand->slug, false) . '>' . esc_html($brand->name) . '</option>';
    }
    $html .= '</select>';
    $html .= '<label for="bd-stock">Tình trạng</label><select id="bd-stock" name="bd_stock">';
    foreach (['' => 'Tất cả', 'instock' => 'Còn hàng', 'outofstock' => 'Hết hàng'] as $slug => $label) {
        $html .= '<option value="' . esc_attr($slug) . '"' . selected($values['bd_stock'], $slug, false) . '>' . esc_html($label) . '</option>';
    }
    $html .= '</select>';
    foreach (['bd_min' => 'Giá từ (₫)', 'bd_max' => 'Giá đến (₫)'] as $field => $label) {
        $html .= '<label for="' . esc_attr($field) . '">' . esc_html($label) . '</label>';
        $html .= '<input id="' . esc_attr($field) . '" type="number" min="0" step="1" inputmode="numeric" name="' . esc_attr($field) . '" value="' . esc_attr($values[$field]) . '">';
    }
    $html .= '<label for="bd-sort">Sắp xếp</label><select id="bd-sort" name="bd_sort">';
    foreach (['recent' => 'Mới nhất', 'title' => 'Tên A–Z', 'price-asc' => 'Giá tăng dần', 'price-desc' => 'Giá giảm dần'] as $slug => $label) {
        $html .= '<option value="' . esc_attr($slug) . '"' . selected($values['bd_sort'], $slug, false) . '>' . esc_html($label) . '</option>';
    }
    $html .= '</select></div>';
    $html .= '<button class="wp-element-button" type="submit">Áp dụng bộ lọc</button>';
    $html .= '<a class="bd-catalogue-reset" href="' . esc_url($action) . '">Xóa bộ lọc</a>';
    $html .= '</form>';

    return $html;
}

function bepduc_register_catalogue_controls(): void
{
    register_block_type('bepduc/catalogue-controls', [
        'api_version' => 3,
        'title' => __('Bộ lọc sản phẩm', 'bepduc'),
        'category' => 'woocommerce',
        'supports' => ['html' => false],
        'render_callback' => 'bepduc_render_catalogue_controls',
    ]);
}
add_action('init', 'bepduc_register_catalogue_controls');
