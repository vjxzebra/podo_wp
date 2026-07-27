<?php
/**
 * Plugin Name: Podo MCP Server
 * Description: MCP-сервер (Model Context Protocol, Streamable HTTP) для CRUD записів блогу. Basic-автентифікація логіном/паролем адмінки або Application Password; доступ лише адміністраторам.
 * Version: 1.0.0
 */

defined('ABSPATH') || exit;

const PODO_MCP_VERSION            = '1.0.0';
const PODO_MCP_PROTOCOL_VERSIONS  = ['2024-11-05', '2025-03-26', '2025-06-18'];
const PODO_MCP_MAX_AUTH_FAILURES  = 10;      // невдалих спроб на IP
const PODO_MCP_AUTH_WINDOW        = 900;     // вікно блокування, секунд

add_action('rest_api_init', function () {
    register_rest_route('podo/v1', '/mcp', [
        'methods'             => ['GET', 'POST', 'DELETE'],
        'callback'            => 'podo_mcp_handle',
        'permission_callback' => 'podo_mcp_auth',
    ]);
});

/* ---------------------------------------------------------------------------
 * Автентифікація: Basic (логін + пароль адмінки чи Application Password),
 * лише користувачі з manage_options. Rate-limit невдалих спроб по IP.
 * ------------------------------------------------------------------------ */

