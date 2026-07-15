<?php
/**
 * Template Name: Про кабінет
 */

defined('ABSPATH') || exit;

get_header();

$podo_hero_image = podo_field('hero_image');
$podo_stats = podo_repeater('stats', [
    ['value' => '7+', 'label' => 'років практики'],
    ['value' => '4000+', 'label' => 'прийнятих пацієнтів'],
    ['value' => '15+', 'label' => 'курсів підвищення'],
    ['value' => '100%', 'label' => 'стерильність МОЗ'],
]);
$podo_principles = podo_repeater('principles', [
    ['title' => 'Спочатку діагноз', 'text' => 'Шукаємо причину проблеми, а не маскуємо симптом.'],
    ['title' => 'Стерильність понад усе', 'text' => 'Повний медичний цикл обробки інструменту за стандартами МОЗ.'],
    ['title' => 'Комфорт і без болю', 'text' => 'Щадні техніки, спокійна атмосфера, увага до кожного пацієнта.'],
]);
$podo_team = podo_repeater('team', [
    ['photo' => '', 'name' => 'Катерина Роженко', 'role' => 'Лікар-подолог, засновниця', 'text' => 'Медична освіта, європейські сертифікати. Спеціалізація — врослий ніготь, діабетична стопа, ортоніксія.'],
    ['photo' => '', 'name' => 'Адміністратор', 'role' => 'Запис і супровід', 'text' => 'Допоможе обрати зручний час, відповість на запитання й зустріне вас у кабінеті.'],
]);
$podo_gallery = podo_repeater('gallery', []);
$podo_gallery_ph = ['фото кабінету', 'обладнання', 'стерилізація'];
?>

<section class="about-hero">
  <div class="container about-hero-grid">
    <div>
      <div class="breadcrumbs"><?php esc_html_e('Головна / Про кабінет', 'podo'); ?></div>
      <h1><?php echo nl2br(esc_html(podo_field('hero_title', "Кабінет подолога\nКатерини Роженко"))); ?></h1>
      <p class="about-hero-text"><?php echo esc_html(podo_field('hero_text', 'Затишний приватний кабінет у Хмельницькому, де подологія — це медицина, а не просто педикюр. Тут кожного пацієнта чекає діагностика, чесний план лікування та турбота на кожному етапі.')); ?></p>
      <div class="hero-actions" style="margin-top:0;">
        <a class="btn" href="<?php echo esc_url(podo_page_url('kontakty') . '#zapys'); ?>"><?php echo esc_html(podo_field('btn_primary_label', 'Записатися')); ?></a>
        <a class="btn btn--ghost" href="<?php echo esc_url(podo_page_url('poslugy')); ?>"><?php echo esc_html(podo_field('btn_secondary_label', 'Послуги')); ?></a>
      </div>
    </div>
    <div>
      <?php if ($podo_hero_image) : ?>
        <img src="<?php echo esc_url($podo_hero_image); ?>" alt="<?php echo esc_attr(podo_opt('brand_name', 'Катерина Роженко')); ?>">
      <?php else : ?>
        <div class="ph ph--45"><span><?php esc_html_e('фото: Катерина Роженко', 'podo'); ?></span></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<div class="stats-strip">
  <div class="container stats-grid">
    <?php foreach ($podo_stats as $podo_stat) : ?>
      <div class="stat-cell">
        <div class="stat-value"><?php echo esc_html($podo_stat['value'] ?? ''); ?></div>
        <div class="stat-label"><?php echo esc_html($podo_stat['label'] ?? ''); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<section class="section--tight">
  <div class="container">
    <h2 style="margin-bottom:12px;"><?php echo esc_html(podo_field('principles_title', 'Принципи роботи')); ?></h2>
    <p style="margin:0 0 36px; font-size:16px; color:var(--muted-2); max-width:600px;"><?php echo esc_html(podo_field('principles_text', 'Те, на чому будується довіра пацієнтів і результат кожної процедури.')); ?></p>
    <div class="grid-3">
      <?php foreach ($podo_principles as $podo_i => $podo_principle) : ?>
        <div class="principle-card">
          <div class="principle-num"><?php echo esc_html((string) ($podo_i + 1)); ?></div>
          <h4><?php echo esc_html($podo_principle['title'] ?? ''); ?></h4>
          <p><?php echo esc_html($podo_principle['text'] ?? ''); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section--tight section--bg">
  <div class="container">
    <h2 style="margin-bottom:36px;"><?php echo esc_html(podo_field('team_title', 'Хто вас зустріне')); ?></h2>
    <div class="team-grid">
      <?php foreach ($podo_team as $podo_member) : ?>
        <div class="team-card">
          <?php if (!empty($podo_member['photo'])) : ?>
            <div class="team-photo"><img src="<?php echo esc_url($podo_member['photo']); ?>" alt="<?php echo esc_attr($podo_member['name'] ?? ''); ?>"></div>
          <?php else : ?>
            <div class="team-photo ph"><span><?php esc_html_e('фото', 'podo'); ?></span></div>
          <?php endif; ?>
          <div class="team-body">
            <h4><?php echo esc_html($podo_member['name'] ?? ''); ?></h4>
            <div class="team-role"><?php echo esc_html($podo_member['role'] ?? ''); ?></div>
            <p><?php echo esc_html($podo_member['text'] ?? ''); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section--tight">
  <div class="container">
    <h2 style="margin-bottom:28px;"><?php echo esc_html(podo_field('gallery_title', 'Кабінет зсередини')); ?></h2>
    <div class="gallery-grid">
      <?php if ($podo_gallery) : ?>
        <?php foreach ($podo_gallery as $podo_img_url) : ?>
          <img src="<?php echo esc_url(is_array($podo_img_url) ? ($podo_img_url['url'] ?? '') : $podo_img_url); ?>" alt="">
        <?php endforeach; ?>
      <?php else : ?>
        <?php foreach ($podo_gallery_ph as $podo_label) : ?>
          <div class="ph ph--43"><span><?php echo esc_html($podo_label); ?></span></div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
