<?php
/**
 * Fallback-шаблон.
 */

defined('ABSPATH') || exit;

get_header();
?>

<main class="section">
  <div class="container">
    <?php while (have_posts()) : the_post(); ?>
      <h1><?php the_title(); ?></h1>
      <div><?php the_content(); ?></div>
    <?php endwhile; ?>
  </div>
</main>

<?php get_footer(); ?>
