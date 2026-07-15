# podo_wp — сайт подологічного кабінету (WordPress + Docker)

Сайт Катерини Роженко (подологія, Хмельницький). План розробки і поточний стан фаз: **PLAN.md** (чекбокси оновлювати після кожної фази).

## Джерело правди для UI

`design/Багатосторінковий сайт.dc.html` — інтерактивний макет усіх 7 сторінок (desktop + mobile).
Кольори, тексти, відступи брати звідти. Дані макета — у блоці `renderVals()` внизу файлу.

## Команди

```bash
docker compose up -d                                  # запуск середовища
docker compose run --rm wpcli sh /scripts/setup.sh    # бутстрап WP (ідемпотентний)
docker compose run --rm wpcli wp <command>            # будь-яка wp-cli команда
docker compose logs wordpress --tail 50               # логи PHP
```

## URL-и (dev)

| Що | URL |
|---|---|
| Сайт | http://localhost:8090 |
| Адмінка | http://localhost:8090/wp-admin (креденшели в `.env`) |
| MailHog (пошта) | http://localhost:8125 |
| phpMyAdmin | http://localhost:8181 |

**Увага:** порти 8080/8025/8081 на цій машині зайняті wslrelay (інший проєкт у WSL) — тому нестандартні. Порти параметризовані в `.env` (`WP_PORT`, `MAILHOG_PORT`, `PMA_PORT`).

## Структура

- `wp-content/themes/podo-theme/` — вся кастомна розробка (тема)
- `wp-content/themes/podo-theme/acf-json/` — ACF field groups (комітяться, синхронізуються автоматично)
- `wp-content/mu-plugins/` — службові плагіни (dev-пошта)
- `scripts/` — setup.sh, seed-content.sh, deploy.sh
- `wp-content/plugins/`, `wp-content/uploads/`, `db-data/` — **не в git**, ставляться скриптом

## Конвенції

1. Секрети тільки в `.env` (git-ignored). Ключ ACF PRO — `${ACF_PRO_KEY}`. Ніколи не хардкодити в код/компоуз/доки.
2. Жодних текстів у шаблонах — тільки `get_field()`/опції з дефолтами. Клієнт редагує все з адмінки.
3. PHP: WP coding standards, escape всього виводу (`esc_html`, `esc_url`, `wp_kses`), text domain `podo`.
4. CSS: vanilla, custom properties з макета (`--accent:#1C8C63`, `--leaf:#6DB33E`, `--ink:#12352A`), брейкпоінт 760px. Без бандлерів.
5. Верифікація UI — зовнішній Chrome (Claude in Chrome MCP), НЕ вбудований preview. Звіряти з макетом на 1200px і 400px.
6. Коміти: `phase-N: <опис>`. Після фази — оновити чекбокси в PLAN.md.
7. Мова сайту й адмінки — українська.