function podo_mcp_client_ip(): string {
    // За Caddy реальний IP у X-Forwarded-For (перший у списку); у dev — REMOTE_ADDR.
    $xff = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])) : '';
    if ($xff !== '') {
        $first = trim(explode(',', $xff)[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
}

function podo_mcp_auth_header(WP_REST_Request $request): string {
    $header = (string) $request->get_header('authorization');
    if ($header === '') {
        foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (!empty($_SERVER[$key])) {
                $header = (string) wp_unslash($_SERVER[$key]);
                break;
            }
        }
    }
    return trim($header);
}

/**
 * Токен на пред'явника: «Authorization: Bearer <пароль застосунку>».
 */
function podo_mcp_bearer_token(WP_REST_Request $request): ?string {
    $header = podo_mcp_auth_header($request);
    if ($header !== '' && stripos($header, 'bearer ') === 0) {
        $token = trim(substr($header, 7));
        return $token !== '' ? $token : null;
    }
    return null;
}

function podo_mcp_basic_credentials(WP_REST_Request $request): ?array {
    $header = podo_mcp_auth_header($request);
    if ($header !== '' && stripos($header, 'basic ') === 0) {
        $decoded = base64_decode(substr($header, 6), true);
        if ($decoded !== false && strpos($decoded, ':') !== false) {
            return explode(':', $decoded, 2);
        }
        return null;
    }
    if (!empty($_SERVER['PHP_AUTH_USER'])) {
        return [
            (string) wp_unslash($_SERVER['PHP_AUTH_USER']),
            isset($_SERVER['PHP_AUTH_PW']) ? (string) wp_unslash($_SERVER['PHP_AUTH_PW']) : '',
        ];
    }
    return null;
}

/**
 * Автентифікація «чистим» токеном: логін не передається, тому пароль застосунку
 * звіряється з паролями всіх адміністраторів сайту (їх одиниці).
 * Токен виду «логін:пароль» теж приймається — тоді перевіряється лише цей користувач.
 */
function podo_mcp_user_by_token(string $token): ?WP_User {
    if (!function_exists('wp_authenticate_application_password')) {
        return null;
    }

    if (strpos($token, ':') !== false) {
        [$login, $password] = explode(':', $token, 2);
        $user = wp_authenticate_application_password(null, $login, $password);
        return $user instanceof WP_User ? $user : null;
    }

    $admins = get_users([
        'role'    => 'administrator',
        'fields'  => ['ID', 'user_login'],
        'number'  => 20,
        'orderby' => 'ID',
    ]);
    foreach ($admins as $admin) {
        $user = wp_authenticate_application_password(null, $admin->user_login, $token);
        if ($user instanceof WP_User) {
            return $user;
        }
    }
    return null;
}

function podo_mcp_auth(WP_REST_Request $request) {
    // На проді — тільки HTTPS: токен не має літати відкритим текстом.
    if (defined('WP_ENV') && WP_ENV === 'production' && !is_ssl()) {
        return new WP_Error('podo_mcp_https', 'MCP доступний лише через HTTPS.', ['status' => 403]);
    }

    // Анти-DNS-rebinding: браузерний запит із чужого походження відхиляємо.
    // Клієнти MCP або не шлють Origin, або шлють свій — він у списку дозволених.
    $origin = (string) $request->get_header('origin');
    if ($origin !== '') {
        $origin_host = (string) wp_parse_url($origin, PHP_URL_HOST);
        $allowed     = apply_filters('podo_mcp_allowed_origins', [
            (string) wp_parse_url(home_url(), PHP_URL_HOST),
            'claude.ai',
            'www.claude.ai',
            'anthropic.com',
            'localhost',
            '127.0.0.1',
        ]);
        if ($origin_host === '' || !in_array($origin_host, $allowed, true)) {
            return new WP_Error('podo_mcp_origin', 'Заборонене походження запиту.', ['status' => 403]);
        }
    }

    $ip        = podo_mcp_client_ip();
    $fail_key  = 'podo_mcp_fail_' . md5($ip);
    $failures  = (int) get_transient($fail_key);
    if ($failures >= PODO_MCP_MAX_AUTH_FAILURES) {
        return new WP_Error('podo_mcp_locked', 'Забагато невдалих спроб. Спробуйте пізніше.', ['status' => 429]);
    }

    $user  = null;
    $token = podo_mcp_bearer_token($request);

    if ($token !== null) {
        // Основний шлях: токен на пред'явника = пароль застосунку WordPress.
        $user = podo_mcp_user_by_token($token);
        if (!$user instanceof WP_User) {
            set_transient($fail_key, $failures + 1, PODO_MCP_AUTH_WINDOW);
            return new WP_Error('podo_mcp_auth', 'Невірний токен (пароль застосунку).', ['status' => 401]);
        }
    } else {
        // Сумісність: Basic із логіном і паролем адмінки або паролем застосунку.
        $creds = podo_mcp_basic_credentials($request);
        if ($creds === null) {
            return new WP_Error(
                'podo_mcp_auth',
                'Потрібен заголовок Authorization: Bearer <пароль застосунку> або Basic <логін:пароль>.',
                ['status' => 401]
            );
        }
        $authenticated = wp_authenticate($creds[0], $creds[1]);
        if (is_wp_error($authenticated)) {
            set_transient($fail_key, $failures + 1, PODO_MCP_AUTH_WINDOW);
            return new WP_Error('podo_mcp_auth', 'Невірний логін або пароль.', ['status' => 401]);
        }
        $user = $authenticated;
    }

    if (!user_can($user, 'manage_options')) {
        return new WP_Error('podo_mcp_forbidden', 'Доступ дозволено лише адміністраторам.', ['status' => 403]);
    }

    delete_transient($fail_key);
    wp_set_current_user($user->ID);
    return true;
}

/* ---------------------------------------------------------------------------
 * Транспорт: Streamable HTTP без SSE (звичайні JSON-відповіді).
 * ------------------------------------------------------------------------ */

function podo_mcp_handle(WP_REST_Request $request) {
    $method = $request->get_method();

    if ($method === 'GET') {
        // SSE-стрім не підтримуємо — за специфікацією сервер може відповісти 405.
        return new WP_REST_Response(null, 405);
    }
    if ($method === 'DELETE') {
        // Сесій немає (stateless) — завершувати нічого.
        return new WP_REST_Response(null, 200);
    }

    $message = json_decode($request->get_body(), true);
    if (!is_array($message)) {
        return new WP_REST_Response([
            'jsonrpc' => '2.0',
            'id'      => null,
            'error'   => ['code' => -32700, 'message' => 'Некоректний JSON.'],
        ], 400);
    }

    // Батч (протокол 2025-03-26 і старіші).
    if (array_is_list($message)) {
        $responses = [];
        foreach ($message as $single) {
            $response = is_array($single) ? podo_mcp_dispatch($single) : podo_mcp_error(null, -32600, 'Некоректний запит.');
            if ($response !== null) {
                $responses[] = $response;
            }
        }
        return $responses === []
            ? new WP_REST_Response(null, 202)
            : new WP_REST_Response($responses, 200);
    }

    $response = podo_mcp_dispatch($message);
    return $response === null
        ? new WP_REST_Response(null, 202)  // нотифікація — без тіла
        : new WP_REST_Response($response, 200);
}

function podo_mcp_result($id, $result): array {
    return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
}

function podo_mcp_error($id, int $code, string $message): array {
    return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
}

function podo_mcp_dispatch(array $message): ?array {
    $id     = $message['id'] ?? null;
    $method = isset($message['method']) ? (string) $message['method'] : '';
    $params = isset($message['params']) && is_array($message['params']) ? $message['params'] : [];

    if ($method === '') {
        return podo_mcp_error($id, -32600, 'Некоректний запит: немає method.');
    }
    if (str_starts_with($method, 'notifications/')) {
        return null;
    }

    switch ($method) {
        case 'initialize':
            $requested = isset($params['protocolVersion']) ? (string) $params['protocolVersion'] : '';
            $version   = in_array($requested, PODO_MCP_PROTOCOL_VERSIONS, true)
                ? $requested
                : PODO_MCP_PROTOCOL_VERSIONS[count(PODO_MCP_PROTOCOL_VERSIONS) - 1];
            return podo_mcp_result($id, [
                'protocolVersion' => $version,
                'capabilities'    => ['tools' => new stdClass()],
                'serverInfo'      => [
                    'name'    => 'podo-wp-mcp',
                    'title'   => 'Podo WP — записи блогу',
                    'version' => PODO_MCP_VERSION,
                ],
                'instructions'    => 'CRUD записів блогу подологічного кабінету. Контент записів — Gutenberg-розмітка '
                    . '(<!-- wp:paragraph --><p>…</p><!-- /wp:paragraph --> тощо). Нові записи створюються чернетками, '
                    . 'якщо не передано status=publish. Категорії задаються слагами (див. list_categories).',
            ]);

        case 'ping':
            return podo_mcp_result($id, new stdClass());

        case 'tools/list':
            return podo_mcp_result($id, ['tools' => podo_mcp_tools_spec()]);

        case 'tools/call':
            $name = isset($params['name']) ? (string) $params['name'] : '';
            $args = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : [];
            try {
                $data = podo_mcp_call_tool($name, $args);
                return podo_mcp_result($id, [
                    'content' => [['type' => 'text', 'text' => wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]],
                    'isError' => false,
                ]);
            } catch (Exception $e) {
                return podo_mcp_result($id, [
                    'content' => [['type' => 'text', 'text' => 'Помилка: ' . $e->getMessage()]],
                    'isError' => true,
                ]);
            }

        default:
            return podo_mcp_error($id, -32601, 'Метод не підтримується: ' . $method);
    }
}

/* ---------------------------------------------------------------------------
 * Інструменти
 * ------------------------------------------------------------------------ */

function podo_mcp_tools_spec(): array {
    $post_fields = [
        'title'           => ['type' => 'string', 'description' => 'Заголовок запису'],
        'content'         => ['type' => 'string', 'description' => 'Контент у Gutenberg-розмітці (блокові HTML-коментарі)'],
        'status'          => ['type' => 'string', 'enum' => ['draft', 'publish', 'pending'], 'description' => 'Статус запису'],
        'excerpt'         => ['type' => 'string', 'description' => 'Короткий опис для картки в сітці блогу (без HTML)'],
        'categories'      => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Слаги категорій (див. list_categories)'],
        'slug'            => ['type' => 'string', 'description' => 'URL-слаг (латиниця); якщо не задано — транслітерується з заголовка'],
        'date'            => ['type' => 'string', 'description' => 'Дата публікації у форматі YYYY-MM-DD HH:MM:SS (час київський)'],
        'seo_description' => ['type' => 'string', 'description' => 'Meta description для пошуковиків (до 170 символів)'],
    ];

    return [
        [
            'name'        => 'list_posts',
            'description' => 'Список записів блогу з фільтрами. Повертає id, заголовок, слаг, статус, дату, категорії, лінк.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'search'   => ['type' => 'string', 'description' => 'Пошук за текстом'],
                    'status'   => ['type' => 'string', 'enum' => ['publish', 'draft', 'pending', 'trash', 'any'], 'description' => 'Фільтр за статусом (типово any)'],
                    'category' => ['type' => 'string', 'description' => 'Слаг категорії'],
                    'page'     => ['type' => 'integer', 'minimum' => 1],
                    'per_page' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50],
                ],
            ],
        ],
        [
            'name'        => 'get_post',
            'description' => 'Повний запис за ID: контент (Gutenberg-розмітка), excerpt, категорії, SEO-опис, обкладинка.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => ['id' => ['type' => 'integer']],
                'required'   => ['id'],
            ],
        ],
        [
            'name'        => 'create_post',
            'description' => 'Створити запис блогу. Без status створюється чернетка. Контент — Gutenberg-розмітка.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => $post_fields,
                'required'   => ['title', 'content'],
            ],
        ],
        [
            'name'        => 'update_post',
            'description' => 'Оновити запис за ID. Передавайте лише поля, які треба змінити.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => array_merge(['id' => ['type' => 'integer']], $post_fields),
                'required'   => ['id'],
            ],
        ],
        [
            'name'        => 'delete_post',
            'description' => 'Видалити запис: типово в кошик; force=true — остаточно.',
            'inputSchema' => [
                'type'       => 'object',
                'properties' => [
                    'id'    => ['type' => 'integer'],
                    'force' => ['type' => 'boolean', 'description' => 'true — видалити остаточно, повз кошик'],
                ],
                'required'   => ['id'],
            ],
        ],
        [
            'name'        => 'list_categories',
            'description' => 'Категорії блогу: id, назва, слаг, кількість записів.',
            'inputSchema' => ['type' => 'object', 'properties' => new stdClass()],
        ],
    ];
}

