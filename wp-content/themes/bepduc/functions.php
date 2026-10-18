<?php
/**
 * Theme bootstrap.
 *
 * @package Bepduc
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register theme support without duplicating WooCommerce business logic.
 */
function bepduc_setup(): void
{
    add_theme_support('woocommerce');
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    add_editor_style('assets/css/catalogue.css');
}
add_action('after_setup_theme', 'bepduc_setup');

// A presentation-only block that reads WooCommerce native brand terms.
require_once get_theme_file_path('inc/product-brand.php');
require_once get_theme_file_path('inc/catalogue-query.php');

/**
 * Enqueue light catalogue styling only on WooCommerce product archives.
 */
function bepduc_catalogue_assets(): void
{
    if (! function_exists('is_shop')) {
        return;
    }

    $shop_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : -1;
    if (! is_shop() && ! is_product_category() && ! is_product_tag()
        && ! is_tax('product_brand') && ! is_post_type_archive('product')
        && ! ($shop_page_id > 0 && is_page($shop_page_id))) {
        return;
    }

    $relative = 'assets/css/catalogue.css';
    $path = get_theme_file_path($relative);
    wp_enqueue_style(
        'bepduc-catalogue',
        get_theme_file_uri($relative),
        [],
        is_file($path) ? (string) filemtime($path) : '0.1.0'
    );
}
add_action('wp_enqueue_scripts', 'bepduc_catalogue_assets');
