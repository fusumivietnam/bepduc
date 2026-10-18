<?php
/**
 * Strict synthetic seed manifest validation.
 *
 * @package BepducCore
 */

declare(strict_types=1);

namespace Bepduc\Core\Seed;

final class Manifest
{
    public static function load(): array
    {
        $file = BEPDUC_CORE_DIR . 'config/demo-products.json';
        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new \RuntimeException('Cannot read demo product manifest.');
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid demo product JSON.', 0, $exception);
        }

        if (!is_array($data) || ($data['schema_version'] ?? null) !== 1
            || ($data['marker'] ?? null) !== 'bepduc-demo-v1'
            || !isset($data['products'], $data['brands'], $data['terms'])
            || !is_array($data['products']) || !is_array($data['brands']) || !is_array($data['terms'])) {
            throw new \RuntimeException('Unsupported or incomplete demo product manifest.');
        }

        $catalogue = \Bepduc\Core\Catalog\Schema::load();
        $categories = array_column($catalogue['categories'], 'slug');
        $attributes = array_column($catalogue['attributes'], 'slug');
        $brandSlugs = [];
        $termSlugs = [];

        foreach ($data['brands'] as $brand) {
            self::assertTerm($brand, 'brand');
            if (isset($brandSlugs[$brand['slug']])) {
                throw new \RuntimeException('Duplicate demo brand: ' . $brand['slug']);
            }
            $brandSlugs[$brand['slug']] = true;
        }

        foreach ($data['terms'] as $taxonomy => $terms) {
            if (!in_array($taxonomy, $attributes, true) || !is_array($terms)) {
                throw new \RuntimeException('Unknown global demo attribute: ' . $taxonomy);
            }
            $termSlugs[$taxonomy] = [];
            foreach ($terms as $term) {
                self::assertTerm($term, $taxonomy);
                if (isset($termSlugs[$taxonomy][$term['slug']])) {
                    throw new \RuntimeException('Duplicate demo term: ' . $taxonomy . '/' . $term['slug']);
                }
                $termSlugs[$taxonomy][$term['slug']] = true;
            }
        }

        if (count($data['products']) !== 24) {
            throw new \RuntimeException('Demo manifest must contain exactly 24 parent products.');
        }

        $skus = [];
        foreach ($data['products'] as $row) {
            foreach (['sku', 'title', 'category', 'brand', 'type', 'attributes'] as $key) {
                if (!isset($row[$key])) {
                    throw new \RuntimeException('Missing demo product field: ' . $key);
                }
            }
            if (!is_string($row['sku']) || !preg_match('/^BD-DEMO-[0-9]{3}$/', $row['sku'])
                || !is_string($row['title']) || strpos($row['title'], '[DEMO] ') !== 0
                || !in_array($row['category'], $categories, true)
                || !isset($brandSlugs[$row['brand']])
                || !in_array($row['type'], ['simple', 'variable'], true)
                || !is_array($row['attributes'])) {
                throw new \RuntimeException('Invalid demo product: ' . (string) $row['sku']);
            }

            self::uniqueSku($row['sku'], $skus);
            if (!in_array($row['visibility'] ?? null, ['visible', 'hidden'], true)
                || !is_bool($row['featured'] ?? null)) {
                throw new \RuntimeException('Invalid demo visibility/featured: ' . $row['sku']);
            }

            foreach ($row['attributes'] as $taxonomy => $slugs) {
                $values = is_array($slugs) ? $slugs : [$slugs];
                if (!isset($termSlugs[$taxonomy]) || !$values) {
                    throw new \RuntimeException('Unknown demo attribute on ' . $row['sku']);
                }
                foreach ($values as $slug) {
                    if (!is_string($slug) || !isset($termSlugs[$taxonomy][$slug])) {
                        throw new \RuntimeException('Missing canonical attribute term on ' . $row['sku']);
                    }
                }
            }

            if ($row['type'] === 'simple') {
                if (isset($row['variants']) || !self::money($row['regular_price'] ?? null)
                    || (isset($row['sale_price']) && (
                        !self::money($row['sale_price'])
                        || (int) $row['sale_price'] >= (int) $row['regular_price']
                    ))
                    || !self::stock($row['stock_quantity'] ?? null)) {
                    throw new \RuntimeException('Invalid simple demo data: ' . $row['sku']);
                }
            } else {
                if (!empty($row['regular_price']) || !isset($row['variants'])
                    || !is_array($row['variants']) || count($row['variants']) < 2
                    || !is_array($row['attributes']['color'] ?? null)) {
                    throw new \RuntimeException('Invalid variable demo parent: ' . $row['sku']);
                }
                foreach ($row['variants'] as $variant) {
                    if (!is_array($variant) || !isset($variant['sku'], $variant['color'])
                        || !preg_match('/^BD-DEMO-[0-9]{3}-[A-Z]+$/', $variant['sku'])
                        || !in_array($variant['color'], $row['attributes']['color'], true)
                        || !self::money($variant['regular_price'] ?? null)
                        || !self::stock($variant['stock_quantity'] ?? null)) {
                        throw new \RuntimeException('Invalid demo variation on: ' . $row['sku']);
                    }
                    self::uniqueSku($variant['sku'], $skus);
                }
            }
        }

        return $data;
    }

    private static function assertTerm($term, string $context): void
    {
        if (!is_array($term) || !isset($term['slug'], $term['name'])
            || !is_string($term['slug']) || !is_string($term['name'])
            || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $term['slug'])
            || trim($term['name']) === '') {
            throw new \RuntimeException('Invalid demo term in ' . $context);
        }
    }

    private static function money($value): bool
    {
        return is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1;
    }

    private static function stock($value): bool
    {
        return is_int($value) && $value >= 0;
    }

    private static function uniqueSku(string $sku, array &$known): void
    {
        if (isset($known[$sku])) {
            throw new \RuntimeException('Duplicate demo SKU: ' . $sku);
        }
        $known[$sku] = true;
    }
}