function podo_mcp_post_summary(WP_Post $post): array {
    return [
        'id'         => $post->ID,
        'title'      => $post->post_title,
        'slug'       => $post->post_name,
        'status'     => $post->post_status,
        'date'       => $post->post_date,
        'link'       => get_permalink($post),
        'categories' => array_map(static fn($t) => $t->slug, get_the_category($post->ID)),
        'excerpt'    => $post->post_excerpt,
    ];
}

function podo_mcp_category_ids(array $slugs): array {
    $ids = [];
    foreach ($slugs as $slug) {
        $term = get_term_by('slug', sanitize_title((string) $slug), 'category');
        if (!$term) {
            $known = implode(', ', array_map(static fn($t) => $t->slug, get_terms(['taxonomy' => 'category', 'hide_empty' => false])));
            throw new Exception("Невідома категорія «{$slug}». Доступні: {$known}");
        }
        $ids[] = (int) $term->term_id;
    }
    return $ids;
}

function podo_mcp_require_post(int $id): WP_Post {
    $post = get_post($id);
    if (!$post || $post->post_type !== 'post') {
        throw new Exception("Запис {$id} не знайдено (інструмент працює лише зі звичайними записами блогу).");
    }
    return $post;
}

function podo_mcp_call_tool(string $name, array $args) {
    switch ($name) {
        case 'list_posts': {
            $per_page = isset($args['per_page']) ? min(50, max(1, (int) $args['per_page'])) : 20;
            $query    = new WP_Query([
                'post_type'      => 'post',
                'post_status'    => isset($args['status']) && $args['status'] !== 'any'
                    ? sanitize_key((string) $args['status'])
                    : ['publish', 'draft', 'pending', 'future', 'private'],
                's'              => isset($args['search']) ? sanitize_text_field((string) $args['search']) : '',
                'category_name'  => isset($args['category']) ? sanitize_title((string) $args['category']) : '',
                'paged'          => isset($args['page']) ? max(1, (int) $args['page']) : 1,
                'posts_per_page' => $per_page,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            return [
                'total'       => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
                'posts'       => array_map('podo_mcp_post_summary', $query->posts),
            ];
        }

        case 'get_post': {
            $post = podo_mcp_require_post((int) ($args['id'] ?? 0));
            $data = podo_mcp_post_summary($post);
            $data['content']         = $post->post_content;
            $data['seo_description'] = function_exists('get_field') ? (string) get_field('seo_description', $post->ID) : '';
            $data['featured_image']  = has_post_thumbnail($post) ? (string) get_the_post_thumbnail_url($post, 'full') : '';
            $data['modified']        = $post->post_modified;
            return $data;
        }

        case 'create_post':
        case 'update_post': {
            $is_update = ($name === 'update_post');
            $postarr   = ['post_type' => 'post'];

            if ($is_update) {
                $post          = podo_mcp_require_post((int) ($args['id'] ?? 0));
                $postarr['ID'] = $post->ID;
            } else {
                foreach (['title', 'content'] as $required) {
                    if (!isset($args[$required]) || trim((string) $args[$required]) === '') {
                        throw new Exception("Поле «{$required}» обовʼязкове.");
                    }
                }
                $postarr['post_status'] = 'draft';
            }

            if (isset($args['title']))   { $postarr['post_title']   = sanitize_text_field((string) $args['title']); }
            if (isset($args['content'])) { $postarr['post_content'] = (string) $args['content']; }
            if (isset($args['excerpt'])) { $postarr['post_excerpt'] = sanitize_textarea_field((string) $args['excerpt']); }
            if (isset($args['slug']))    { $postarr['post_name']    = sanitize_title((string) $args['slug']); }
            if (isset($args['status'])) {
                $status = sanitize_key((string) $args['status']);
                if (!in_array($status, ['draft', 'publish', 'pending'], true)) {
                    throw new Exception('Дозволені статуси: draft, publish, pending.');
                }
                $postarr['post_status'] = $status;
            }
            if (isset($args['date'])) {
                $raw = trim((string) $args['date']);
                // Голий формат = київський час; ISO-8601 з офсетом/Z конвертується в час сайту.
                if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(:\d{2})?([Zz]|[+\-]\d{2}:?\d{2})?$/', $raw)) {
                    throw new Exception('Невірний формат дати. Очікується YYYY-MM-DD HH:MM:SS (час київський) або ISO-8601.');
                }
                $dt = date_create($raw, wp_timezone());
                if ($dt === false) {
                    throw new Exception('Невірна дата.');
                }
                $dt->setTimezone(wp_timezone());
                $postarr['post_date']     = $dt->format('Y-m-d H:i:s');
                $postarr['post_date_gmt'] = get_gmt_from_date($postarr['post_date']);
                $postarr['edit_date']     = true;
            }
            if (isset($args['categories'])) {
                if (!is_array($args['categories'])) {
                    throw new Exception('categories має бути масивом слагів.');
                }
                $postarr['post_category'] = podo_mcp_category_ids($args['categories']);
            }

            $post_id = $is_update ? wp_update_post(wp_slash($postarr), true) : wp_insert_post(wp_slash($postarr), true);
            if (is_wp_error($post_id)) {
                throw new Exception($post_id->get_error_message());
            }
            if (isset($args['seo_description']) && function_exists('update_field')) {
                update_field('seo_description', sanitize_textarea_field((string) $args['seo_description']), (int) $post_id);
            }

            $result             = podo_mcp_post_summary(podo_mcp_require_post((int) $post_id));
            $result['edit_url'] = get_edit_post_link((int) $post_id, 'raw');
            return $result;
        }

        case 'delete_post': {
            $post  = podo_mcp_require_post((int) ($args['id'] ?? 0));
            $force = !empty($args['force']);
            $done  = $force ? wp_delete_post($post->ID, true) : wp_trash_post($post->ID);
            if (!$done) {
                throw new Exception('Не вдалося видалити запис.');
            }
            return [
                'id'     => $post->ID,
                'title'  => $post->post_title,
                'result' => $force ? 'видалено остаточно' : 'переміщено в кошик',
            ];
        }

        case 'list_categories': {
            $terms = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
            return array_map(static fn($t) => [
                'id'    => $t->term_id,
                'name'  => $t->name,
                'slug'  => $t->slug,
                'count' => $t->count,
            ], is_array($terms) ? $terms : []);
        }

        default:
            throw new Exception("Невідомий інструмент: {$name}");
    }
}
