<?php
/**
 * Картка відгуку.
 *
 * @param array $args {id: int, variant: 'home'|'full'}
 */

defined('ABSPATH') || exit;

$podo_id      = (int) ($args['id'] ?? get_the_ID());
$podo_variant = $args['variant'] ?? 'home';
$podo_text    = (string) get_field('review_text', $podo_id);
$podo_service = (string) get_field('review_service', $podo_id);
$podo_rating  = (int) get_field('review_rating', $podo_id);
$podo_rating  = $podo_rating > 0 ? min($podo_rating, 5) : 5;
$podo_name    = get_the_title($podo_id);
$podo_initial = mb_substr(trim($podo_name), 0, 1);
?>
<div class="card review-card">
  <?php if ($podo_variant === 'full') : ?>
    <div class="review-card-head">
      <span class="stars" aria-hidden="true"><?php echo esc_html(str_repeat('★', $podo_rating)); ?></span>
      <?php if ($podo_service) : ?>
        <span class="badge"><?php echo esc_html($podo_service); ?></span>
      <?php endif; ?>
    </div>
  <?php else : ?>
    <span class="stars" aria-hidden="true"><?php echo esc_html(str_repeat('★', $podo_rating)); ?></span>
  <?php endif; ?>
  <p><?php echo esc_html($podo_text); ?></p>
  <div class="review-author">
    <?php if ($podo_variant === 'full') : ?>
      <span class="review-avatar"><?php echo esc_html($podo_initial); ?></span>
    <?php endif; ?>
    <span><?php echo esc_html($podo_name); ?></span>
  </div>
</div>
