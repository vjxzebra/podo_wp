<?php
/**
 * CTA-банда (темно-зелений блок із кнопкою).
 *
 * @param array $args {title, text, button_label, button_url}
 */

defined('ABSPATH') || exit;

$podo_title  = $args['title'] ?? 'Готові подбати про свої стопи?';
$podo_text   = $args['text'] ?? 'Запишіться на консультацію — підберемо зручний час і складемо план догляду саме для вас.';
$podo_label  = $args['button_label'] ?? 'Записатися →';
$podo_url    = $args['button_url'] ?? podo_page_url('kontakty') . '#zapys';
?>
<div class="cta-band">
  <div>
    <h2><?php echo esc_html($podo_title); ?></h2>
    <p><?php echo esc_html($podo_text); ?></p>
  </div>
  <a class="btn btn--white" href="<?php echo esc_url($podo_url); ?>"><?php echo esc_html($podo_label); ?></a>
</div>
