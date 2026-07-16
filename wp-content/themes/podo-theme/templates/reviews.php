<?php
/**
 * Template Name: Відгуки
 */

defined('ABSPATH') || exit;

get_header();

$podo_distribution = podo_repeater('distribution', [
    ['stars_label' => '5 ★', 'percent' => 92],
    ['stars_label' => '4 ★', 'percent' => 6],
    ['stars_label' => '3 ★', 'percent' => 2],
    ['stars_label' => '2 ★', 'percent' => 0],
]);
$podo_reviews = get_posts(['post_type' => 'review', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><?php esc_html_e('Головна / Відгуки', 'podo'); ?></div>
    <h1><?php echo esc_html(podo_field('hero_title', 'Відгуки пацієнтів')); ?></h1>
    <p class="page-hero-sub"><?php echo esc_html(podo_field('hero_subtitle', "Понад 240 реальних відгуків. Ось лише частина історій пацієнтів, яким вдалося повернути здоров'я стопам.")); ?></p>
  </div>
</section>

<section class="section--tight" style="padding-block:48px;">
  <div class="container">
    <div class="reviews-summary">
      <div class="summary-card">
        <div class="summary-value"><?php echo esc_html(podo_field('summary_rating', '4.9')); ?></div>
        <span class="stars" aria-hidden="true">★★★★★</span>
        <div class="summary-label"><?php echo esc_html(podo_field('summary_count_label', 'на основі 240+ відгуків')); ?></div>
      </div>
      <div class="distribution">
        <?php foreach ($podo_distribution as $podo_row) : ?>
          <?php $podo_percent = max(0, min(100, (int) ($podo_row['percent'] ?? 0))); ?>
          <div class="dist-row">
            <span class="dist-label"><?php echo esc_html($podo_row['stars_label'] ?? ''); ?></span>
            <div class="dist-bar"><div class="dist-fill" style="width:<?php echo esc_attr((string) $podo_percent); ?>%;"></div></div>
            <span class="dist-percent"><?php echo esc_html($podo_percent . '%'); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($podo_reviews) : ?>
      <div class="masonry">
        <?php foreach ($podo_reviews as $podo_review) : ?>
          <?php get_template_part('template-parts/review-card', null, ['id' => $podo_review->ID, 'variant' => 'full']); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="promo-box promo-box--center" style="margin-top:36px;">
      <h3><?php echo esc_html(podo_field('cta_title', 'Були на прийомі? Залиште відгук')); ?></h3>
      <p><?php echo esc_html(podo_field('cta_text', 'Ваша думка допомагає іншим пацієнтам зробити перший крок.')); ?></p>
      <button class="btn" type="button" data-review-open><?php echo esc_html(podo_field('cta_btn_label', 'Написати відгук')); ?></button>
    </div>
  </div>
</section>

<?php $podo_services = get_posts(['post_type' => 'service', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']); ?>
<div class="modal" data-review-modal hidden>
  <div class="modal-backdrop" data-review-close></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="review-modal-title">
    <button class="modal-close" type="button" data-review-close aria-label="<?php esc_attr_e('Закрити', 'podo'); ?>">&times;</button>
    <div data-review-form-wrap>
      <h3 id="review-modal-title"><?php echo esc_html(podo_field('modal_title', 'Ваш відгук')); ?></h3>
      <p class="modal-sub"><?php echo esc_html(podo_field('modal_text', 'Розкажіть про свій досвід — відгук з’явиться на сайті після модерації.')); ?></p>
      <form class="review-form" method="post" novalidate>
        <div>
          <label for="review-name"><?php esc_html_e("Ваше ім'я", 'podo'); ?></label>
          <input id="review-name" name="name" type="text" required placeholder="<?php esc_attr_e("Ім'я", 'podo'); ?>">
        </div>
        <fieldset class="rating-field">
          <legend><?php esc_html_e('Оцінка', 'podo'); ?></legend>
          <div class="star-rating">
            <?php for ($podo_star = 5; $podo_star >= 1; $podo_star--) : ?>
              <input type="radio" id="review-star-<?php echo (int) $podo_star; ?>" name="rating" value="<?php echo (int) $podo_star; ?>" <?php checked($podo_star, 5); ?>>
              <?php /* translators: %d — кількість зірок */ ?>
              <label for="review-star-<?php echo (int) $podo_star; ?>" title="<?php echo esc_attr(sprintf(__('%d з 5', 'podo'), $podo_star)); ?>">★</label>
            <?php endfor; ?>
          </div>
        </fieldset>
        <div>
          <label for="review-service"><?php esc_html_e("Послуга (необов'язково)", 'podo'); ?></label>
          <select id="review-service" name="service">
            <option value=""><?php esc_html_e('Оберіть послугу', 'podo'); ?></option>
            <?php foreach ($podo_services as $podo_service) : ?>
              <option value="<?php echo esc_attr(get_the_title($podo_service)); ?>"><?php echo esc_html(get_the_title($podo_service)); ?></option>
            <?php endforeach; ?>
            <option value="<?php esc_attr_e('Консультація', 'podo'); ?>"><?php esc_html_e('Консультація', 'podo'); ?></option>
          </select>
        </div>
        <div>
          <label for="review-text"><?php esc_html_e('Відгук', 'podo'); ?></label>
          <textarea id="review-text" name="text" rows="5" required minlength="10" maxlength="2000" placeholder="<?php esc_attr_e('Що вас турбувало і як пройшов прийом?', 'podo'); ?>"></textarea>
        </div>
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute; left:-9999px;">
        <button type="submit" class="btn"><?php esc_html_e('Надіслати відгук', 'podo'); ?></button>
        <p class="form-error" data-review-error role="alert"></p>
        <p class="modal-consent"><?php echo esc_html(podo_field('modal_consent_text', 'Натискаючи, ви погоджуєтесь на обробку персональних даних')); ?></p>
      </form>
    </div>
    <div class="modal-success" data-review-success hidden>
      <div class="modal-success-icon">✓</div>
      <h3><?php echo esc_html(podo_field('modal_success_title', 'Дякуємо за відгук!')); ?></h3>
      <p><?php echo esc_html(podo_field('modal_success_text', 'Ми опублікуємо його після модерації — зазвичай протягом одного-двох днів.')); ?></p>
    </div>
  </div>
</div>

<?php get_footer(); ?>
