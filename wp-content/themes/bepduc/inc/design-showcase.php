<?php
/**
 * QA-only, read-only component specimens. Never registered on production.
 * All product fields come from WooCommerce; no demo prices or stock claims.
 */
declare(strict_types=1);
if (! defined('ABSPATH')) { exit; }

function bepduc_ui_kit_allowed(): bool
{
    return in_array(wp_get_environment_type(), ['local', 'development', 'staging'], true);
}

function bepduc_ui_kit_shortcode(): string
{
    if (! bepduc_ui_kit_allowed()) { return ''; }

    $html = '<main class="bd-ui-kit" aria-labelledby="bd-ui-kit-title"><header class="bd-ui-kit__intro">'
        . '<p class="bd-ui-kit__eyebrow">BẾP ĐỨC · DESIGN SYSTEM / QA</p>'
        . '<h1 id="bd-ui-kit-title">Component Showcase</h1>'
        . '<p>Trang kiểm thử giao diện nội bộ; dữ liệu sản phẩm lấy từ WooCommerce.</p></header>';

    $colors = ['canvas' => 'Canvas', 'surface' => 'Surface', 'ink' => 'Ink',
        'muted' => 'Muted', 'border' => 'Border', 'accent' => 'Accent',
        'accent-dark' => 'Accent Dark', 'success' => 'Success'];
    $html .= '<section aria-labelledby="bd-ui-colors"><h2 id="bd-ui-colors">Color tokens</h2><div class="bd-ui-kit__swatches">';
    foreach ($colors as $slug => $label) {
        $html .= '<div class="bd-ui-kit__swatch"><span class="bd-ui-kit__swatch-color bd-ui-kit__swatch-color--'
            . esc_attr($slug) . '"></span><span>' . esc_html($label) . '</span></div>';
    }
    $html .= '</div></section>';

    $shop = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
    $html .= '<section aria-labelledby="bd-ui-actions"><h2 id="bd-ui-actions">Action buttons</h2>'
        . '<div class="bd-ui-kit__actions"><a class="bd-home__primary" href="' . esc_url($shop) . '">Khám phá sản phẩm</a>'
        . '<a class="bd-home__secondary" href="' . esc_url($shop) . '">Xem cửa hàng</a>'
        . '<button type="button" disabled>Disabled</button></div></section>';

    $html .= '<section aria-labelledby="bd-ui-fields"><h2 id="bd-ui-fields">Form states</h2>'
        . '<div class="bd-ui-kit__fields"><div><label for="bd-ui-select">Danh mục thiết bị</label>'
        . '<select id="bd-ui-select"><option>Chọn danh mục</option></select></div>'
        . '<div><label for="bd-ui-disabled">Disabled</label>'
        . '<input id="bd-ui-disabled" value="Không thể chỉnh sửa" disabled></div></div></section>';

    $html .= '<section aria-labelledby="bd-ui-product"><h2 id="bd-ui-product">WooCommerce product card</h2>';
    if (function_exists('wc_get_products')) {
        $items = wc_get_products(['status' => 'publish', 'limit' => 1, 'visibility' => 'visible']);
        foreach ($items as $product) {
            if (! $product instanceof WC_Product) { continue; }
            $html .= '<article class="bd-ui-kit__product bd-product-card">'
                . '<a href="' . esc_url(get_permalink($product->get_id())) . '">'
                . '<span class="bd-ui-kit__product-image">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</span>'
                . '<span class="bd-ui-kit__product-name">' . esc_html($product->get_name()) . '</span></a>'
                . '<div class="bd-ui-kit__price">' . wp_kses_post($product->get_price_html()) . '</div>'
                . '<p class="bd-ui-kit__stock">' . esc_html($product->is_in_stock() ? __('Còn hàng', 'bepduc') : __('Hết hàng', 'bepduc')) . '</p>'
                . '</article>';
        }
    }
    $html .= '</section>';

    $html .= '<section aria-labelledby="bd-ui-editorial"><h2 id="bd-ui-editorial">Editorial card</h2>';
    $posts = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1]);
    foreach ($posts as $post) {
        $html .= '<article class="bd-ui-kit__article bd-journal__card">'
            . '<a href="' . esc_url(get_permalink($post)) . '">'
            . '<span class="bd-ui-kit__article-image">' . (has_post_thumbnail($post) ? get_the_post_thumbnail($post, 'medium') : '') . '</span>'
            . '<strong>' . esc_html(get_the_title($post)) . '</strong></a>'
            . '<p>' . esc_html(wp_trim_words(get_the_excerpt($post), 20)) . '</p></article>';
    }
    return $html . '</section></main>';
}

function bepduc_register_ui_kit(): void
{
    if (! bepduc_ui_kit_allowed()) { return; }
    add_shortcode('bepduc_design_showcase', 'bepduc_ui_kit_shortcode');
}
add_action('init', 'bepduc_register_ui_kit');

function bepduc_ui_kit_assets(): void
{
    if (! bepduc_ui_kit_allowed() || ! is_page('ui-kit')) { return; }
    foreach (['assets/css/homepage.css', 'assets/css/editorial.css', 'assets/css/ui-kit.css'] as $relative) {
        $path = get_theme_file_path($relative);
        wp_enqueue_style('bepduc-kit-' . sanitize_title(basename($relative)), get_theme_file_uri($relative),
            [], is_file($path) ? (string) filemtime($path) : '0.1.0');
    }
}
add_action('wp_enqueue_scripts', 'bepduc_ui_kit_assets');
