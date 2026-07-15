<?php
/**
 * Картка послуги.
 *
 * @param array $args {id: int, with_button: bool}
 */

defined('ABSPATH') || exit;

$podo_id          = (int) ($args['id'] ?? get_the_ID());
$podo_with_button = !empty($args['with_button']);
$podo_number      = (string) get_field('service_number', $podo_id);
$podo_short       = (string) get_field('service_short', $podo_id);
$podo_price       = (string) get_field('service_price', $podo_id);
?>
<div class="card service-card">
  <?php if ($podo_number) : ?>
    <div class="service-num"><?php echo esc_html($podo_number); ?></div>
  <?php endif; ?>
  <h3><?php echo esc_html(get_the_title($podo_id)); ?></h3>
  <p><?php echo esc_html($podo_short); ?></p>
  <?php if ($podo_with_button) : ?>
    <div class="service-card-foot">
      <span class="service-price"><?php echo esc_html($podo_price); ?></span>
      <a class="service-book-link" href="<?php echo esc_url(podo_page_url('kontakty') . '#zapys'); ?>"><?php esc_html_e('Записатися', 'podo'); ?></a>
    </div>
  <?php else : ?>
    <div class="service-price"><?php echo esc_html($podo_price); ?></div>
  <?php endif; ?>
</div>
