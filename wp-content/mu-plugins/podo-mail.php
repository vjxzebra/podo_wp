<?php
/**
 * Plugin Name: Podo Mail (dev)
 * Description: У режимі розробки маршрутизує wp_mail() у MailHog (localhost:8025).
 */

defined('ABSPATH') || exit;

if (defined('WP_ENV') && WP_ENV === 'development') {
    add_action('phpmailer_init', function ($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host     = 'mailhog';
        $phpmailer->Port     = 1025;
        $phpmailer->SMTPAuth = false;
    });

    add_filter('wp_mail_from', fn() => 'dev@podo.local');
    add_filter('wp_mail_from_name', fn() => 'Podo Dev');
}
