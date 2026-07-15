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
        <span class="stars">★★★★★</span>
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
      <a class="btn" href="<?php echo esc_url(podo_page_url('kontakty')); ?>"><?php echo esc_html(podo_field('cta_btn_label', 'Написати відгук')); ?></a>
    </div>
  </div>
</section>

<?php get_footer(); ?>
