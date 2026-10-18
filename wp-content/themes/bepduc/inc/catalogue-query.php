<?php
/**
 * Use the WordPress native query for products with taxonomy-safe inheritance.
 *
 * @package Bepduc
 */
declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product shop may resolve as a WordPress Page; taxonomy archives must
 * preserve their main query context for correct term filtering.
 *
 * @param array<string, mixed> $parsed_block Parsed block.
 * @return array<string, mixed>
 */
function bepduc_catalogue_query_context(array $parsed_block): array
{
    if (($parsed_block['blockName'] ?? '') !== 'core/query'
        || ! in_array('bd-catalogue-collection', explode(' ', (string) ($parsed_block['attrs']['className'] ?? '')), true)) {
        return $parsed_block;
    }

    if (is_tax(['product_cat', 'product_brand', 'product_tag']) || is_post_type_archive('product')) {
        $parsed_block['attrs']['query']['inherit'] = true;
    } else {
        $parsed_block['attrs']['query']['inherit'] = false;
        $parsed_block['attrs']['query']['postType'] = 'product';
    }

    return $parsed_block;
}
add_filter('render_block_data', 'bepduc_catalogue_query_context');
