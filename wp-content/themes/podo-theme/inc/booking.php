<?php
/**
 * Заявки на запис: REST-ендпоінт, збереження в CPT booking, email-сповіщення.
 */

defined('ABSPATH') || exit;

const PODO_BOOKING_RATE_LIMIT = 5;       // заявок з одного IP
const PODO_BOOKING_RATE_WINDOW = 3600;   // за годину
const PODO_RECAPTCHA_MIN_SCORE = 0.5;    // поріг reCAPTCHA v3 (0 — бот, 1 — людина)
const PODO_RECAPTCHA_ACTION = 'booking'; // action, який передає фронтенд у grecaptcha.execute()

add_action('rest_api_init', function () {
    register_rest_route('podo/v1', '/booking', [
        'methods'             => 'POST',
        'callback'            => 'podo_booking_submit',
        'permission_callback' => '__return_true',
        'args'                => [
            'name'      => ['type' => 'string', 'required' => true],
            'phone'     => ['type' => 'string', 'required' => true],
            'service'   => ['type' => 'string', 'required' => false],
            'comment'   => ['type' => 'string', 'required' => false],
            'website'   => ['type' => 'string', 'required' => false], // honeypot
            'nonce'     => ['type' => 'string', 'required' => true],
            'recaptcha' => ['type' => 'string', 'required' => false],
        ],
    ]);
});

/**
 * Обробка заявки з форми.
 */
function podo_booking_submit(WP_REST_Request $request) {
    // Honeypot: боти заповнюють приховане поле — вдаємо успіх, нічого не зберігаємо
    if ((string) $request->get_param('website') !== '') {
        return new WP_REST_Response(['success' => true], 200);
    }

    if (!wp_verify_nonce((string) $request->get_param('nonce'), 'podo_booking')) {
        return new WP_Error('podo_nonce', __('Сесія застаріла. Оновіть сторінку і спробуйте ще раз.', 'podo'), ['status' => 403]);
    }

    // Rate-limit по IP
    $ip  = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $key = 'podo_bk_' . md5($ip);
    $count = (int) get_transient($key);
    if ($count >= PODO_BOOKING_RATE_LIMIT) {
        return new WP_Error('podo_rate', __('Забагато заявок. Спробуйте пізніше або зателефонуйте нам.', 'podo'), ['status' => 429]);
    }

    // reCAPTCHA (якщо ввімкнена в опціях)
    $captcha_check = podo_verify_recaptcha((string) $request->get_param('recaptcha'), $ip, PODO_RECAPTCHA_ACTION, PODO_RECAPTCHA_MIN_SCORE);
    if (is_wp_error($captcha_check)) {
        return $captcha_check;
    }

    $name    = sanitize_text_field((string) $request->get_param('name'));
    $phone   = sanitize_text_field((string) $request->get_param('phone'));
    $service = sanitize_text_field((string) $request->get_param('service'));
    $comment = sanitize_textarea_field((string) $request->get_param('comment'));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        return new WP_Error('podo_name', __("Вкажіть, будь ласка, ваше ім'я.", 'podo'), ['status' => 400]);
    }

    // Український номер у будь-якому написанні -> нормалізуємо до "+380 XX XXX XX XX"
    $phone_digits = preg_replace('/\D/', '', $phone);
    if (preg_match('/^(?:38)?0(\d{9})$/', (string) $phone_digits, $m)) {
        $phone = '+380 ' . substr($m[1], 0, 2) . ' ' . substr($m[1], 2, 3) . ' ' . substr($m[1], 5, 2) . ' ' . substr($m[1], 7, 2);
    } else {
        return new WP_Error('podo_phone', __('Вкажіть коректний український номер: +380 XX XXX XX XX.', 'podo'), ['status' => 400]);
    }

    $post_id = wp_insert_post([
        'post_type'   => 'booking',
        'post_status' => 'publish',
        'post_title'  => sprintf('%s — %s', $name, $phone),
    ], true);

    if (is_wp_error($post_id)) {
        return new WP_Error('podo_save', __('Не вдалося зберегти заявку. Зателефонуйте нам, будь ласка.', 'podo'), ['status' => 500]);
    }

    update_field('booking_status', 'new', $post_id);
    update_field('booking_name', $name, $post_id);
    update_field('booking_phone', $phone, $post_id);
    update_field('booking_service', $service, $post_id);
    update_field('booking_comment', $comment, $post_id);

    set_transient($key, $count + 1, PODO_BOOKING_RATE_WINDOW);

    podo_booking_notify($post_id, $name, $phone, $service, $comment);

    return new WP_REST_Response(['success' => true], 200);
}

/**
 * Email-сповіщення про нову заявку.
 */
function podo_booking_notify(int $post_id, string $name, string $phone, string $service, string $comment): void {
    $to = podo_opt('booking_email', (string) get_option('admin_email'));

    $subject = sprintf(__('Нова заявка на запис: %s', 'podo'), $name);
    $lines = [
        __('Нова заявка з сайту', 'podo'),
        '',
        __("Ім'я:", 'podo') . ' ' . $name,
        __('Телефон:', 'podo') . ' ' . $phone,
        __('Послуга:', 'podo') . ' ' . ($service !== '' ? $service : '—'),
        __('Коментар:', 'podo') . ' ' . ($comment !== '' ? $comment : '—'),
        '',
        __('Переглянути в адмінці:', 'podo') . ' ' . admin_url('post.php?post=' . $post_id . '&action=edit'),
    ];

    wp_mail($to, $subject, implode("\n", $lines));
}

/**
 * Лічильник нових заявок у меню адмінки.
 */
add_action('admin_menu', function () {
    global $menu;

    $count = (int) (new WP_Query([
        'post_type'      => 'booking',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => 'booking_status',
        'meta_value'     => 'new',
        'no_found_rows'  => false,
    ]))->found_posts;

    if ($count < 1) {
        return;
    }

    foreach ($menu as $i => $item) {
        if (($item[2] ?? '') === 'edit.php?post_type=booking') {
            $menu[$i][0] .= sprintf(' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $count);
            break;
        }
    }
});

/**
 * Поля заявки в адмінці лише для читання (редагується тільки статус).
 */
add_filter('acf/load_field', function ($field) {
    if (!is_admin() || empty($field['name'])) {
        return $field;
    }
    $readonly = ['booking_name', 'booking_phone', 'booking_service', 'booking_comment'];
    if (in_array($field['name'], $readonly, true) && get_post_type() === 'booking') {
        $field['readonly'] = 1;
    }
    return $field;
});
