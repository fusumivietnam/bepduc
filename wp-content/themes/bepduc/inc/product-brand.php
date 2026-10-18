<?php
/**
 * Product brand is WooCommerce-owned data; the theme only formats it.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Register a light server-rendered block for Woo product-brand context.
 */
function bepduc_register_brand_block(): void
{
    register_block_type('bepduc/product-brand', [
        'api_version' => 3,
        'title' => __('Thương hiệu sản phẩm', 'bepduc'),
        'category' => 'woocommerce',
        'supports' => [
            'html' => false,
        ],
        'uses_context' => ['postId', 'postType'],
        'render_callback' => 'bepduc_render_product_brand',
    ]);
}
add_action('init', 'bepduc_register_brand_block');

/**
 * @param array<string, mixed> $attributes Block attributes.
 * @param string $content Saved content.
 * @param WP_Block $block Block context holder.
 */
function bepduc_render_product_brand(array $attributes, string $content, WP_Block $block): string
{
    $post_id = isset($block->context['postId']) ? (int) $block->context['postId'] : 0;
    if ($post_id <= 0 || ! taxonomy_exists('product_brand')) {
        return '';
    }

    $brands = get_the_terms($post_id, 'product_brand');
    if (! is_array($brands) || ! $brands) {
        return '';
    }

    $brand = reset($brands);
    if (! ($brand instanceof WP_Term)) {
        return '';
    }

    $link = get_term_link($brand);
    if (is_wp_error($link)) {
        return '';
    }

    return sprintf(
        '<div class="bd-product-card__brand"><a href="%s" rel="tag">%s</a></div>',
        esc_url($link),
        esc_html($brand->name)
    );
}
