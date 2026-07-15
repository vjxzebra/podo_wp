<?php
/**
 * Template Name: Контакти
 */

defined('ABSPATH') || exit;

get_header();

$podo_phone    = podo_opt('contact_phone', '+380 67 123 45 67');
$podo_address  = podo_opt('contact_address', 'Хмельницький, вул. Проскурівська, 00');
$podo_schedule = podo_opt('contact_schedule', 'Пн–Сб, 9:00–19:00 · Нд — вихідний');
$podo_tg       = podo_opt('social_telegram');
$podo_viber    = podo_opt('social_viber');
$podo_ig       = podo_opt('social_instagram');
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><?php esc_html_e('Головна / Контакти', 'podo'); ?></div>
    <h1><?php echo esc_html(podo_field('hero_title', 'Контакти та запис')); ?></h1>
    <p class="page-hero-sub"><?php echo esc_html(podo_field('hero_subtitle', 'Залиште заявку або зателефонуйте — підберемо зручний час. Приймаємо за попереднім записом.')); ?></p>
  </div>
</section>

<div class="container contacts-layout">
  <div>
    <div class="contact-info">
      <div class="ci">
        <div class="ci-icon">📍</div>
        <div>
          <div class="ci-label"><?php esc_html_e('Адреса', 'podo'); ?></div>
          <div class="ci-value"><?php echo esc_html($podo_address); ?></div>
        </div>
      </div>
      <div class="ci">
        <div class="ci-icon">☎</div>
        <div>
          <div class="ci-label"><?php esc_html_e('Телефон', 'podo'); ?></div>
          <div class="ci-value"><a href="<?php echo esc_url(podo_tel_href($podo_phone)); ?>"><?php echo esc_html($podo_phone); ?></a></div>
        </div>
      </div>
      <div class="ci">
        <div class="ci-icon">🕐</div>
        <div>
          <div class="ci-label"><?php esc_html_e('Графік роботи', 'podo'); ?></div>
          <div class="ci-value"><?php echo esc_html($podo_schedule); ?></div>
        </div>
      </div>
    </div>

    <div class="messengers">
      <?php if ($podo_tg) : ?><a class="messenger messenger--tg" href="<?php echo esc_url($podo_tg); ?>" target="_blank" rel="noopener">Telegram</a><?php endif; ?>
      <?php if ($podo_viber) : ?><a class="messenger messenger--viber" href="<?php echo esc_url($podo_viber, ['http', 'https', 'viber']); ?>" target="_blank" rel="noopener">Viber</a><?php endif; ?>
      <?php if ($podo_ig) : ?><a class="messenger messenger--ig" href="<?php echo esc_url($podo_ig); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
    </div>

    <?php echo podo_map_embed(); // phpcs:ignore WordPress.Security.EscapeOutput -- санітизується у podo_map_embed() ?>
  </div>

  <?php get_template_part('template-parts/booking-form'); ?>
</div>

<?php get_footer(); ?>
