<?php
/**
 * Стаття блогу.
 */

defined('ABSPATH') || exit;

get_header();

while (have_posts()) :
    the_post();
    $podo_cats = get_the_category();
    ?>
    <section class="article-hero">
      <div class="container">
        <div class="post-card-meta" style="margin-bottom:0;">
          <?php if ($podo_cats) : ?>
            <a class="badge" href="<?php echo esc_url(get_category_link($podo_cats[0])); ?>"><?php echo esc_html($podo_cats[0]->name); ?></a>
          <?php endif; ?>
          <span class="read-time"><?php echo esc_html(podo_read_time(get_the_ID())); ?></span>
        </div>
        <h1><?php the_title(); ?></h1>
        <div class="article-meta">
          <span><?php echo esc_html(get_the_date()); ?></span>
        </div>
      </div>
    </section>

    <div class="container">
      <article class="article-body">
        <?php if (has_post_thumbnail()) : ?>
          <?php the_post_thumbnail('large', ['style' => 'border-radius:16px; margin-bottom:24px;']); ?>
        <?php endif; ?>
        <?php the_content(); ?>
      </article>
    </div>
    <?php
endwhile;

get_template_part('template-parts/cta-band');
get_footer();
