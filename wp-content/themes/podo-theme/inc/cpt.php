<?php
/**
 * Кастомні типи записів і таксономії.
 */

defined('ABSPATH') || exit;

add_action('init', function () {

    // --- Послуги ---
    register_post_type('service', [
        'labels' => [
            'name'          => __('Послуги', 'podo'),
            'singular_name' => __('Послуга', 'podo'),
            'add_new'       => __('Додати послугу', 'podo'),
            'add_new_item'  => __('Нова послуга', 'podo'),
            'edit_item'     => __('Редагувати послугу', 'podo'),
            'menu_name'     => __('Послуги', 'podo'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'menu_position'       => 20,
        'menu_icon'           => 'dashicons-clipboard',
        'supports'            => ['title', 'page-attributes'],
        'has_archive'         => false,
        'rewrite'             => false,
        'exclude_from_search' => true,
    ]);

    register_taxonomy('service_cat', 'service', [
        'labels' => [
            'name'          => __('Категорії послуг', 'podo'),
            'singular_name' => __('Категорія послуг', 'podo'),
            'menu_name'     => __('Категорії', 'podo'),
        ],
        'hierarchical'      => true,
        'public'            => false,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ]);

    // --- Відгуки ---
    register_post_type('review', [
        'labels' => [
            'name'          => __('Відгуки', 'podo'),
            'singular_name' => __('Відгук', 'podo'),
            'add_new'       => __('Додати відгук', 'podo'),
            'add_new_item'  => __('Новий відгук', 'podo'),
            'edit_item'     => __('Редагувати відгук', 'podo'),
            'menu_name'     => __('Відгуки', 'podo'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => true,
        'menu_position'       => 21,
        'menu_icon'           => 'dashicons-star-filled',
        'supports'            => ['title'],
        'has_archive'         => false,
        'rewrite'             => false,
        'exclude_from_search' => true,
    ]);

    // --- Заявки на запис (створюються лише формою на сайті) ---
    register_post_type('booking', [
        'labels' => [
            'name'          => __('Заявки', 'podo'),
            'singular_name' => __('Заявка', 'podo'),
            'edit_item'     => __('Заявка', 'podo'),
            'menu_name'     => __('Заявки', 'podo'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_rest'        => false,
        'menu_position'       => 22,
        'menu_icon'           => 'dashicons-phone',
        'supports'            => ['title'],
        'has_archive'         => false,
        'rewrite'             => false,
        'exclude_from_search' => true,
        'capabilities'        => ['create_posts' => 'do_not_allow'],
        'map_meta_cap'        => true,
    ]);
});

/* ---------- Адмін-колонки ---------- */

// Послуги: номер і ціна
add_filter('manage_service_posts_columns', function ($columns) {
    $columns['service_number'] = __('№', 'podo');
    $columns['service_price']  = __('Ціна', 'podo');
    return $columns;
});
add_action('manage_service_posts_custom_column', function ($column, $post_id) {
    if ($column === 'service_number') {
        echo esc_html((string) get_field('service_number', $post_id));
    }
    if ($column === 'service_price') {
        echo esc_html((string) get_field('service_price', $post_id));
    }
}, 10, 2);

// Відгуки: послуга та рейтинг
add_filter('manage_review_posts_columns', function ($columns) {
    $columns['review_service'] = __('Послуга', 'podo');
    $columns['review_rating']  = __('Рейтинг', 'podo');
    return $columns;
});
add_action('manage_review_posts_custom_column', function ($column, $post_id) {
    if ($column === 'review_service') {
        echo esc_html((string) get_field('review_service', $post_id));
    }
    if ($column === 'review_rating') {
        $rating = (int) get_field('review_rating', $post_id);
        echo esc_html($rating ? str_repeat('★', $rating) : '—');
    }
}, 10, 2);

// Заявки: телефон, послуга, статус
add_filter('manage_booking_posts_columns', function ($columns) {
    unset($columns['date']);
    $columns['booking_phone']   = __('Телефон', 'podo');
    $columns['booking_service'] = __('Послуга', 'podo');
    $columns['booking_status']  = __('Статус', 'podo');
    $columns['booking_crm']     = __('CRM', 'podo');
    $columns['date']            = __('Дата', 'podo');
    return $columns;
});
add_action('manage_booking_posts_custom_column', function ($column, $post_id) {
    if ($column === 'booking_phone') {
        echo esc_html((string) get_field('booking_phone', $post_id));
    }
    if ($column === 'booking_service') {
        echo esc_html((string) get_field('booking_service', $post_id));
    }
    if ($column === 'booking_status') {
        $status = (string) get_field('booking_status', $post_id);
        if ($status === 'done') {
            echo '<span style="background:#EAF4EF;color:#166B4E;padding:3px 10px;border-radius:999px;font-weight:600;">' . esc_html__('Опрацьована', 'podo') . '</span>';
        } else {
            echo '<span style="background:#FDECEA;color:#B3261E;padding:3px 10px;border-radius:999px;font-weight:600;">' . esc_html__('Нова', 'podo') . '</span>';
        }
    }
    if ($column === 'booking_crm') {
        $status = (string) get_field('booking_crm_status', $post_id);
        $labels = [
            'sent'     => __('Надіслано', 'podo'),
            'failed'   => __('Помилка', 'podo'),
            'disabled' => __('Вимкнено', 'podo'),
        ];
        echo esc_html($labels[$status] ?? '—');
    }
}, 10, 2);

// Фільтр заявок за статусом
add_action('restrict_manage_posts', function ($post_type) {
    if ($post_type !== 'booking') {
        return;
    }
    $current = sanitize_text_field(wp_unslash($_GET['booking_status'] ?? ''));
    ?>
    <select name="booking_status">
        <option value=""><?php esc_html_e('Всі статуси', 'podo'); ?></option>
        <option value="new" <?php selected($current, 'new'); ?>><?php esc_html_e('Нові', 'podo'); ?></option>
        <option value="done" <?php selected($current, 'done'); ?>><?php esc_html_e('Опрацьовані', 'podo'); ?></option>
    </select>
    <?php
});
add_action('parse_query', function ($query) {
    global $pagenow;
    $status = sanitize_text_field(wp_unslash($_GET['booking_status'] ?? ''));
    if (is_admin() && $pagenow === 'edit.php' && ($query->query_vars['post_type'] ?? '') === 'booking' && $status !== '') {
        $query->query_vars['meta_key']   = 'booking_status';
        $query->query_vars['meta_value'] = $status;
    }
});
