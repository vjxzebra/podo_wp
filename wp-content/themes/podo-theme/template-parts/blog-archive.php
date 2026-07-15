<?php
/**
 * Спільна розмітка архіву блогу (home.php + category.php).
 */

defined('ABSPATH') || exit;

$podo_blog_page_id = (int) get_option('page_for_posts');
$podo_blog_url     = $podo_blog_page_id ? (string) get_permalink($podo_blog_page_id) : home_url('/');
$podo_current_cat  = is_category() ? get_queried_object_id() : 0;
$podo_categories   = get_categories(['hide_empty' => true]);
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><?php esc_html_e('Головна / Блог', 'podo'); ?></div>
    <h1><?php echo esc_html(podo_field('hero_title', "Блог про здоров'я стоп", $podo_blog_page_id ?: false)); ?></h1>
    <p class="page-hero-sub"><?php echo esc_html(podo_field('hero_subtitle', 'Корисні статті про догляд, профілактику та лікування — простою мовою, від практикуючого подолога.', $podo_blog_page_id ?: false)); ?></p>
  </div>
</section>

<div class="container" style="padding-top:40px;">
  <div class="pills">
    <a class="pill <?php echo $podo_current_cat ? '' : 'pill--active'; ?>" href="<?php echo esc_url($podo_blog_url); ?>"><?php esc_html_e('Всі', 'podo'); ?></a>
    <?php foreach ($podo_categories as $podo_cat) : ?>
      <a class="pill <?php echo $podo_current_cat === $podo_cat->term_id ? 'pill--active' : ''; ?>" href="<?php echo esc_url(get_category_link($podo_cat)); ?>"><?php echo esc_html($podo_cat->name); ?></a>
    <?php endforeach; ?>
  </div>
</div>

<section style="padding:36px 0 56px;">
  <div class="container">
    <?php if (have_posts()) : ?>
      <div class="grid-3" style="gap:24px;">
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/post-card', null, ['id' => get_the_ID()]); ?>
        <?php endwhile; ?>
      </div>
      <div class="pagination">
        <?php the_posts_pagination(['prev_text' => '←', 'next_text' => '→']); ?>
      </div>
    <?php else : ?>
      <p style="color:var(--muted-2);"><?php esc_html_e('У цій категорії поки немає статей.', 'podo'); ?></p>
    <?php endif; ?>
  </div>
</section>
