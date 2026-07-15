<?php
/**
 * Головна сторінка.
 */

defined('ABSPATH') || exit;

get_header();

$podo_book_url     = podo_page_url('kontakty') . '#zapys';
$podo_services_url = podo_page_url('poslugy');

$podo_hero_bullets = podo_repeater('hero_bullets', [
    ['text' => 'Медична освіта'],
    ['text' => 'Повна стерилізація'],
    ['text' => 'Безболісно'],
]);
$podo_why_items = podo_repeater('why_items', [
    ['icon' => '✓', 'title' => 'Стерильність МОЗ', 'text' => 'Повний цикл обробки інструменту за стандартами.'],
    ['icon' => '♦', 'title' => 'Європейські методики', 'text' => 'Сучасні протоколи, постійне навчання.'],
    ['icon' => '◐', 'title' => 'Без болю', 'text' => 'Щадні техніки без хірургічного втручання.'],
    ['icon' => '◍', 'title' => 'Індивідуальний план', 'text' => 'Рішення під вашу проблему та спосіб життя.'],
]);
$podo_hero_image  = podo_field('hero_image');
$podo_about_image = podo_field('about_image');

$podo_services = get_posts(['post_type' => 'service', 'numberposts' => 6, 'orderby' => 'menu_order', 'order' => 'ASC']);
$podo_reviews  = get_posts(['post_type' => 'review', 'numberposts' => 3, 'orderby' => 'menu_order', 'order' => 'ASC']);
$podo_posts    = get_posts(['post_type' => 'post', 'numberposts' => 3]);
?>

