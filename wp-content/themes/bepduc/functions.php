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
}
add_action('after_setup_theme', 'bepduc_setup');
