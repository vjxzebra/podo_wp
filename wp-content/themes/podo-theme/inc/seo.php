<?php
/**
 * SEO-мінімум: meta description, Open Graph, Twitter Cards, favicon.
 */

defined('ABSPATH') || exit;

/**
 * Description поточної сторінки: ACF-поле seo_description → excerpt → tagline.
 */
function podo_seo_description(): string {
    if (is_singular()) {
        $custom = (string) get_field('seo_description');
        if ($custom !== '') {
            return $custom;
        }
        if (is_single() && has_excerpt()) {
            return wp_strip_all_tags(get_the_excerpt());
        }
    }
    if (is_home()) {
        $blog_id = (int) get_option('page_for_posts');
        if ($blog_id) {
            $custom = (string) get_field('seo_description', $blog_id);
            if ($custom !== '') {
                return $custom;
            }
        }
    }
    if (is_category()) {
        $term_description = trim(wp_strip_all_tags(term_description()));
        if ($term_description !== '') {
            return $term_description;
        }
    }
    return (string) get_bloginfo('description');
}

/**
 * Канонічний URL поточної сторінки (з трейлінг-слешем, як у пермалінках).
 */
function podo_seo_url(): string {
    if (is_front_page()) {
        return home_url('/');
    }
    $request = (string) ($GLOBALS['wp']->request ?? '');
    return $request === '' ? home_url('/') : home_url(user_trailingslashit($request));
}

/**
 * OG-зображення: мініатюра запису (кроп 1200×630) → дефолтна брендована картка теми.
 *
 * @return array{url:string, width:int, height:int}
 */
function podo_og_image(): array {
    if (is_singular() && has_post_thumbnail()) {
        $src = wp_get_attachment_image_src(get_post_thumbnail_id(), 'podo-og');
        if ($src) {
            return ['url' => (string) $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2]];
        }
    }
    return [
        'url'    => get_template_directory_uri() . '/assets/img/og-default.png',
        'width'  => 1200,
        'height' => 630,
    ];
}

add_action('wp_head', function () {
    $description = trim(podo_seo_description());
    $title       = wp_get_document_title();
    $url         = podo_seo_url();
    $image       = podo_og_image();
    $site_name   = (string) get_bloginfo('name');
    $image_alt   = is_singular() ? (string) get_the_title() : $site_name;

    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    echo '<meta property="og:locale" content="uk_UA">' . "\n";
    echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description !== '') {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
    echo '<meta property="og:image:width" content="' . (int) $image['width'] . '">' . "\n";
    echo '<meta property="og:image:height" content="' . (int) $image['height'] . '">' . "\n";
    echo '<meta property="og:image:alt" content="' . esc_attr($image_alt) . '">' . "\n";

    if (is_single()) {
        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
        $category = get_the_category();
        if (!empty($category)) {
            echo '<meta property="article:section" content="' . esc_attr($category[0]->name) . '">' . "\n";
        }
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description !== '') {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
}, 5);

// Favicon з теми, якщо в Customizer не заданий site icon
add_action('wp_head', function () {
    if (!has_site_icon()) {
        echo '<link rel="icon" type="image/png" href="' . esc_url(get_template_directory_uri() . '/assets/img/logo.png') . '">' . "\n";
    }
}, 6);
