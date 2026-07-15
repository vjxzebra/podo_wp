<?php
/**
 * Шапка сайту.
 */

defined('ABSPATH') || exit;

$podo_phone     = podo_opt('contact_phone', '+380 67 123 45 67');
$podo_cta_label = podo_opt('header_cta_label', 'Записатися');
$podo_brand     = podo_opt('brand_name', 'Катерина Роженко');
$podo_tagline   = podo_opt('brand_tagline', 'Подологія · Хмельницький');
$podo_logo      = podo_opt('brand_logo', get_template_directory_uri() . '/assets/img/logo.png');
$podo_book_url  = podo_page_url('kontakty') . '#zapys';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-header-wrap">
  <header class="site-header">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
      <img src="<?php echo esc_url($podo_logo); ?>" alt="<?php echo esc_attr($podo_brand); ?>" width="42" height="42">
      <span>
        <span class="brand-name"><?php echo esc_html($podo_brand); ?></span><br>
        <span class="brand-tagline"><?php echo esc_html($podo_tagline); ?></span>
      </span>
    </a>

    <nav aria-label="<?php esc_attr_e('Головне меню', 'podo'); ?>">
      <?php
      wp_nav_menu([
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'site-menu',
          'fallback_cb'    => false,
          'depth'          => 1,
      ]);
      ?>
    </nav>

    <div class="header-actions">
      <a class="header-phone" href="<?php echo esc_url(podo_tel_href($podo_phone)); ?>"><?php echo esc_html($podo_phone); ?></a>
      <a class="btn btn--sm" href="<?php echo esc_url($podo_book_url); ?>"><?php echo esc_html($podo_cta_label); ?></a>
    </div>

    <button class="burger" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="<?php esc_attr_e('Відкрити меню', 'podo'); ?>">
      <span></span><span></span><span></span>
    </button>
  </header>

  <div class="mobile-menu" id="mobile-menu">
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'container'      => false,
        'menu_class'     => '',
        'items_wrap'     => '<ul>%3$s</ul>',
        'fallback_cb'    => false,
        'depth'          => 1,
    ]);
    ?>
    <div class="mobile-menu-actions">
      <a class="header-phone" style="display:inline" href="<?php echo esc_url(podo_tel_href($podo_phone)); ?>"><?php echo esc_html($podo_phone); ?></a>
      <a class="btn btn--sm" href="<?php echo esc_url($podo_book_url); ?>"><?php echo esc_html($podo_cta_label); ?></a>
    </div>
  </div>
</div>
