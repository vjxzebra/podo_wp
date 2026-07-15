<?php
/**
 * SEO-мінімум: meta description, Open Graph, favicon.
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
    return (string) get_bloginfo('description');
}

add_action('wp_head', function () {
    $description = trim(podo_seo_description());
    $title       = wp_get_document_title();
    $url         = home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));

    $image = '';
    if (is_singular() && has_post_thumbnail()) {
        $image = (string) get_the_post_thumbnail_url(null, 'large');
    }
    if ($image === '') {
        $logo = podo_opt('brand_logo', get_template_directory_uri() . '/assets/img/logo.png');
        $image = $logo;
    }

    if ($description !== '') {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta property="og:locale" content="uk_UA">' . "\n";
    echo '<meta property="og:type" content="' . (is_single() ? 'article' : 'website') . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description !== '') {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
}, 5);

// Favicon з теми, якщо в Customizer не заданий site icon
add_action('wp_head', function () {
    if (!has_site_icon()) {
        echo '<link rel="icon" type="image/png" href="' . esc_url(get_template_directory_uri() . '/assets/img/logo.png') . '">' . "\n";
    }
}, 6);
