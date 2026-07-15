<?php
/**
 * Хелпери теми.
 */

defined('ABSPATH') || exit;

/**
 * Глобальна опція сайту (ACF Options Page) з дефолтом.
 * До Фази 2 поля ще не створені — повертаються дефолти з макета.
 */
function podo_opt(string $name, string $default = ''): string {
    if (function_exists('get_field')) {
        $value = get_field($name, 'option');
        if (is_string($value) && $value !== '') {
            return $value;
        }
    }
    return $default;
}

/**
 * ACF-поле поточної сторінки з дефолтом.
 */
function podo_field(string $name, string $default = '', $post_id = false): string {
    if (function_exists('get_field')) {
        $value = get_field($name, $post_id);
        if (is_string($value) && $value !== '') {
            return $value;
        }
    }
    return $default;
}

/**
 * Телефон у форматі для tel: (лише цифри та +).
 */
function podo_tel_href(string $phone): string {
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

/**
 * URL сторінки за слагом (для внутрішніх лінків у шаблонах).
 */
function podo_page_url(string $slug): string {
    $page = get_page_by_path($slug);
    return $page ? (string) get_permalink($page) : home_url('/');
}
