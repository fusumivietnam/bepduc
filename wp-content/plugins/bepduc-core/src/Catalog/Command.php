<?php
/**
 * Explicit, non-destructive WooCommerce catalogue setup via WP-CLI.
 *
 * @package BepducCore
 */

declare(strict_types=1);

namespace Bepduc\Core\Catalog;

final class Command
{
    /**
     * Add only missing WooCommerce product categories and global attributes.
     *
     * No products, attribute terms, brands or existing schema are modified.
     *
     * ## OPTIONS
     *
     * [--allow-production]
     * : Explicitly permit catalogue setup in production.
     */
    public function sync(array $args, array $assoc_args): void
    {
        $this->assertWooCommerce();

        if (wp_get_environment_type() === 'production' && !isset($assoc_args['allow-production'])) {
            \WP_CLI::error('Production requires --allow-production after reviewing the manifest.');
        }

        $manifest = $this->manifest();

        // Catch drift before making any changes.
        $this->checkDefinitions($manifest, false);

        $addedCategories = 0;
        foreach ($manifest['categories'] as $category) {
            if (get_term_by('slug', $category['slug'], 'product_cat')) {
                continue;
            }

            $created = wp_insert_term($category['name'], 'product_cat', ['slug' => $category['slug']]);
            if (is_wp_error($created)) {
                \WP_CLI::error('Cannot create category ' . $category['slug'] . ': ' . $created->get_error_message());
            }
            $addedCategories++;
        }

        $addedAttributes = 0;
        foreach ($manifest['attributes'] as $attribute) {
            if ($this->findAttribute($attribute['slug']) !== null) {
                continue;
            }

            $created = wc_create_attribute([
                'name' => $attribute['label'],
                'slug' => $attribute['slug'],
                'type' => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
            ]);
            if (is_wp_error($created)) {
                \WP_CLI::error('Cannot create attribute ' . $attribute['slug'] . ': ' . $created->get_error_message());
            }
            $addedAttributes++;
        }

        $this->checkDefinitions($manifest, true);
        \WP_CLI::success(sprintf(
            'Catalogue schema v%d ready (%d categories, %d attributes created).',
            $manifest['schema_version'],
            $addedCategories,
            $addedAttributes
        ));
    }

    /**
     * Verify the installed WooCommerce catalogue against its Git manifest.
     *
     * This command never writes data.
     */
    public function verify(array $args, array $assoc_args): void
    {
        $this->assertWooCommerce();
        $manifest = $this->manifest();
        $this->checkDefinitions($manifest, true);
        \WP_CLI::success('Catalogue schema v' . $manifest['schema_version'] . ' matches Git.');
    }

    private function assertWooCommerce(): void
    {
        if (!function_exists('wc_create_attribute') || !function_exists('wc_get_attribute_taxonomies')
            || !taxonomy_exists('product_cat') || !taxonomy_exists('product_brand')) {
            \WP_CLI::error('WooCommerce with native product categories and brands must be active.');
        }
    }

    private function manifest(): array
    {
        try {
            return Schema::load();
        } catch (\Throwable $exception) {
            \WP_CLI::error($exception->getMessage());
        }
    }

    /**
     * Stop on drift. A missing entry is acceptable only in preflight sync.
     */
    private function checkDefinitions(array $manifest, bool $requireAll): void
    {
        foreach ($manifest['categories'] as $category) {
            $term = get_term_by('slug', $category['slug'], 'product_cat');
            if (!$term) {
                if ($requireAll) {
                    \WP_CLI::error('Missing category: ' . $category['slug']);
                }
                continue;
            }
            if ($term->name !== $category['name']) {
                \WP_CLI::error('Category label drift: ' . $category['slug']);
            }
        }

        foreach ($manifest['attributes'] as $attribute) {
            $current = $this->findAttribute($attribute['slug']);
            if ($current === null) {
                if ($requireAll) {
                    \WP_CLI::error('Missing attribute: pa_' . $attribute['slug']);
                }
                continue;
            }
            if ($current->attribute_label !== $attribute['label']
                || $current->attribute_type !== 'select'
                || $current->attribute_orderby !== 'menu_order'
                || (int) $current->attribute_public !== 0) {
                \WP_CLI::error('Attribute schema drift: pa_' . $attribute['slug']);
            }
        }
    }

    private function findAttribute(string $slug): ?object
    {
        foreach (wc_get_attribute_taxonomies() as $attribute) {
            if ($attribute->attribute_name === $slug) {
                return $attribute;
            }
        }

        return null;
    }
}
