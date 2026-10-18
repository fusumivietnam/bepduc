<?php
/**
 * Development-only WooCommerce product fixtures. No production writes.
 *
 * @package BepducCore
 */
declare(strict_types=1);

namespace Bepduc\Core\Seed;

final class Command
{
    private const MARKER = 'bepduc-demo-v1';

    /**
     * Create 24 synthetic parent products and two variations.
     *
     * [--allow-staging] : Explicitly allow staging writes.
     */
    public function products(array $args, array $assoc_args): void
    {
        $environment = wp_get_environment_type();
        if (!in_array($environment, ['local', 'development', 'staging'], true)) {
            \WP_CLI::error('Demo writes forbidden on production or unknown environment; no override.');
        }
        if ($environment === 'staging' && !isset($assoc_args['allow-staging'])) {
            \WP_CLI::error('Staging demo writes require --allow-staging.');
        }
        $this->assertWoo();
        $data = $this->data();
        $this->assertCatalogue($data);
        $this->preflight($data);
        $this->createTerms($data);
        $created = 0;
        $skipped = 0;
        $newVariants = 0;
        foreach ($data['products'] as $row) {
            $id = wc_get_product_id_by_sku($row['sku']);
            if ($id) {
                $skipped++;
            } else {
                $product = $row['type'] === 'variable' ? new \WC_Product_Variable() : new \WC_Product_Simple();
                $product->set_name($row['title']);
                $product->set_sku($row['sku']);
                $product->set_status('publish');
                $product->set_catalog_visibility($row['visibility']);
                $product->set_featured($row['featured']);
                $product->set_description('SẢN PHẨM DEMO: dữ liệu giả lập, không phải hàng đang bán.');
                $product->set_short_description('Dữ liệu mẫu phục vụ thiết kế và kiểm thử.');
                $product->set_category_ids([$this->termId($row['category'], 'product_cat')]);
                $product->set_attributes($this->buildAttributes($row));
                if ($row['type'] === 'simple') {
                    $product->set_regular_price($row['regular_price']);
                    $sale = $row['sale_price'] ?? '';
                    $product->set_sale_price($sale);
                    $product->set_price($sale !== '' ? $sale : $row['regular_price']);
                    $this->stock($product, $row['stock_quantity']);
                } else {
                    $product->set_manage_stock(false);
                    $product->set_default_attributes(['pa_color' => $row['attributes']['color'][0]]);
                }
                $product->update_meta_data('_bd_demo_fixture', self::MARKER);
                $product->update_meta_data('_bd_demo_fixture_hash', $this->hash($row));
                $id = $product->save();
                $assigned = wp_set_object_terms($id, [$this->termId($row['brand'], 'product_brand')], 'product_brand');
                if (is_wp_error($assigned)) {
                    \WP_CLI::error('Cannot assign demo brand: ' . $assigned->get_error_message());
                }
                $created++;
            }

            if ($row['type'] === 'variable') {
                foreach ($row['variants'] as $childRow) {
                    if (wc_get_product_id_by_sku($childRow['sku'])) {
                        continue;
                    }
                    $child = new \WC_Product_Variation();
                    $child->set_parent_id((int) $id);
                    $child->set_name($row['title'] . ' — ' . $childRow['color']);
                    $child->set_sku($childRow['sku']);
                    $child->set_status('publish');
                    $child->set_attributes(['pa_color' => $childRow['color']]);
                    $child->set_regular_price($childRow['regular_price']);
                    $child->set_price($childRow['regular_price']);
                    $this->stock($child, $childRow['stock_quantity']);
                    $child->update_meta_data('_bd_demo_fixture', self::MARKER);
                    $child->update_meta_data('_bd_demo_fixture_hash', $this->hash($childRow));
                    $child->save();
                    $newVariants++;
                }
                \WC_Product_Variable::sync((int) $id);
            }
        }
        $this->assertRecords($data);
        \WP_CLI::success(sprintf('24 demo parents ready: %d created, %d kept; %d variations created.',
            $created, $skipped, $newVariants));
    }

    /**
     * Read-only verification. Safe on any WP environment.
     */
    public function verify(array $args, array $assoc_args): void
    {
        $this->assertWoo();
        $data = $this->data();
        $this->assertCatalogue($data);
        $this->assertRecords($data);
        \WP_CLI::success('Verified 24 demo products and 2 variations.');
    }

    private function data(): array
    {
        try {
            return Manifest::load();
        } catch (\Throwable $e) {
            \WP_CLI::error($e->getMessage());
        }
    }

