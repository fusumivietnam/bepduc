<?php
/** Storefront home: display Woo products and WordPress pages, never demo claims. */
declare(strict_types=1);
if (! defined('ABSPATH')) { exit; }

function bepduc_home_link(string $slug): string
{
    $page = get_page_by_path($slug, OBJECT, 'page');
    return $page instanceof WP_Post && $page->post_status === 'publish'
        ? get_permalink($page) : home_url('/shop/');
}

function bepduc_render_homepage(): string
{
    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $compare = bepduc_home_link('so-sanh');
    $advisor = bepduc_home_link('tu-van-chon-bep');

    $html = '<div class="bd-home">';
    $html .= '<section class="bd-home__hero"><div class="bd-home__hero-copy"><p class="bd-home__eyebrow">'
        . esc_html__('THIẾT BỊ BẾP CAO CẤP', 'bepduc') . '</p><h1>'
        . esc_html__('Nâng tầm trải nghiệm trong căn bếp Việt', 'bepduc') . '</h1><p>'
        . esc_html__('Khám phá thiết bị bếp, đối chiếu thông số thực tế và chọn giải pháp phù hợp với không gian của bạn.', 'bepduc')
        . '</p><div class="bd-home__actions"><a class="bd-home__primary" href="' . esc_url($shop) . '">'
        . esc_html__('Khám phá sản phẩm', 'bepduc') . '</a><a class="bd-home__secondary" href="'
        . esc_url($advisor) . '">' . esc_html__('Tư vấn chọn thiết bị', 'bepduc')
        . '</a></div></div><div class="bd-home__hero-art" role="img" aria-label="' . esc_attr__('Vị trí ảnh không gian bếp, chờ ảnh được cấp phép', 'bepduc') . '">'
        . '<span class="bd-home__photo-caption">' . esc_html__('Ảnh không gian bếp sẽ được cập nhật', 'bepduc') . '</span></div></section>';

    $html .= '<section class="bd-home__section" aria-labelledby="bd-home-categories"><div class="bd-home__section-head"><div>'
        . '<p class="bd-home__eyebrow">' . esc_html__('KHÁM PHÁ DANH MỤC', 'bepduc') . '</p>'
        . '<h2 id="bd-home-categories">' . esc_html__('Danh mục thiết bị', 'bepduc')
        . '</h2></div><a href="' . esc_url($shop) . '">' . esc_html__('Xem tất cả sản phẩm →', 'bepduc') . '</a></div>';
    $html .= '<div class="bd-home__categories">';
    if (taxonomy_exists('product_cat')) {
        $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 6, 'orderby' => 'count', 'order' => 'DESC']);
        if (! is_wp_error($terms)) {
            foreach ($terms as $term) {
                $url = get_term_link($term);
                if (is_wp_error($url)) { continue; }
                $html .= '<a class="bd-home__category" href="' . esc_url($url) . '"><span class="bd-home__category-index" aria-hidden="true">◌</span>'
                    . '<span>' . esc_html($term->name) . '</span><span aria-hidden="true">↗</span></a>';
            }
        }
    }
    $html .= '</div></section>';

    $html .= '<section class="bd-home__section" aria-labelledby="bd-home-products"><div class="bd-home__section-head"><div>'
        . '<p class="bd-home__eyebrow">' . esc_html__('BỘ SƯU TẬP', 'bepduc') . '</p>'
        . '<h2 id="bd-home-products">' . esc_html__('Sản phẩm nổi bật', 'bepduc')
        . '</h2></div><a href="' . esc_url($shop) . '">' . esc_html__('Đến cửa hàng →', 'bepduc') . '</a></div>';
    $html .= '<div class="bd-home__products">';
    if (function_exists('wc_get_products')) {
        $products = wc_get_products(['status' => 'publish', 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC', 'visibility' => 'visible']);
        foreach ($products as $product) {
            if (! $product instanceof WC_Product || $product->get_catalog_visibility() === 'hidden') { continue; }
            $html .= '<article class="bd-home__product"><a href="' . esc_url(get_permalink($product->get_id())) . '">'
                . '<span class="bd-home__product-image">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</span>'
                . '<span class="bd-home__product-name">' . esc_html($product->get_name()) . '</span></a>'
                . '<div class="bd-home__product-price">' . wp_kses_post($product->get_price_html()) . '</div></article>';
        }
    }
    $html .= '</div></section>';

    $html .= '<section class="bd-home__guides"><div><p class="bd-home__eyebrow">'
        . esc_html__('CHỌN MUA CÓ CƠ SỞ', 'bepduc') . '</p><h2>'
        . esc_html__('Chọn thiết bị dựa trên dữ liệu thực', 'bepduc') . '</h2><p>'
        . esc_html__('So sánh sản phẩm theo dữ liệu WooCommerce hoặc lọc theo loại thiết bị và ngân sách. Kiểm tra tài liệu nhà sản xuất trước khi lắp đặt.', 'bepduc')
        . '</p></div><div class="bd-home__guide-links"><a href="' . esc_url($compare) . '">'
        . esc_html__('So sánh thiết bị', 'bepduc') . ' <span aria-hidden="true">↗</span></a><a href="'
        . esc_url($advisor) . '">' . esc_html__('Tư vấn chọn bếp', 'bepduc')
        . ' <span aria-hidden="true">↗</span></a></div></section>';
    return $html . '</div>';
}

add_action('init', static function (): void {
    register_block_type('bepduc/homepage', [
        'api_version' => 3,
        'title' => 'Trang chủ Bếp Đức',
        'category' => 'widgets',
        'supports' => ['html' => false],
        'render_callback' => 'bepduc_render_homepage',
    ]);
});
