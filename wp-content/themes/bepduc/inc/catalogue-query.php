<?php
/**
 * WooCommerce shop pages can be routed as a normal WordPress Page.
 * In that case, Product Collection must not inherit the page query.
 * Taxonomy/product archives still inherit their normal WC query context.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @param array<string, mixed> $parsed_block Parsed block data.
 * @return array<string, mixed>
 */
function bepduc_shop_collection_query(array $parsed_block): array
{
    if (($parsed_block['blockName'] ?? '') !== 'woocommerce/product-collection'
        || ! function_exists('wc_get_page_id')) {
        return $parsed_block;
    }

    $shop_page_id = wc_get_page_id('shop');
    if ($shop_page_id < 1 || ! is_page($shop_page_id)
        || is_post_type_archive('product')) {
        return $parsed_block;
    }

    // Only the Page-routed shop needs an independent Woo Product Collection.
    $parsed_block['attrs']['query']['inherit'] = false;
    $parsed_block['attrs']['query']['postType'] = 'product';
    $parsed_block['attrs']['query']['filterable'] = true;

    return $parsed_block;
}
add_filter('render_block_data', 'bepduc_shop_collection_query');
