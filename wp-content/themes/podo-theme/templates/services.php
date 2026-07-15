<?php
/**
 * Template Name: Послуги
 */

defined('ABSPATH') || exit;

get_header();

$podo_terms = get_terms([
    'taxonomy'   => 'service_cat',
    'hide_empty' => true,
    'orderby'    => 'id',
    'order'      => 'ASC',
]);
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><?php esc_html_e('Головна / Послуги', 'podo'); ?></div>
    <h1><?php echo esc_html(podo_field('hero_title', 'Послуги подолога')); ?></h1>
    <p class="page-hero-sub"><?php echo esc_html(podo_field('hero_subtitle', 'Повний спектр медичного догляду за стопами й нігтями. Оберіть напрямок або запишіться на консультацію — визначимо проблему разом.')); ?></p>
  </div>
</section>

<?php if (!is_wp_error($podo_terms)) : ?>
  <?php foreach ($podo_terms as $podo_term) : ?>
    <?php
    $podo_group_services = get_posts([
        'post_type'   => 'service',
        'numberposts' => -1,
        'orderby'     => 'menu_order',
        'order'       => 'ASC',
        'tax_query'   => [[ 'taxonomy' => 'service_cat', 'field' => 'term_id', 'terms' => $podo_term->term_id ]],
    ]);
    if (!$podo_group_services) {
        continue;
    }
    ?>
    <section class="service-group">
      <div class="container">
        <div class="service-group-head">
          <h2><?php echo esc_html($podo_term->name); ?></h2>
        </div>
        <div class="grid-3">
          <?php foreach ($podo_group_services as $podo_service) : ?>
            <?php get_template_part('template-parts/service-card', null, ['id' => $podo_service->ID, 'with_button' => true]); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

<div class="section--tight"></div>

<?php
get_template_part('template-parts/cta-band', null, [
    'title'        => podo_field('cta_title', 'Не впевнені, яка послуга потрібна?'),
    'text'         => podo_field('cta_text', 'Запишіться на консультацію — оглянемо стопи, поставимо діагноз і підберемо рішення.'),
    'button_label' => podo_field('cta_btn_label', 'Консультація →'),
]);

get_footer();
