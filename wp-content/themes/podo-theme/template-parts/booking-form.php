<?php
/**
 * Форма заявки на запис (логіка сабміту — inc/booking.php + assets/js/booking.js).
 */

defined('ABSPATH') || exit;

$podo_services = get_posts(['post_type' => 'service', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
?>
<div class="form-card" id="zapys">
  <div data-booking-form-wrap>
    <h2><?php echo esc_html(podo_field('form_title', 'Запис на прийом')); ?></h2>
    <p class="form-card-sub"><?php echo esc_html(podo_field('form_text', 'Заповніть форму — Катерина або адміністратор передзвонять для підтвердження.')); ?></p>
    <form class="booking-form" method="post" novalidate>
      <div>
        <label for="booking-name"><?php esc_html_e("Ваше ім'я", 'podo'); ?></label>
        <input id="booking-name" name="name" type="text" required placeholder="<?php esc_attr_e("Ім'я", 'podo'); ?>">
      </div>
      <div>
        <label for="booking-phone"><?php esc_html_e('Телефон', 'podo'); ?></label>
        <input id="booking-phone" name="phone" type="tel" required placeholder="+380">
      </div>
      <div>
        <label for="booking-service"><?php esc_html_e('Послуга', 'podo'); ?></label>
        <select id="booking-service" name="service">
          <option value=""><?php esc_html_e('Оберіть послугу', 'podo'); ?></option>
          <?php foreach ($podo_services as $podo_service) : ?>
            <option value="<?php echo esc_attr(get_the_title($podo_service)); ?>"><?php echo esc_html(get_the_title($podo_service)); ?></option>
          <?php endforeach; ?>
          <option value="<?php esc_attr_e('Консультація', 'podo'); ?>"><?php esc_html_e('Консультація', 'podo'); ?></option>
        </select>
      </div>
      <div>
        <label for="booking-comment"><?php esc_html_e("Коментар (необов'язково)", 'podo'); ?></label>
        <textarea id="booking-comment" name="comment" rows="3" placeholder="<?php esc_attr_e('Опишіть проблему або зручний час', 'podo'); ?>"></textarea>
      </div>
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute; left:-9999px;">
      <?php if (podo_recaptcha_enabled()) : ?>
        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr(podo_opt('recaptcha_site_key')); ?>" data-theme="dark"></div>
      <?php endif; ?>
      <button type="submit"><?php esc_html_e('Надіслати заявку', 'podo'); ?></button>
      <p class="form-error" data-booking-error role="alert"></p>
      <p class="form-consent"><?php echo esc_html(podo_field('form_consent_text', 'Натискаючи, ви погоджуєтесь на обробку персональних даних')); ?></p>
    </form>
  </div>
  <div class="form-success" data-booking-success hidden>
    <div class="form-success-icon">✓</div>
    <h3><?php echo esc_html(podo_field('form_success_title', 'Заявку надіслано!')); ?></h3>
    <p><?php echo esc_html(podo_field('form_success_text', 'Дякуємо за звернення. Ми зателефонуємо найближчим часом, щоб підтвердити запис.')); ?></p>
  </div>
</div>
