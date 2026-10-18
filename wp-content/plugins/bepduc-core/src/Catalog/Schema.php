<?php
/**
 * Versioned catalogue definitions. WooCommerce remains the data owner.
 *
 * @package BepducCore
 */

declare(strict_types=1);

namespace Bepduc\Core\Catalog;

final class Schema
{
    /**
     * @return array{schema_version: int, categories: array, attributes: array}
     */
    public static function load(): array
    {
        $path = BEPDUC_CORE_DIR . 'config/catalog.json';
        $raw = file_get_contents($path);

        if ($raw === false) {
            throw new \RuntimeException('Cannot read catalogue manifest: ' . $path);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Invalid catalogue JSON.', 0, $exception);
        }

        if (!is_array($data) || ($data['schema_version'] ?? null) !== 1) {
            throw new \RuntimeException('Unsupported catalogue schema version.');
        }

        foreach (['categories' => ['slug', 'name'], 'attributes' => ['slug', 'label']] as $group => $fields) {
            if (!isset($data[$group]) || !is_array($data[$group])) {
                throw new \RuntimeException('Missing catalogue manifest group: ' . $group);
            }

            $seen = [];
            foreach ($data[$group] as $entry) {
                if (!is_array($entry)) {
                    throw new \RuntimeException('Invalid entry in ' . $group);
                }
                foreach ($fields as $field) {
                    if (!isset($entry[$field]) || !is_string($entry[$field]) || trim($entry[$field]) === '') {
                        throw new \RuntimeException('Missing or empty ' . $field . ' in ' . $group);
                    }
                }
                $slug = $entry['slug'];
                if (!preg_match('/^[a-z0-9_]+(?:-[a-z0-9_]+)*$/', $slug)) {
                    throw new \RuntimeException('Invalid slug in ' . $group . ': ' . $slug);
                }
                if (isset($seen[$slug])) {
                    throw new \RuntimeException('Duplicate slug in ' . $group . ': ' . $slug);
                }
                $seen[$slug] = true;
            }
        }

        return $data;
    }
}
