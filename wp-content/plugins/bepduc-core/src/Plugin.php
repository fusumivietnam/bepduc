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

        // Catalogue setup is explicit: no database changes on activation/page views.
        if (defined('WP_CLI') && WP_CLI) {
            require_once BEPDUC_CORE_DIR . 'src/Catalog/Schema.php';
            require_once BEPDUC_CORE_DIR . 'src/Catalog/Command.php';
            \WP_CLI::add_command('bepduc catalog', \Bepduc\Core\Catalog\Command::class);
            require_once BEPDUC_CORE_DIR . 'src/Seed/Manifest.php';
            require_once BEPDUC_CORE_DIR . 'src/Seed/Command.php';
            \WP_CLI::add_command('bepduc seed', \Bepduc\Core\Seed\Command::class);
        }
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
