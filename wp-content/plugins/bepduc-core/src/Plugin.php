<?php
/**
 * Core plugin bootstrap.
 *
 * @package BepducCore
 */

declare(strict_types=1);

namespace Bepduc\Core;

final class Plugin
{
    /**
     * Register project-level hooks.
     */
    public static function boot(): void
    {
        add_filter('acf/settings/save_json', [self::class, 'acfSaveJsonPath']);
        add_filter('acf/settings/load_json', [self::class, 'acfLoadJsonPaths']);
    }

    /**
     * Keep ACF field-group definitions versioned with the plugin.
     */
    public static function acfSaveJsonPath(string $path): string
    {
        return BEPDUC_CORE_DIR . 'acf-json';
    }

    /**
     * Load project ACF field groups from the repository.
     *
     * @param array<int, string> $paths Existing ACF JSON lookup paths.
     * @return array<int, string>
     */
    public static function acfLoadJsonPaths(array $paths): array
    {
        $paths[] = BEPDUC_CORE_DIR . 'acf-json';

        return array_values(array_unique($paths));
    }
}
