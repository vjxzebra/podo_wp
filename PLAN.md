# План розробки: сайт подологічного кабінету Катерини Роженко

WordPress-сайт за макетом `design/Багатосторінковий сайт.dc.html`. Розробка ведеться агентно:
кожна фаза — самодостатнє завдання з чіткими кроками, Definition of Done (DoD) і командами верифікації.
Фази виконуються строго по черзі; всередині фази задачі можна паралелити, якщо вказано.

---

## 1. Цілі та вимоги

- Багатосторінковий сайт: Головна, Послуги, Про кабінет, Блог, Ціни, Відгуки, Контакти.
- **Усі тексти редагуються** з адмінки (ACF-поля на сторінках + глобальні опції).
- **Блог** — стандартні записи WordPress з категоріями та фільтром.
- **Заявки на запис** — форма на Контактах (і CTA по сайту), збереження в адмінці + email-сповіщення.
- **Карта в контактах** налаштовується з адмінки (embed-код Google Maps).
- Кастомні поля — **ACF PRO** (ключ у `.env` → `ACF_PRO_KEY`, у git не потрапляє).
- Локальна розробка в **Docker**, деплой на сервер теж через Docker Compose.
- Мова сайту та адмінки — українська.

## 2. Стек і ключові рішення

| Область | Рішення | Чому |
|---|---|---|
| CMS | WordPress 6.x (образ `wordpress:php8.3-apache`) | стандарт, простий деплой |
| БД | MariaDB 11 | сумісність, легкість |
| Кастомні поля | ACF PRO (ліцензія в `.env`) | вимога замовника |
| Тема | Власна класична тема `podo-theme` (PHP-шаблони) | точна відповідність макету |
| CSS/JS | Vanilla CSS (custom properties) + невеликий JS, **без бандлера** | простота для агентів, макет інлайновий |
| Шрифт | Onest — self-hosted woff2 у темі | продуктивність, без запитів до Google |
| Форма запису | Власний REST-ендпоінт + CPT `booking` | контроль, без зайвих плагінів |
| Пошта (dev) | MailHog | перегляд листів локально |
| Пошта (prod) | WP Mail SMTP (SMTP-креденшели в `.env`) | доставка заявок |
| ЧПУ-слаги | Плагін Cyr-to-Lat | кириличні назви записів |
| Prod-проксі | Caddy (авто-SSL Let's Encrypt) | мінімум конфігурації |
| CI/CD | GitHub Actions → SSH deploy | автоматизація |

Слаги сторінок (з макета): `/` `/poslugy` `/pro-kabinet` `/blog` `/tsiny` `/vidhuky` `/kontakty`.

## 3. Структура репозиторію (цільова)

```
podo_wp/
├── docker-compose.yml            # dev
├── docker-compose.prod.yml       # prod (override)
├── .env / .env.example           # секрети та конфіг (у git — тільки .example)
├── Caddyfile                     # prod reverse proxy + SSL
├── scripts/
│   ├── setup.sh                  # ідемпотентна установка WP: core install, плагіни, ACF PRO, тема, сторінки
│   ├── seed-content.sh           # наповнення контентом з макета (wp-cli)
│   └── deploy.sh                 # деплой на сервер
├── wp-content/
│   ├── themes/podo-theme/
│   │   ├── style.css             # шапка теми + дизайн-система
│   │   ├── functions.php
│   │   ├── inc/                  # setup.php, enqueue.php, cpt.php, acf-options.php, booking.php, mail.php
│   │   ├── acf-json/             # ACF field groups (синхронізація через git)
│   │   ├── assets/{css,js,fonts,img}/
│   │   ├── template-parts/       # nav, footer, cta-band, service-card, review-card, post-card, booking-form
│   │   ├── front-page.php        # Головна
│   │   ├── page-poslugy.php, page-pro-kabinet.php, page-tsiny.php, page-vidhuky.php, page-kontakty.php
│   │   ├── home.php              # архів блогу
│   │   ├── single.php, category.php, 404.php, header.php, footer.php, index.php
│   └── mu-plugins/
│       └── podo-mail.php         # dev: роутинг wp_mail у MailHog
├── design/                       # макети (readonly, джерело правди для UI)
├── CLAUDE.md                     # інструкції для агентів (створюється у Фазі 0)
└── PLAN.md                       # цей файл
```

## 4. Модель даних

### CPT (реєструються кодом у `inc/cpt.php`)

| CPT | Публічний | Призначення | Поля (ACF) |
|---|---|---|---|
| `service` | архіву немає, single вимкнено | картки послуг | номер `01…`, короткий опис, ціна («від 500 ₴») |
| `review` | ні (виводиться списками) | відгуки | ім'я, текст, назва послуги (бейдж), рейтинг (1–5, дефолт 5) |
| `booking` | ні, тільки адмінка | заявки з форми | ім'я, телефон, послуга, коментар, статус (нова/опрацьована), дата |

Таксономія `service_cat` для `service`: Нігті, Шкіра стоп, Спеціальні програми.
Блог — стандартні `post` + `category` (Догляд за стопами, Захворювання нігтів, Діабетична стопа, Дитяча подологія).

### ACF: сторінки (field groups у `acf-json/`, локації — по шаблону сторінки)

- **Головна**: hero (чіп, заголовок, підзаголовок, 3 буліти, тексти кнопок, фото), картка рейтингу (оцінка, к-ть відгуків), заголовки секцій, «Чому обирають» (repeater: іконка, заголовок, текст ×4), CTA-банда (заголовок, текст, кнопка).
- **Послуги**: hero (хлібні крихти статичні), CTA-блок внизу.
- **Про кабінет**: hero (заголовок, текст, фото), статистика (repeater: число, підпис ×4), принципи (repeater ×3), команда (repeater: фото, ім'я, посада, опис), галерея (gallery).
- **Ціни**: групи прайсу (repeater: назва групи → repeater рядків: послуга, ціна), сайдбар запису (заголовок, текст, буліти).
- **Відгуки**: зведення (оцінка, к-ть, розподіл по зірках repeater), CTA-блок.
- **Контакти**: заголовок/підзаголовок, тексти форми.
- **Блог (home.php)**: заголовок, підзаголовок, блок розсилки (опційно, див. Фазу 5).

### ACF Options Page «Налаштування сайту» (`inc/acf-options.php`)

Логотип, назва/підпис у шапці, телефон, адреса, графік роботи, посилання Telegram/Viber/Instagram,
**карта: textarea для iframe-embed Google Maps** (рендер через санітизацію, дозволений тільки `<iframe src="https://www.google.com/maps/embed...">`),
email для заявок, текст копірайту, список послуг для селекта форми (за замовчуванням — з CPT `service`).

---

## 5. Фази розробки

> **Формат виконання для агента:** прочитай фазу цілком → виконай кроки → прожени верифікацію →
> онови чекбокси у цьому файлі → закоміть з повідомленням `phase-N: <що зроблено>`.

### Фаза 0 — Інфраструктура Docker + бутстрап WP

- [x] `docker-compose.yml`: сервіси `db` (mariadb:11, volume `db-data/`), `wordpress` (порт 8080, bind-mount `./wp-content:/var/www/html/wp-content`), `wpcli` (образ `wordpress:cli`, той самий mount + мережа), `mailhog` (порт 8025), `phpmyadmin` (порт 8081, опційно). Всі креденшели — з `.env`.
- [x] `.env.example` — копія `.env` з плейсхолдерами замість значень (ключ ACF НЕ вписувати).
- [x] `scripts/setup.sh` (ідемпотентний, запускається через `wpcli`):
  1. чекає готовності БД; `wp core install` з параметрами з `.env`; мова `uk`;
  2. `wp plugin install "https://connect.advancedcustomfields.com/v2/plugins/download?p=pro&k=${ACF_PRO_KEY}" --activate` (перевірено: старий index.php-ендпоінт віддає 404, робочий — v2); прописати `define('ACF_PRO_LICENSE', ...)` у wp-config через `wp config set`;
  3. `wp plugin install cyr2lat --activate`; видалити akismet/hello;
  4. permalink structure `/%postname%/`;
  5. створити сторінки з переліку слагів (розділ 2) і призначити шаблони; «Головна» → static front page, «Блог» → posts page;
  6. створити категорії блогу і терміни `service_cat`.
- [x] `wp-content/mu-plugins/podo-mail.php`: якщо `WP_ENV=development` — phpmailer на `mailhog:1025`.
- [x] `CLAUDE.md` проєкту: команди запуску, URL-и (сайт :8080, MailHog :8025, PMA :8081), правило «UI верифікувати в зовнішньому Chrome (Claude in Chrome MCP)», конвенції з розділу 6.

**DoD / Верифікація:**
```bash
docker compose up -d && docker compose run --rm wpcli bash /scripts/setup.sh
docker compose run --rm wpcli wp plugin list   # acf-pro: active
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080   # 200
```
Адмінка відкривається українською, всі 7 сторінок створені, ACF PRO активний.

### Фаза 1 — Скелет теми та дизайн-система

- [x] Тема `podo-theme`: `style.css` (шапка), `functions.php` + `inc/setup.php` (theme supports: title-tag, post-thumbnails, menus, html5), `inc/enqueue.php`.
- [x] Дизайн-токени в `assets/css/main.css` як CSS custom properties — **точно з макета**: `--accent:#1C8C63; --leaf:#6DB33E; --ink:#12352A; --band: linear-gradient(160deg,#12352A,#0B3D2E)`, фони `#F4F9F6/#FBFDFC`, радіуси 18/24px, кнопки-пігулки (radius 999px).
- [x] Шрифт Onest (400–800) woff2 self-hosted у `assets/fonts/` + `@font-face`.
- [x] `header.php`: шапка з лого (`design/assets/logo.png` → `assets/img/`), меню (wp_nav_menu, локація `primary`), телефон і кнопка «Записатися» з опцій, активний пункт з підкресленням; бургер + мобільне меню (JS).
- [x] `footer.php`: 4 колонки як у макеті, дані з опцій.
- [x] `template-parts/cta-band.php` — темно-зелена CTA-банда (параметризована).
- [x] Адаптив: брейкпоінт 760px, сітки → 1 колонка, як у контейнер-квері макета.

> Примітка (Windows-машина розробника): порти 8080/8025/8081 перехоплює wslrelay, тому dev-порти
> параметризовані в `.env`: сайт **:8090**, MailHog **:8125**, phpMyAdmin **:8181**.
> Зовнішній Chrome (Claude in Chrome) фіксує емуляцію viewport ~1990px — мобільний адаптив
> верифікується у вбудованому браузері (viewport 375px), desktop — у зовнішньому Chrome.

**DoD:** тема активована (`wp theme activate podo-theme`), на будь-якій сторінці видно шапку/футер, що піксельно відповідають макету на 1200px і 400px. Верифікація — скріншоти в зовнішньому Chrome проти макета.

### Фаза 2 — Модель даних: CPT + ACF

- [ ] `inc/cpt.php`: CPT `service`, `review`, `booking` + таксономія `service_cat` (укр. лейбли, іконки dashicons, `show_in_rest` для service/review).
- [ ] Увімкнути ACF Local JSON (`acf-json/` у темі), створити всі field groups з розділу 4 (можна PHP-експортом через `acf_add_local_field_group` → зберегти в JSON).
- [ ] `inc/acf-options.php`: options page «Налаштування сайту» з полями з розділу 4.
- [ ] Адмін-колонки: для `service` — категорія та ціна; для `review` — послуга; для `booking` — телефон, послуга, статус (з кольоровим бейджем), фільтр за статусом.

**DoD:** у адмінці видно 3 CPT з полями; `acf-json/` містить усі групи; редагування поля в UI оновлює JSON. `wp post-type list` показує CPT.

### Фаза 3 — Шаблони сторінок (можна паралелити по сторінках)

Для кожної сторінки: розмітка з макета, дані з ACF/CPT, жодного захардкоженого тексту (все через `get_field()` з розумними дефолтами `?:`).

- [ ] `front-page.php`: hero + рейтинг-картка; 6 послуг (`service`, orderby menu_order); тизер «Про кабінет»; «Чому обирають» ×4; 3 відгуки; 3 останні пости; CTA-банда.
- [ ] `page-poslugy.php`: секції по термінах `service_cat`, картки з ціною і кнопкою «Записатися» (лінк на /kontakty#zapys); CTA-блок.
- [ ] `home.php` (блог): фільтр-пігулки по категоріях (посилання на `category.php`, активна підсвічена), сітка карток 3×N, пагінація; `category.php` — той самий вигляд.
- [ ] `single.php`: стаття (заголовок, мета: категорія + час читання, контент Gutenberg зі стилями під дизайн, блок «Записатися» внизу).
- [ ] `page-tsiny.php`: групи прайсу з repeater + липкий сайдбар запису.
- [ ] `page-vidhuky.php`: зведення рейтингу з прогрес-барами, CSS-masonry (`columns:3`) відгуків з CPT, CTA.
- [ ] `page-pro-kabinet.php`: hero, смуга статистики, принципи, команда, галерея.
- [ ] `page-kontakty.php`: інфо-блоки з опцій, кнопки месенджерів, карта (див. Фазу 5), форма (див. Фазу 4).
- [ ] `404.php`, `index.php` (fallback).

**DoD:** кожна сторінка візуально відповідає макету (desktop + mobile, звірка скріншотами в зовнішньому Chrome); зміна будь-якого тексту через адмінку відображається на фронті.

### Фаза 4 — Форма заявки на запис

- [ ] `template-parts/booking-form.php`: ім'я*, телефон*, селект послуги (з CPT `service`), коментар; згода на обробку даних (текст з опцій); honeypot-поле; nonce.
- [ ] `inc/booking.php`: REST-роут `POST /wp-json/podo/v1/booking` — валідація (телефон: `+?[\d\s\-()]{9,}`), санітизація, перевірка nonce+honeypot, простий rate-limit (transient по IP, максимум 5/год), створення запису `booking` (статус «нова»), `wp_mail` на email з опцій.
- [ ] `assets/js/booking.js`: fetch-сабміт, стани loading/успіх («Заявку надіслано!» як у макеті)/помилка, без перезавантаження.
- [ ] Адмінка: заявки read-only (поля показані через ACF, редагується лише статус), лічильник нових заявок у меню (bubble).

**DoD / Верифікація:** сабміт форми в зовнішньому Chrome → запис з'являється у «Заявки», лист видно у MailHog (localhost:8025); порожні/невалідні поля дають помилку; honeypot-сабміт тихо ігнорується.

### Фаза 5 — Карта, контакти, дрібниці

- [ ] Рендер карти на Контактах: iframe з options-поля через whitelist-санітайзер (тільки `google.com/maps/embed`), lazy-loading; якщо поле порожнє — плейсхолдер як у макеті.
- [ ] Клікабельні `tel:` посилання всюди, месенджер-кнопки з опцій (ховати, якщо лінк не заданий).
- [ ] Блок розсилки на блозі (**опційно**): форма зберігає email у CPT `subscriber` + експорт CSV кнопкою в адмінці. Якщо не робимо — прибрати блок з шаблону.
- [ ] SEO-мінімум: `wp_title`, meta description з ACF-поля на сторінках, OG-теги, favicon з лого.

**DoD:** вставка embed-коду карти в опції → карта на сайті; видалення → плейсхолдер. Смоук усіх сторінок без PHP-notices (`WP_DEBUG=true` в dev).

### Фаза 6 — Наповнення контентом

- [ ] `scripts/seed-content.sh` (wp-cli, ідемпотентний): 9 послуг, 6 відгуків, 6 статей блогу, прайс — **усі тексти дослівно з макета** (джерело: `design/Багатосторінковий сайт.dc.html`, блок `renderVals()`), ACF-поля сторінок і опції (телефон, адреса, графік).
- [ ] Плейсхолдери зображень: згенерувати нейтральні заглушки (штрихований фон як у макеті) у `assets/img/placeholders/`; реальні фото клієнт завантажить пізніше через адмінку.

**DoD:** свіжий `docker compose up` + `setup.sh` + `seed-content.sh` дає повністю наповнений сайт, ідентичний макету.

### Фаза 7 — QA

- [ ] Прогін усіх сторінок desktop (1200px) + mobile (400px) у зовнішньому Chrome, порівняння з макетом (обидва режими перемикача).
- [ ] Форми: заявка (успіх/помилки/rate-limit), фільтр блогу, пагінація, бургер-меню.
- [ ] `wp core verify-checksums`, `WP_DEBUG` — без notices; консоль браузера — без помилок.
- [ ] Lighthouse: Performance ≥ 90, A11y ≥ 90, SEO ≥ 90 (mobile).
- [ ] Безпека: `wp option get users_can_register` = 0, XML-RPC вимкнено, редактор файлів вимкнено (`DISALLOW_FILE_EDIT`).

**DoD:** чекліст закритий, знайдені дефекти виправлені й реверифіковані.

### Фаза 8 — Деплой на сервер

Передумови (надає користувач перед фазою): сервер Ubuntu 22.04+ з SSH-доступом, домен, SMTP-креденшели. **Агент не виконує деплой без цих даних — запитати.**

- [ ] `docker-compose.prod.yml`: без mailhog/phpmyadmin; + сервіс `caddy` (порти 80/443, volume для сертифікатів); wordpress без публічного порту (тільки внутрішня мережа); `restart: unless-stopped`.
- [ ] `Caddyfile`: домен → `wordpress:80`, авто-HTTPS.
- [ ] Prod `.env` на сервері: `WP_ENV=production`, реальні паролі, `WP_URL=https://<домен>`, SMTP для WP Mail SMTP (`wp plugin install wp-mail-smtp` у prod-setup).
- [ ] `scripts/deploy.sh`: rsync теми/mu-plugins/скриптів на сервер → `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d` → `wp cache flush`.
- [ ] Перенос контенту: `wp db export` локально → import на сервері → `wp search-replace 'http://localhost:8080' 'https://<домен>'` → rsync `wp-content/uploads`.
- [ ] GitHub Actions `.github/workflows/deploy.yml`: on push to `main` → SSH deploy (секрети: `SSH_KEY`, `SSH_HOST`; ключ ACF на сервері вже в `.env`, у CI не передається).
- [ ] Бекапи: cron на сервері — щоденний `mysqldump` + tar uploads, ротація 14 днів.
- [ ] Пост-деплой смоук: сайт по HTTPS, форма шле лист на реальну пошту, адмінка працює.

**DoD:** сайт живий на домені з валідним SSL, заявка з проду доходить на email, CI-деплой проходить з коміту в `main`.

---

## 6. Правила для агентів

1. **Джерело правди для UI** — `design/Багатосторінковий сайт.dc.html`. Кольори, відступи, радіуси, тексти брати звідти, не вигадувати.
2. **Секрети** тільки в `.env` (git-ignored). Ключ ACF PRO — `${ACF_PRO_KEY}`. Ніколи не вписувати ключі у код, компоуз-файли чи цей план.
3. **Жодних текстів у шаблонах** — тільки `get_field()`/`get_option()` з дефолтами. Мета: клієнт редагує все з адмінки.
4. ACF field groups — тільки через `acf-json/` (комітяться). Після зміни полів у адмінці перевіряти, що JSON оновився.
5. **Верифікація UI** — у зовнішньому Chrome (Claude in Chrome MCP), не у вбудованому preview. Порівнювати з макетом на 1200px і 400px.
6. Коміти по фазах: `phase-N: <опис>`. Не комітити `wp-content/plugins/` (ставляться скриптом), `uploads/`, `db-data/`.
7. Після кожної фази — оновити чекбокси в цьому файлі.
8. PHP: WordPress coding standards, екранування виводу (`esc_html`, `esc_url`, `wp_kses` для embed-ів), i18n-функції з text domain `podo`.
9. Якщо крок неможливо виконати (немає доступів, ключ не працює) — зупинитися і спитати користувача, не обходити.
