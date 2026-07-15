<?php
/**
 * Футер сайту.
 */

defined('ABSPATH') || exit;

$podo_phone    = podo_opt('contact_phone', '+380 67 123 45 67');
$podo_address  = podo_opt('contact_address', 'Хмельницький, вул. Проскурівська, 00');
$podo_schedule = podo_opt('contact_schedule', 'Пн–Сб, 9:00–19:00');
$podo_brand    = podo_opt('brand_name', 'Катерина Роженко');
$podo_logo     = podo_opt('brand_logo', get_template_directory_uri() . '/assets/img/logo.png');
$podo_about    = podo_opt('footer_about', 'Медичний догляд за стопами й нігтями у Хмельницькому. Стерильно, безпечно, без болю.');
$podo_copy     = podo_opt('footer_copyright', '© ' . gmdate('Y') . ' Катерина Роженко · Подологічний кабінет. Усі права захищені.');

$podo_socials = [
    'Instagram' => podo_opt('social_instagram'),
    'Telegram'  => podo_opt('social_telegram'),
    'Viber'     => podo_opt('social_viber'),
];
?>

<footer class="site-footer">
  <div class="site-footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-brand">
          <img src="<?php echo esc_url($podo_logo); ?>" alt="" width="40" height="40">
          <div>
            <div class="footer-brand-name"><?php echo esc_html($podo_brand); ?></div>
            <div class="footer-brand-sub"><?php esc_html_e('Подологічний кабінет', 'podo'); ?></div>
          </div>
        </div>
        <p class="footer-about"><?php echo esc_html($podo_about); ?></p>
      </div>

      <div class="footer-col">
        <div class="footer-title"><?php esc_html_e('Сторінки', 'podo'); ?></div>
        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '<ul>%3$s</ul>',
            'fallback_cb'    => false,
            'depth'          => 1,
        ]);
        ?>
      </div>

      <div class="footer-col">
        <div class="footer-title"><?php esc_html_e('Контакти', 'podo'); ?></div>
        <ul>
          <li><a href="<?php echo esc_url(podo_tel_href($podo_phone)); ?>"><?php echo esc_html($podo_phone); ?></a></li>
          <li><?php echo esc_html($podo_address); ?></li>
          <li><?php echo esc_html($podo_schedule); ?></li>
        </ul>
      </div>

      <div class="footer-col">
        <div class="footer-title"><?php esc_html_e('Ми в мережах', 'podo'); ?></div>
        <ul>
          <?php foreach ($podo_socials as $podo_label => $podo_url) : ?>
            <?php if ($podo_url) : ?>
              <li><a href="<?php echo esc_url($podo_url, ['http', 'https', 'viber']); ?>" target="_blank" rel="noopener"><?php echo esc_html($podo_label); ?></a></li>
            <?php else : ?>
              <li><span><?php echo esc_html($podo_label); ?></span></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>

    <div class="footer-bottom"><?php echo esc_html($podo_copy); ?></div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
