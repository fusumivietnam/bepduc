<?php
/**
 * QA-only mail sink. Copied into the disposable local WordPress mu-plugins
 * directory by CI. No emails leave the isolated test environment.
 */
declare(strict_types=1);
if (! defined('ABSPATH') || wp_get_environment_type() !== 'local') {
    return;
}
add_filter('pre_wp_mail', static function ($return, array $args) {
    $events = get_option('bepduc_checkout_e2e_mail_events', []);
    if (! is_array($events)) { $events = []; }
    $events[] = [
        'to' => is_array($args['to'] ?? '') ? implode(',', $args['to']) : (string) ($args['to'] ?? ''),
        'subject' => (string) ($args['subject'] ?? ''),
        'timestamp' => time(),
    ];
    update_option('bepduc_checkout_e2e_mail_events', $events, false);
    return true; // WordPress considers mail sent; external transport is bypassed.
}, 10, 2);
