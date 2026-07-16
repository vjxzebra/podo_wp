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

    // Форма заявки — лише на сторінці контактів
    if (is_page_template('templates/contacts.php')) {
        wp_enqueue_script(
            'podo-booking',
            $uri . '/assets/js/booking.js',
            [],
            (string) filemtime($dir . '/assets/js/booking.js'),
            ['in_footer' => true]
        );
        if (podo_recaptcha_enabled()) {
            wp_enqueue_script(
                'google-recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . rawurlencode(podo_recaptcha_site_key()) . '&hl=uk',
                [],
                null,
                ['in_footer' => true]
            );
        }
        wp_localize_script('podo-booking', 'podoBooking', [
            'endpoint'     => esc_url_raw(rest_url('podo/v1/booking')),
            'nonce'        => wp_create_nonce('podo_booking'),
            // X-WP-Nonce: без нього REST знеособлює залогінених користувачів,
            // і кастомний nonce (створений для них на сторінці) не проходить перевірку
            'restNonce'    => wp_create_nonce('wp_rest'),
            'recaptcha'    => podo_recaptcha_enabled(),
            'recaptchaKey' => podo_recaptcha_site_key(),
            'i18n'         => [
                'required'    => __("Заповніть, будь ласка, ім'я та телефон.", 'podo'),
                'phone'       => __('Вкажіть повний номер телефону: +380 XX XXX XX XX.', 'podo'),
                'captchaWait' => __('Захист від спаму ще завантажується — зачекайте секунду і спробуйте знову.', 'podo'),
                'captchaFail' => __('Не вдалося пройти перевірку захисту від спаму. Оновіть сторінку або зателефонуйте нам.', 'podo'),
                'stale'       => __('Сесія застаріла. Оновіть сторінку і спробуйте ще раз.', 'podo'),
                'error'       => __('Щось пішло не так. Спробуйте ще раз або зателефонуйте.', 'podo'),
            ],
        ]);
    }
});

// Прибираємо зайве зі стандартного head
add_action('init', function () {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
});

// Сторінку з формою не можна кешувати: в HTML запечені nonce і стан reCAPTCHA
add_action('template_redirect', function () {
    if (is_page_template('templates/contacts.php')) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
    }
});
