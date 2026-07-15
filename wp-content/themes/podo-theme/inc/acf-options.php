<?php
/**
 * ACF: сторінка опцій.
 * Групи полів живуть у acf-json/ (Local JSON, синхронізуються через git).
 */

defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_options_page')) {
        return;
    }
    acf_add_options_page([
        'page_title' => __('Налаштування сайту', 'podo'),
        'menu_title' => __('Налаштування сайту', 'podo'),
        'menu_slug'  => 'podo-settings',
        'capability' => 'manage_options',
        'position'   => 59,
        'icon_url'   => 'dashicons-admin-generic',
        'redirect'   => false,
    ]);
});
