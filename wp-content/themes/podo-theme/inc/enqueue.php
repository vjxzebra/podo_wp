<?php
/**
 * Стилі та скрипти.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function () {
    $dir = get_template_directory();
    $uri = get_template_directory_uri();

    wp_enqueue_style(
        'podo-main',
        $uri . '/assets/css/main.css',
        [],
        (string) filemtime($dir . '/assets/css/main.css')
    );

    wp_enqueue_script(
        'podo-main',
        $uri . '/assets/js/main.js',
        [],
        (string) filemtime($dir . '/assets/js/main.js'),
        ['in_footer' => true]
    );
});

// Прибираємо зайве зі стандартного head
add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
});
