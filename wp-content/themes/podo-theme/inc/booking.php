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

    podo_booking_send_to_crm($post_id, $name, $phone, $service, $comment);
    podo_booking_notify($post_id, $name, $phone, $service, $comment);

    return new WP_REST_Response(['success' => true], 200);
}

/**
 * Відправка заявки в Podoria CRM через server-side integration.
 */
function podo_booking_send_to_crm(int $post_id, string $name, string $phone, string $service, string $comment): void {
    $base_url = untrailingslashit(podo_crm_booking_base_url());
    $token    = podo_crm_booking_token();

    if ($base_url === '' || $token === '') {
        update_field('booking_crm_status', 'disabled', $post_id);
        return;
    }

    $payload = [
        'source'             => 'WEBSITE',
        'client_name'        => mb_substr($name, 0, 160),
        'phone'              => mb_substr($phone, 0, 32),
        'service'            => mb_substr($service, 0, 160),
        'message'            => mb_substr($comment, 0, 2000),
        'external_reference' => mb_substr('wp-booking-' . $post_id, 0, 160),
    ];

    $response = wp_remote_post($base_url . '/api/v1/integrations/booking-requests', [
        'timeout' => 12,
        'headers' => [
            'Authorization'   => 'Bearer ' . $token,
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
            'Idempotency-Key' => 'podo-wp-booking-' . $post_id,
        ],
        'body' => wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    if (is_wp_error($response)) {
        podo_booking_mark_crm_failed($post_id, $response->get_error_message());
        return;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);

    if ($code === 200 || $code === 201) {
        update_field('booking_crm_status', 'sent', $post_id);
        update_field('booking_crm_id', sanitize_text_field((string) ($body['id'] ?? '')), $post_id);
        update_field('booking_crm_public_number', sanitize_text_field((string) ($body['public_number'] ?? '')), $post_id);
        update_field('booking_crm_error', '', $post_id);
        return;
    }

    $message = is_array($body) && isset($body['message'])
        ? (string) $body['message']
        : (string) wp_remote_retrieve_body($response);
    podo_booking_mark_crm_failed($post_id, sprintf('HTTP %d: %s', $code, $message));
}

/**
 * Позначає заявку як не відправлену в CRM, не ламаючи сабміт форми на сайті.
 */
function podo_booking_mark_crm_failed(int $post_id, string $message): void {
    update_field('booking_crm_status', 'failed', $post_id);
    update_field('booking_crm_error', mb_substr($message, 0, 1000), $post_id);
    error_log(sprintf('podo booking CRM: заявку #%d не відправлено: %s', $post_id, $message));
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
    $readonly = [
        'booking_name',
        'booking_phone',
        'booking_service',
        'booking_comment',
        'booking_crm_status',
        'booking_crm_id',
        'booking_crm_public_number',
        'booking_crm_error',
    ];
    if (in_array($field['name'], $readonly, true) && get_post_type() === 'booking') {
        $field['readonly'] = 1;
    }
    return $field;
});