    private function assertWoo(): void
    {
        if (!class_exists(\WC_Product_Simple::class) || !class_exists(\WC_Product_Variation::class)
            || !taxonomy_exists('product_cat') || !taxonomy_exists('product_brand')) {
            \WP_CLI::error('WooCommerce and native product_brand must be available.');
        }
    }

    private function assertCatalogue(array $data): void
    {
        $catalog = \Bepduc\Core\Catalog\Schema::load();
        foreach ($catalog['categories'] as $category) {
            if (!get_term_by('slug', $category['slug'], 'product_cat')) {
                \WP_CLI::error('Missing category. Run wp bepduc catalog sync in a separate request.');
            }
        }
        foreach (array_keys($data['terms']) as $name) {
            if (!taxonomy_exists('pa_' . $name) || wc_attribute_taxonomy_id_by_name('pa_' . $name) < 1) {
                \WP_CLI::error('Missing registered Woo attribute pa_' . $name);
            }
        }
    }

    private function preflight(array $data): void
    {
        foreach ($data['brands'] as $brand) {
            $term = get_term_by('slug', $brand['slug'], 'product_brand');
            if ($term && ($term->name !== $brand['name']
                || get_term_meta($term->term_id, '_bd_demo_fixture', true) !== self::MARKER)) {
                \WP_CLI::error('Demo brand collision: ' . $brand['slug']);
            }
        }
        foreach ($data['terms'] as $taxonomy => $terms) {
            foreach ($terms as $entry) {
                $term = get_term_by('slug', $entry['slug'], 'pa_' . $taxonomy);
                if ($term && $term->name !== $entry['name']) {
                    \WP_CLI::error('Attribute term name collision: pa_' . $taxonomy . '/' . $entry['slug']);
                }
            }
        }
        foreach ($data['products'] as $row) {
            $parent = $this->managed($row['sku'], $row, $row['type']);
            if ($parent) {
                $this->assertParent($parent, $row);
            }
            foreach ($row['variants'] ?? [] as $variant) {
                $child = $this->managed($variant['sku'], $variant, 'variation');
                if ($child && (!$parent || $child->get_parent_id() !== $parent->get_id())) {
                    \WP_CLI::error('Orphaned/foreign demo variation: ' . $variant['sku']);
                }
            }
        }
    }

    private function createTerms(array $data): void
    {
        foreach ($data['brands'] as $brand) {
            if (get_term_by('slug', $brand['slug'], 'product_brand')) {
                continue;
            }
            $created = wp_insert_term($brand['name'], 'product_brand', ['slug' => $brand['slug']]);
            if (is_wp_error($created)) {
                \WP_CLI::error('Cannot create demo brand: ' . $created->get_error_message());
            }
            update_term_meta((int) $created['term_id'], '_bd_demo_fixture', self::MARKER);
        }
        foreach ($data['terms'] as $taxonomy => $terms) {
            foreach ($terms as $entry) {
                if (get_term_by('slug', $entry['slug'], 'pa_' . $taxonomy)) {
                    continue;
                }
                $created = wp_insert_term($entry['name'], 'pa_' . $taxonomy, ['slug' => $entry['slug']]);
                if (is_wp_error($created)) {
                    \WP_CLI::error('Cannot create demo term: ' . $created->get_error_message());
                }
            }
        }
    }

    private function buildAttributes(array $row): array
    {
        $items = [];
        foreach ($row['attributes'] as $name => $slugs) {
            $taxonomy = 'pa_' . $name;
            $values = is_array($slugs) ? $slugs : [$slugs];
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
            $attribute->set_name($taxonomy);
            $attribute->set_options(array_map(fn (string $slug): int => $this->termId($slug, $taxonomy), $values));
            $attribute->set_visible(true);
            $attribute->set_variation($row['type'] === 'variable' && $name === 'color');
            $items[] = $attribute;
        }
        return $items;
    }

    private function termId(string $slug, string $taxonomy): int
    {
        $term = get_term_by('slug', $slug, $taxonomy);
        if (!$term) {
            \WP_CLI::error('Missing taxonomy term ' . $taxonomy . '/' . $slug);
        }
        return (int) $term->term_id;
    }

    private function stock(\WC_Product $product, int $qty): void
    {
        $product->set_manage_stock(true);
        $product->set_stock_quantity($qty);
        $product->set_stock_status($qty > 0 ? 'instock' : 'outofstock');
    }

