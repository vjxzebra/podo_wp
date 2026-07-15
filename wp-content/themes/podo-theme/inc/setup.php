<?php
/**
 * Базове налаштування теми.
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', function () {
    load_theme_textdomain('podo', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => __('Головне меню', 'podo'),
    ]);

    add_image_size('podo-card', 720, 450, true);   // картки статей 16/10
    add_image_size('podo-portrait', 640, 800, true); // портрети 4/5
});

// Безпека: XML-RPC не використовується — блокуємо ендпоінт повністю
add_filter('xmlrpc_enabled', '__return_false');
add_action('init', function () {
    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
        http_response_code(403);
        exit;
    }
});
