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
 * Ключі reCAPTCHA: константи з .env сервера (PODO_RECAPTCHA_*) мають пріоритет
 * над опціями адмінки — на проді секрети живуть в оточенні, не в БД.
 */
function podo_recaptcha_site_key(): string {
    if (defined('PODO_RECAPTCHA_SITE_KEY') && PODO_RECAPTCHA_SITE_KEY !== '') {
        return (string) PODO_RECAPTCHA_SITE_KEY;
    }
    return podo_opt('recaptcha_site_key');
}

function podo_recaptcha_secret_key(): string {
    if (defined('PODO_RECAPTCHA_SECRET_KEY') && PODO_RECAPTCHA_SECRET_KEY !== '') {
        return (string) PODO_RECAPTCHA_SECRET_KEY;
    }
    return podo_opt('recaptcha_secret_key');
}

/**
 * Чи ввімкнена reCAPTCHA для форми заявки (потрібні обидва ключі).
 */
function podo_recaptcha_enabled(): bool {
    return podo_recaptcha_site_key() !== '' && podo_recaptcha_secret_key() !== '';
}

/**
 * Налаштування CRM для серверної відправки заявок.
 * Константи з оточення мають пріоритет над ACF, щоб за потреби тримати секрети поза БД.
 */
function podo_crm_booking_base_url(): string {
    if (defined('PODO_CRM_BOOKING_BASE_URL') && PODO_CRM_BOOKING_BASE_URL !== '') {
        return (string) PODO_CRM_BOOKING_BASE_URL;
    }
    return podo_opt('crm_booking_base_url', 'https://crm.rozhenko.km.ua/');
}

function podo_crm_booking_token(): string {
    if (defined('PODO_CRM_BOOKING_TOKEN') && PODO_CRM_BOOKING_TOKEN !== '') {
        return (string) PODO_CRM_BOOKING_TOKEN;
    }
    return podo_opt('crm_booking_token');
}

/**
 * Серверна перевірка Google reCAPTCHA v3: success + action + score.
 * Повертає true, якщо капча вимкнена (немає ключів) або токен валідний; інакше WP_Error.
 *
 * @return true|WP_Error
 */
function podo_verify_recaptcha(string $token, string $ip, string $action, float $min_score) {
    if (!podo_recaptcha_enabled()) {
        return true;
    }

    if ($token === '') {
        return new WP_Error('podo_captcha', __('Не вдалося підтвердити, що ви не робот. Оновіть сторінку і спробуйте ще раз.', 'podo'), ['status' => 400]);
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'timeout' => 8,
        'body'    => [
            'secret'   => podo_recaptcha_secret_key(),
            'response' => $token,
            'remoteip' => $ip,
        ],
    ]);

    if (is_wp_error($response)) {
        error_log('podo recaptcha: siteverify недоступний: ' . $response->get_error_message());
        return new WP_Error('podo_captcha_http', __('Не вдалося перевірити захист від спаму. Спробуйте ще раз або зателефонуйте нам.', 'podo'), ['status' => 502]);
    }

    $body       = json_decode((string) wp_remote_retrieve_body($response), true);
    $score      = isset($body['score']) ? (float) $body['score'] : 0.0;
    $got_action = (string) ($body['action'] ?? '');

    if (empty($body['success']) || $got_action !== $action || $score < $min_score) {
        error_log(sprintf(
            'podo recaptcha: відхилено (success=%s, action=%s, score=%.2f, codes=%s)',
            empty($body['success']) ? '0' : '1',
            $got_action !== '' ? $got_action : '—',
            $score,
            implode(',', (array) ($body['error-codes'] ?? []))
        ));
        return new WP_Error('podo_captcha', __('Перевірка захисту від спаму не пройдена. Оновіть сторінку і спробуйте ще раз, або зателефонуйте нам.', 'podo'), ['status' => 400]);
    }

    return true;
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