    private function hash(array $row): string
    {
        return hash('sha256', wp_json_encode($row));
    }

    private function managed(string $sku, array $row, string $type): ?\WC_Product
    {
        $id = wc_get_product_id_by_sku($sku);
        if (!$id) {
            return null;
        }
        $product = wc_get_product($id);
        if (!$product || $product->get_type() !== $type
            || $product->get_meta('_bd_demo_fixture', true) !== self::MARKER
            || $product->get_meta('_bd_demo_fixture_hash', true) !== $this->hash($row)) {
            \WP_CLI::error('SKU is unmanaged or fixture has changed: ' . $sku);
        }
        return $product;
    }

    private function assertRecords(array $data): void
    {
        foreach ($data['brands'] as $brand) {
            $term = get_term_by('slug', $brand['slug'], 'product_brand');
            if (!$term || $term->name !== $brand['name']
                || get_term_meta($term->term_id, '_bd_demo_fixture', true) !== self::MARKER) {
                \WP_CLI::error('Demo brand missing or drifted: ' . $brand['slug']);
            }
        }
        foreach ($data['terms'] as $name => $entries) {
            foreach ($entries as $entry) {
                $term = get_term_by('slug', $entry['slug'], 'pa_' . $name);
                if (!$term || $term->name !== $entry['name']) {
                    \WP_CLI::error('Demo attribute term missing or drifted: pa_' . $name . '/' . $entry['slug']);
                }
            }
        }
        foreach ($data['products'] as $row) {
            $parent = $this->managed($row['sku'], $row, $row['type']);
            if (!$parent) {
                \WP_CLI::error('Missing demo product: ' . $row['sku']);
            }
            $this->assertParent($parent, $row);
            foreach ($row['variants'] ?? [] as $variant) {
                $child = $this->managed($variant['sku'], $variant, 'variation');
                if (!$child || $child->get_parent_id() !== $parent->get_id()
                    || ($child->get_variation_attributes()['attribute_pa_color'] ?? '') !== $variant['color']
                    || $child->get_regular_price() !== $variant['regular_price']
                    || (int) $child->get_stock_quantity() !== $variant['stock_quantity']
                    || $child->get_stock_status() !== ($variant['stock_quantity'] > 0 ? 'instock' : 'outofstock')) {
                    \WP_CLI::error('Demo variation drift: ' . $variant['sku']);
                }
            }
        }
    }

    private function assertParent(\WC_Product $p, array $row): void
    {
        if ($p->get_name() !== $row['title']
            || $p->get_status() !== 'publish'
            || $p->get_catalog_visibility() !== $row['visibility']
            || $p->get_featured() !== $row['featured']
            || $p->get_category_ids() !== [$this->termId($row['category'], 'product_cat')]
            || !has_term($row['brand'], 'product_brand', $p->get_id())
            || (int) $p->get_image_id() !== 0) {
            \WP_CLI::error('Demo core product drift: ' . $row['sku']);
        }
        if ($row['type'] === 'simple') {
            $sale = $row['sale_price'] ?? '';
            if ($p->get_regular_price() !== $row['regular_price']
                || $p->get_sale_price() !== $sale
                || $p->get_price() !== ($sale !== '' ? $sale : $row['regular_price'])
                || !$p->get_manage_stock()
                || (int) $p->get_stock_quantity() !== $row['stock_quantity']
                || $p->get_stock_status() !== ($row['stock_quantity'] > 0 ? 'instock' : 'outofstock')) {
                \WP_CLI::error('Demo price/stock drift: ' . $row['sku']);
            }
        }
        $existing = $p->get_attributes();
        if (count($existing) !== count($row['attributes'])) {
            \WP_CLI::error('Demo attribute count drift: ' . $row['sku']);
        }
        foreach ($row['attributes'] as $name => $slugs) {
            $tax = 'pa_' . $name;
            if (!isset($existing[$tax]) || !$existing[$tax]->is_taxonomy()) {
                \WP_CLI::error('Missing global demo attribute: ' . $row['sku'] . '/' . $tax);
            }
            $expected = array_map(fn (string $slug): int => $this->termId($slug, $tax),
                is_array($slugs) ? $slugs : [$slugs]);
            $actual = array_map('intval', $existing[$tax]->get_options());
            sort($actual);
            sort($expected);
            if ($actual !== $expected) {
                \WP_CLI::error('Demo attribute value drift: ' . $row['sku'] . '/' . $tax);
            }
        }
    }
}
