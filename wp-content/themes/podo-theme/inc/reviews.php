<?php
/**
 * Відгуки відвідувачів: REST-ендпоінт, збереження на модерацію (pending), email-сповіщення.
 */

defined('ABSPATH') || exit;

const PODO_REVIEW_RATE_WINDOW = DAY_IN_SECONDS; // один відгук з одного IP на добу
const PODO_REVIEW_RECAPTCHA_ACTION = 'review';  // action, який передає фронтенд у grecaptcha.execute()

add_action('rest_api_init', function () {
    register_rest_route('podo/v1', '/review', [
        'methods'             => 'POST',
        'callback'            => 'podo_review_submit',
        'permission_callback' => '__return_true',
        'args'                => [
            'name'      => ['type' => 'string', 'required' => true],
            'rating'    => ['type' => 'integer', 'required' => true],
            'service'   => ['type' => 'string', 'required' => false],
            'text'      => ['type' => 'string', 'required' => true],
            'website'   => ['type' => 'string', 'required' => false], // honeypot
            'nonce'     => ['type' => 'string', 'required' => true],
            'recaptcha' => ['type' => 'string', 'required' => false],
        ],
    ]);
});

/**
 * Обробка відгуку з попапа на сторінці відгуків.
 */
function podo_review_submit(WP_REST_Request $request) {
    // Honeypot: боти заповнюють приховане поле — вдаємо успіх, нічого не зберігаємо
    if ((string) $request->get_param('website') !== '') {
        return new WP_REST_Response(['success' => true], 200);
    }

    if (!wp_verify_nonce((string) $request->get_param('nonce'), 'podo_review')) {
        return new WP_Error('podo_nonce', __('Сесія застаріла. Оновіть сторінку і спробуйте ще раз.', 'podo'), ['status' => 403]);
    }

    // Один відгук на добу з одного IP
    $ip  = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'] ?? ''));
    $key = 'podo_rev_' . md5($ip);
    if (get_transient($key)) {
        return new WP_Error('podo_rate', __('Ви вже залишали відгук сьогодні. Дякуємо! Новий можна буде надіслати завтра.', 'podo'), ['status' => 429]);
    }

    $captcha_check = podo_verify_recaptcha((string) $request->get_param('recaptcha'), $ip, PODO_REVIEW_RECAPTCHA_ACTION, PODO_RECAPTCHA_MIN_SCORE);
    if (is_wp_error($captcha_check)) {
        return $captcha_check;
    }

    $name    = sanitize_text_field((string) $request->get_param('name'));
    $rating  = (int) $request->get_param('rating');
    $service = sanitize_text_field((string) $request->get_param('service'));
    $text    = sanitize_textarea_field((string) $request->get_param('text'));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        return new WP_Error('podo_name', __("Вкажіть, будь ласка, ваше ім'я.", 'podo'), ['status' => 400]);
    }
    if ($rating < 1 || $rating > 5) {
        return new WP_Error('podo_rating', __('Оберіть оцінку від 1 до 5 зірок.', 'podo'), ['status' => 400]);
    }
    if (mb_strlen($text) < 10) {
        return new WP_Error('podo_text', __('Напишіть, будь ласка, хоча б кілька слів про ваш досвід.', 'podo'), ['status' => 400]);
    }
    if (mb_strlen($text) > 2000) {
        return new WP_Error('podo_text', __('Відгук задовгий — максимум 2000 символів.', 'podo'), ['status' => 400]);
    }

    // pending: на сайті з'явиться лише після схвалення в адмінці
    $post_id = wp_insert_post([
        'post_type'   => 'review',
        'post_status' => 'pending',
        'post_title'  => $name,
    ], true);

    if (is_wp_error($post_id)) {
        return new WP_Error('podo_save', __('Не вдалося зберегти відгук. Спробуйте, будь ласка, пізніше.', 'podo'), ['status' => 500]);
    }

    update_field('review_text', $text, $post_id);
    update_field('review_service', $service, $post_id);
    update_field('review_rating', $rating, $post_id);

    set_transient($key, 1, PODO_REVIEW_RATE_WINDOW);

    podo_review_notify($post_id, $name, $rating, $service, $text);

    return new WP_REST_Response(['success' => true], 200);
}

/**
 * Email-сповіщення про новий відгук на модерації.
 */
function podo_review_notify(int $post_id, string $name, int $rating, string $service, string $text): void {
    $to = podo_opt('booking_email', (string) get_option('admin_email'));

    $subject = sprintf(__('Новий відгук на модерації: %s', 'podo'), $name);
    $lines = [
        __('Новий відгук з сайту (очікує схвалення)', 'podo'),
        '',
        __("Ім'я:", 'podo') . ' ' . $name,
        __('Оцінка:', 'podo') . ' ' . str_repeat('★', $rating),
        __('Послуга:', 'podo') . ' ' . ($service !== '' ? $service : '—'),
        '',
        $text,
        '',
        __('Схвалити в адмінці:', 'podo') . ' ' . admin_url('post.php?post=' . $post_id . '&action=edit'),
    ];

    wp_mail($to, $subject, implode("\n", $lines));
}
