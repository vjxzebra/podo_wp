#!/bin/sh
# Ідемпотентний бутстрап WordPress (dev і prod).
# Запуск: docker compose run --rm wpcli sh /scripts/setup.sh
set -e

cd /var/www/html

log() { echo "==> $1"; }

log "Чекаю на wp-config.php (генерує контейнер wordpress)..."
i=0
until [ -f wp-config.php ]; do
  i=$((i + 1))
  [ "$i" -gt 60 ] && echo "ПОМИЛКА: wp-config.php не з'явився. Спершу: docker compose up -d" && exit 1
  sleep 2
done

log "Чекаю на базу даних..."
i=0
until wp db check >/dev/null 2>&1; do
  i=$((i + 1))
  [ "$i" -gt 60 ] && echo "ПОМИЛКА: БД недоступна" && exit 1
  sleep 2
done

if ! wp core is-installed 2>/dev/null; then
  log "Встановлюю WordPress ($WP_URL)..."
  wp core install \
    --url="$WP_URL" \
    --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASSWORD" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --skip-email
else
  log "WordPress вже встановлено, пропускаю core install"
fi

log "Мова: українська"
wp language core install uk >/dev/null 2>&1 || true
wp language core activate uk >/dev/null 2>&1 || true

log "Базові опції"
wp option update blogdescription "Подологія · Хмельницький" >/dev/null
wp option update timezone_string "Europe/Kyiv" >/dev/null
wp option update date_format "d.m.Y" >/dev/null
wp option update time_format "H:i" >/dev/null
wp option update users_can_register 0 >/dev/null
wp rewrite structure "/%postname%/" >/dev/null

log "ACF PRO"
if ! wp plugin is-installed advanced-custom-fields-pro 2>/dev/null; then
  if [ -z "$ACF_PRO_KEY" ]; then
    echo "ПОМИЛКА: ACF_PRO_KEY не заданий у .env"
    exit 1
  fi
  ACF_URL="https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_PRO_KEY}"
  if ! wp plugin install "$ACF_URL" >/dev/null; then
    echo "   пряма установка не вдалась, пробую через curl..."
    curl -fsSL -o /tmp/acf-pro.zip "$ACF_URL"
    wp plugin install /tmp/acf-pro.zip >/dev/null
    rm -f /tmp/acf-pro.zip
  fi
fi
wp plugin activate advanced-custom-fields-pro >/dev/null 2>&1 || true
wp config set ACF_PRO_LICENSE "$ACF_PRO_KEY" --type=constant >/dev/null 2>&1 || true

log "Плагіни: cyr2lat, чистка стандартних"
wp plugin is-installed cyr2lat 2>/dev/null || wp plugin install cyr2lat >/dev/null
wp plugin activate cyr2lat >/dev/null 2>&1 || true
wp plugin delete akismet hello >/dev/null 2>&1 || true

if [ "${WP_ENV:-development}" = "production" ]; then
  log "Прод: WP Mail SMTP (конфіг через WPMS_* константи з .env)"
  wp plugin is-installed wp-mail-smtp 2>/dev/null || wp plugin install wp-mail-smtp >/dev/null
  wp plugin activate wp-mail-smtp >/dev/null 2>&1 || true
fi

log "Сторінки"
# Прибираємо стандартну демо-сторінку
_sample=$(wp post list --post_type=page --name=sample-page --field=ID --posts_per_page=1)
[ -n "$_sample" ] && wp post delete "$_sample" --force >/dev/null

# create_page <slug> <title> -> друкує ID
create_page() {
  _id=$(wp post list --post_type=page --name="$1" --post_status=publish --field=ID --posts_per_page=1)
  if [ -z "$_id" ]; then
    _id=$(wp post create --post_type=page --post_status=publish --post_title="$2" --post_name="$1" --porcelain)
    echo "   створено: $2 (#$_id)" >&2
  fi
  echo "$_id"
}

HOME_ID=$(create_page "golovna" "Головна")
SERVICES_ID=$(create_page "poslugy" "Послуги")
ABOUT_ID=$(create_page "pro-kabinet" "Про кабінет")
BLOG_ID=$(create_page "blog" "Блог")
PRICES_ID=$(create_page "tsiny" "Ціни")
REVIEWS_ID=$(create_page "vidhuky" "Відгуки")
CONTACTS_ID=$(create_page "kontakty" "Контакти")

# Шаблони сторінок (ACF location rules прив'язані до page_template)
wp post meta update "$SERVICES_ID" _wp_page_template "templates/services.php" >/dev/null
wp post meta update "$ABOUT_ID" _wp_page_template "templates/about.php" >/dev/null
wp post meta update "$PRICES_ID" _wp_page_template "templates/prices.php" >/dev/null
wp post meta update "$REVIEWS_ID" _wp_page_template "templates/reviews.php" >/dev/null
wp post meta update "$CONTACTS_ID" _wp_page_template "templates/contacts.php" >/dev/null

wp option update show_on_front page >/dev/null
wp option update page_on_front "$HOME_ID" >/dev/null
wp option update page_for_posts "$BLOG_ID" >/dev/null

log "Категорії блогу"
for cat in "Догляд за стопами" "Захворювання нігтів" "Діабетична стопа" "Дитяча подологія"; do
  wp term create category "$cat" >/dev/null 2>&1 || true
done

# Терміни service_cat реєструє тема (Фаза 2) — створюємо, якщо таксономія вже є
if wp taxonomy list --field=name 2>/dev/null | grep -q "^service_cat$"; then
  log "Категорії послуг"
  for cat in "Нігті" "Шкіра стоп" "Спеціальні програми"; do
    wp term create service_cat "$cat" >/dev/null 2>&1 || true
  done
fi

log "Головне меню"
# Меню — терм таксономії nav_menu; `wp menu list --field=name` повертає порожнє (wp-cli quirk)
if ! wp term list nav_menu --field=slug 2>/dev/null | grep -qx "main"; then
  wp menu create "main" >/dev/null
  for pid in "$HOME_ID" "$SERVICES_ID" "$ABOUT_ID" "$BLOG_ID" "$PRICES_ID" "$REVIEWS_ID" "$CONTACTS_ID"; do
    wp menu item add-post main "$pid" >/dev/null
  done
fi
# Локація primary з'явиться після активації теми (Фаза 1)
wp menu location assign main primary >/dev/null 2>&1 || true

# Тема активується, коли з'явиться (Фаза 1)
if wp theme list --field=name 2>/dev/null | grep -q "^podo-theme$"; then
  wp theme activate podo-theme >/dev/null 2>&1 || true
  wp menu location assign main primary >/dev/null 2>&1 || true
fi

wp rewrite flush >/dev/null

log "ГОТОВО. Сайт: $WP_URL | Адмінка: $WP_URL/wp-admin ($WP_ADMIN_USER)"
