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
    add_editor_style('assets/css/product-detail.css');
}
add_action('after_setup_theme', 'bepduc_setup');

// A presentation-only block that reads WooCommerce native brand terms.
require_once get_theme_file_path('inc/product-brand.php');
require_once get_theme_file_path('inc/catalogue-query.php');
require_once get_theme_file_path('inc/catalogue-filters.php');
require_once get_theme_file_path('inc/product-detail.php');
require_once get_theme_file_path('inc/product-compare.php');
require_once get_theme_file_path('inc/buying-advisor.php');
require_once get_theme_file_path('inc/homepage.php');

/**
 * Enqueue light catalogue styling only on WooCommerce product archives.
 */
function bepduc_catalogue_assets(): void
{
    // Small static style sheet; load globally so Woo's Page-routed shops work
    // even when conditional tags do not classify the request as an archive.
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

/** Load only the single-product stylesheet on product detail pages. */
function bepduc_product_detail_assets(): void
{
    if (! is_singular('product')) {
        return;
    }
    $relative = 'assets/css/product-detail.css';
    $path = get_theme_file_path($relative);
    wp_enqueue_style('bepduc-product-detail', get_theme_file_uri($relative), [], is_file($path) ? (string) filemtime($path) : '0.1.0');
}
add_action('wp_enqueue_scripts', 'bepduc_product_detail_assets');

/** Load comparison styling only on comparison page and product pages with compare CTA. */
function bepduc_compare_assets(): void
{
    if (! is_page('so-sanh') && ! is_singular('product')) {
        return;
    }
    $relative = 'assets/css/product-compare.css';
    $path = get_theme_file_path($relative);
    wp_enqueue_style('bepduc-product-compare', get_theme_file_uri($relative), [], is_file($path) ? (string) filemtime($path) : '0.1.0');
}
add_action('wp_enqueue_scripts', 'bepduc_compare_assets');

/** Only load advisor presentation on its dedicated page. */
function bepduc_buying_advisor_assets(): void
{
    if (! is_page('tu-van-chon-bep')) {
        return;
    }
    $relative = 'assets/css/buying-advisor.css';
    $path = get_theme_file_path($relative);
    wp_enqueue_style('bepduc-buying-advisor', get_theme_file_uri($relative), [], is_file($path) ? (string) filemtime($path) : '0.1.0');
}
add_action('wp_enqueue_scripts', 'bepduc_buying_advisor_assets');

/** Homepage styles only on the public front page. */
function bepduc_homepage_assets(): void
{
    if (! is_front_page()) { return; }
    $relative = 'assets/css/homepage.css';
    $path = get_theme_file_path($relative);
    wp_enqueue_style('bepduc-homepage', get_theme_file_uri($relative), [], is_file($path) ? (string) filemtime($path) : '0.1.0');
}
add_action('wp_enqueue_scripts', 'bepduc_homepage_assets');
