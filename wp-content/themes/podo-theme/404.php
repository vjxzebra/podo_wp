<?php
/**
 * Сторінка 404.
 */

defined('ABSPATH') || exit;

get_header();
?>

<div class="container error-page">
  <div class="num">404</div>
  <h1 style="font-size:30px; margin-top:10px;"><?php esc_html_e('Сторінку не знайдено', 'podo'); ?></h1>
  <p><?php esc_html_e('Можливо, її перенесли або видалили. Поверніться на головну — там усе на місці.', 'podo'); ?></p>
  <a class="btn" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('На головну', 'podo'); ?></a>
</div>

<?php get_footer(); ?>
