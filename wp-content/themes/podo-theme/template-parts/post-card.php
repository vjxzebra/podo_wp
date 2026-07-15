<?php
/**
 * Картка статті блогу.
 *
 * @param array $args {id: int}
 */

defined('ABSPATH') || exit;

$podo_id   = (int) ($args['id'] ?? get_the_ID());
$podo_cats = get_the_category($podo_id);
$podo_cat  = $podo_cats ? $podo_cats[0]->name : '';
?>
<article class="post-card">
  <a class="post-card-media <?php echo has_post_thumbnail($podo_id) ? '' : 'ph'; ?>" href="<?php echo esc_url(get_permalink($podo_id)); ?>">
    <?php if (has_post_thumbnail($podo_id)) : ?>
      <?php echo get_the_post_thumbnail($podo_id, 'podo-card'); ?>
    <?php else : ?>
      <span><?php echo esc_html($podo_cat ?: __('ілюстрація', 'podo')); ?></span>
    <?php endif; ?>
  </a>
  <div class="post-card-body">
    <div class="post-card-meta">
      <?php if ($podo_cat) : ?>
        <span class="badge"><?php echo esc_html($podo_cat); ?></span>
      <?php endif; ?>
      <span class="read-time"><?php echo esc_html(podo_read_time($podo_id)); ?></span>
    </div>
    <h3><a href="<?php echo esc_url(get_permalink($podo_id)); ?>"><?php echo esc_html(get_the_title($podo_id)); ?></a></h3>
    <p class="post-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt($podo_id), 18)); ?></p>
    <div class="post-card-foot">
      <span class="post-card-date"><?php echo esc_html(get_the_date('', $podo_id)); ?></span>
      <a class="arrow-link" href="<?php echo esc_url(get_permalink($podo_id)); ?>"><?php esc_html_e('Читати →', 'podo'); ?></a>
    </div>
  </div>
</article>
