<?php
/**
 * Template Name: Ціни
 */

defined('ABSPATH') || exit;

get_header();

$podo_phone  = podo_opt('contact_phone', '+380 67 123 45 67');
$podo_groups = podo_repeater('price_groups', []);
$podo_bullets = podo_repeater('sidebar_bullets', [
    ['text' => 'Огляд і діагностика'],
    ['text' => 'Чесна ціна до початку'],
    ['text' => 'План лікування'],
]);
?>

<section class="page-hero">
  <div class="container">
    <div class="breadcrumbs"><?php esc_html_e('Головна / Ціни', 'podo'); ?></div>
    <h1><?php echo esc_html(podo_field('hero_title', 'Ціни на послуги')); ?></h1>
    <p class="page-hero-sub"><?php echo esc_html(podo_field('hero_subtitle', 'Прозорі ціни без прихованих доплат. Остаточна вартість залежить від складності — її озвучують до початку процедури.')); ?></p>
  </div>
</section>

<section class="section--tight">
  <div class="container prices-layout">
    <div class="price-groups">
      <?php foreach ($podo_groups as $podo_group) : ?>
        <div>
          <div class="price-group-head">
            <h2><?php echo esc_html($podo_group['group_title'] ?? ''); ?></h2>
          </div>
          <div class="price-table">
            <?php foreach ((array) ($podo_group['rows'] ?? []) as $podo_row) : ?>
              <div class="price-row">
                <span class="price-row-name"><?php echo esc_html($podo_row['name'] ?? ''); ?></span>
                <span class="price-row-price"><?php echo esc_html($podo_row['price'] ?? ''); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$podo_groups) : ?>
        <p style="color:var(--muted-2);"><?php esc_html_e('Прайс наповнюється — зателефонуйте, і ми зорієнтуємо за вартістю.', 'podo'); ?></p>
      <?php endif; ?>
    </div>

    <aside class="sticky-card">
      <h3><?php echo esc_html(podo_field('sidebar_title', 'Запис на прийом')); ?></h3>
      <p><?php echo esc_html(podo_field('sidebar_text', 'Не знаєте, скільки коштуватиме саме ваш випадок? Запишіться — порахуємо на консультації.')); ?></p>
      <ul class="check-list">
        <?php foreach ($podo_bullets as $podo_bullet) : ?>
          <li><?php echo esc_html($podo_bullet['text'] ?? ''); ?></li>
        <?php endforeach; ?>
      </ul>
      <a class="btn btn--white" href="<?php echo esc_url(podo_page_url('kontakty') . '#zapys'); ?>"><?php echo esc_html(podo_field('sidebar_btn_label', 'Записатися')); ?></a>
      <div class="sticky-card-phone"><?php esc_html_e('або', 'podo'); ?> <a href="<?php echo esc_url(podo_tel_href($podo_phone)); ?>"><?php echo esc_html($podo_phone); ?></a></div>
    </aside>
  </div>
</section>

<?php get_footer(); ?>