<!-- Hero -->
<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="chip"><?php echo esc_html(podo_field('hero_chip', 'Медична подологія за європейськими протоколами')); ?></span>
      <h1><?php echo nl2br(esc_html(podo_field('hero_title', "Здорові стопи\nбез болю та хірургії"))); ?></h1>
      <p class="hero-sub"><?php echo esc_html(podo_field('hero_subtitle', 'Кабінет подолога Катерини Роженко у Хмельницькому. Діагностика, лікування та догляд за стопами й нігтями — стерильно, безпечно, з турботою.')); ?></p>
      <div class="hero-actions">
        <a class="btn" href="<?php echo esc_url($podo_book_url); ?>"><?php echo esc_html(podo_field('hero_btn_primary', 'Записатися на прийом')); ?></a>
        <a class="btn btn--ghost" href="<?php echo esc_url($podo_services_url); ?>"><?php echo esc_html(podo_field('hero_btn_secondary', 'Усі послуги')); ?></a>
      </div>
      <div class="hero-bullets">
        <?php foreach ($podo_hero_bullets as $podo_bullet) : ?>
          <span class="hero-bullet"><?php echo esc_html($podo_bullet['text'] ?? ''); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="hero-media">
      <?php if ($podo_hero_image) : ?>
        <img class="hero-photo" src="<?php echo esc_url($podo_hero_image); ?>" alt="<?php echo esc_attr(podo_opt('brand_name', 'Катерина Роженко')); ?>">
      <?php else : ?>
        <div class="ph ph--45"><span><?php esc_html_e('фото: кабінет / процедура', 'podo'); ?></span></div>
      <?php endif; ?>
      <div class="rating-card">
        <span class="stars" aria-hidden="true">★★★★★</span>
        <div>
          <div class="rating-value"><?php echo esc_html(podo_field('rating_value', '4.9 / 5')); ?></div>
          <div class="rating-count"><?php echo esc_html(podo_field('rating_count', '240+ відгуків')); ?></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Послуги -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <div class="eyebrow"><?php echo esc_html(podo_field('services_eyebrow', 'Послуги')); ?></div>
        <h2><?php echo esc_html(podo_field('services_title', 'Напрямки допомоги')); ?></h2>
      </div>
      <a class="arrow-link" href="<?php echo esc_url($podo_services_url); ?>"><?php echo esc_html(podo_field('services_link_label', 'Дивитись усі →')); ?></a>
    </div>
    <div class="grid-3">
      <?php foreach ($podo_services as $podo_service) : ?>
        <?php get_template_part('template-parts/service-card', null, ['id' => $podo_service->ID]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Про кабінет (тизер) -->
<section class="section--tight section--bg">
  <div class="container hero-grid" style="grid-template-columns:.85fr 1.15fr; gap:48px;">
    <div>
      <?php if ($podo_about_image) : ?>
        <img src="<?php echo esc_url($podo_about_image); ?>" alt="<?php echo esc_attr(podo_opt('brand_name', 'Катерина Роженко')); ?>" style="width:100%; aspect-ratio:1/1; object-fit:cover; border-radius:22px;">
      <?php else : ?>
        <div class="ph ph--11"><span><?php esc_html_e('фото: Катерина Роженко', 'podo'); ?></span></div>
      <?php endif; ?>
    </div>
    <div>
      <div class="eyebrow"><?php echo esc_html(podo_field('about_eyebrow', 'Про кабінет')); ?></div>
      <h2 style="margin:12px 0 18px;"><?php echo esc_html(podo_field('about_title', 'Лікар-подолог із медичним підходом')); ?></h2>
      <p style="margin:0 0 16px; font-size:16px; line-height:1.7; color:var(--muted);"><?php echo esc_html(podo_field('about_text', "Понад 7 років допомагаю пацієнтам Хмельницького повертати стопам здоров'я. Працюю за європейськими методиками та використовую лише стерильний інструмент. Кожна процедура — це діагноз, план і контроль результату.")); ?></p>
      <a class="arrow-link" href="<?php echo esc_url(podo_page_url('pro-kabinet')); ?>"><?php echo esc_html(podo_field('about_link_label', 'Детальніше про мене →')); ?></a>
    </div>
  </div>
</section>

<!-- Чому обирають -->
<section class="section">
  <div class="container">
    <h2 style="text-align:center; margin-bottom:40px;"><?php echo esc_html(podo_field('why_title', 'Чому обирають цей кабінет')); ?></h2>
    <div class="grid-4">
      <?php foreach ($podo_why_items as $podo_item) : ?>
        <div class="why-item">
          <div class="why-icon" aria-hidden="true"><?php echo esc_html($podo_item['icon'] ?? '✓'); ?></div>
          <h3><?php echo esc_html($podo_item['title'] ?? ''); ?></h3>
          <p><?php echo esc_html($podo_item['text'] ?? ''); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Відгуки -->
<?php if ($podo_reviews) : ?>
<section class="section--tight section--bg">
  <div class="container">
    <div class="section-head">
      <h2><?php echo esc_html(podo_field('reviews_title', 'Що кажуть пацієнти')); ?></h2>
      <a class="arrow-link" href="<?php echo esc_url(podo_page_url('vidhuky')); ?>"><?php echo esc_html(podo_field('reviews_link_label', 'Усі відгуки →')); ?></a>
    </div>
    <div class="grid-3">
      <?php foreach ($podo_reviews as $podo_review) : ?>
        <?php get_template_part('template-parts/review-card', null, ['id' => $podo_review->ID, 'variant' => 'home']); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Блог -->
<?php if ($podo_posts) : ?>
<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <div class="eyebrow"><?php echo esc_html(podo_field('blog_eyebrow', 'Блог')); ?></div>
        <h2><?php echo esc_html(podo_field('blog_title', "Корисне про здоров'я стоп")); ?></h2>
      </div>
      <a class="arrow-link" href="<?php echo esc_url(podo_page_url('blog')); ?>"><?php echo esc_html(podo_field('blog_link_label', 'До блогу →')); ?></a>
    </div>
    <div class="grid-3">
      <?php foreach ($podo_posts as $podo_post_item) : ?>
        <?php get_template_part('template-parts/post-card', null, ['id' => $podo_post_item->ID]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
get_template_part('template-parts/cta-band', null, [
    'title'        => podo_field('cta_title', 'Готові подбати про свої стопи?'),
    'text'         => podo_field('cta_text', 'Запишіться на консультацію — підберемо зручний час і складемо план догляду саме для вас.'),
    'button_label' => podo_field('cta_btn_label', 'Записатися →'),
]);

get_footer();
