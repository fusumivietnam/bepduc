<?php
/**
 * Plugin Name: Bepduc Core
 * Description: Project-specific domain logic and integrations for bepduc.com.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: Fusumi Vietnam
 * Text Domain: bepduc-core
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('BEPDUC_CORE_VERSION', '0.1.0');
define('BEPDUC_CORE_FILE', __FILE__);
define('BEPDUC_CORE_DIR', plugin_dir_path(__FILE__));

require_once BEPDUC_CORE_DIR . 'src/Plugin.php';

Bepduc\Core\Plugin::boot();
