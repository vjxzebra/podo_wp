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

/**
 * Приблизний час читання статті («5 хв»).
 */
function podo_read_time(int $post_id): string {
    $text  = wp_strip_all_tags((string) get_post_field('post_content', $post_id));
    $words = preg_match_all('/[\p{L}\p{N}]+/u', $text);
    $mins  = max(1, (int) ceil((int) $words / 200));
    /* translators: %d — хвилини читання */
    return sprintf(__('%d хв', 'podo'), $mins);
}

/**
 * Карта на сторінці контактів: санітизований iframe з опції map_embed.
 * Дозволяються лише вбудовані карти Google (google.com/maps/embed).
 */
function podo_map_embed(): string {
    $raw = podo_opt('map_embed');

    if ($raw !== '' && preg_match('/src=["\']([^"\']+)["\']/', $raw, $m)) {
        $src = html_entity_decode($m[1], ENT_QUOTES);
        $host = wp_parse_url($src, PHP_URL_HOST) ?: '';
        $path = wp_parse_url($src, PHP_URL_PATH) ?: '';
        $is_google_embed = preg_match('/(^|\.)google\.[a-z.]+$/i', $host) && str_starts_with($path, '/maps');
        if ($is_google_embed) {
            return '<div class="map-box"><iframe src="' . esc_url($src) . '" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="' . esc_attr__('Карта проїзду', 'podo') . '"></iframe></div>';
        }
    }

    return '<div class="map-box ph"><span>' . esc_html__('карта Google Maps', 'podo') . '</span></div>';
}

/**
 * Чи ввімкнена reCAPTCHA для форми заявки (потрібні обидва ключі).
 */
function podo_recaptcha_enabled(): bool {
    return podo_opt('recaptcha_site_key') !== '' && podo_opt('recaptcha_secret_key') !== '';
}

/**
 * Repeater-поле з фолбеком (дефолти з макета до наповнення контентом).
 */
function podo_repeater(string $name, array $default, $post_id = false): array {
    if (function_exists('get_field')) {
        $value = get_field($name, $post_id);
        if (is_array($value) && $value !== []) {
            return $value;
        }
    }
    return $default;
}
