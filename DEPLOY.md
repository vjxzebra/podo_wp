# Деплой на сервер

## Передумови

- Сервер Ubuntu 22.04+ з Docker і compose-плагіном (`curl -fsSL https://get.docker.com | sh`)
- Домен з A-записом на IP сервера (SSL Caddy отримає сам)
- SMTP-креденшели для відправки заявок (Brevo/Mailgun/будь-який SMTP)

## Перший деплой

```bash
# 1. На сервері: створити каталог і .env
ssh user@server "mkdir -p /opt/podo"
scp .env.example user@server:/opt/podo/.env
# відредагувати /opt/podo/.env: розкоментувати секцію Production,
# заповнити WP_ENV, WP_URL, SITE_DOMAIN, SMTP_*, паролі БД, ACF_PRO_KEY,
# WP_ADMIN_* (реальний пароль!)

# 2. З локальної машини: код + запуск
./scripts/deploy.sh user@server /opt/podo
```

`deploy.sh` робить rsync коду, `docker compose up -d` (з prod-оверлеєм: без MailHog/PMA, з Caddy), і проганяє ідемпотентний `setup.sh` (core install за потреби, ACF PRO, WP Mail SMTP, сторінки).

## Перенос контенту з dev

```bash
# локально
docker compose run --rm wpcli wp db export - > dump.sql
scp dump.sql user@server:/opt/podo/

# на сервері
cd /opt/podo
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm wpcli wp db import /var/www/html/../dump.sql   # або через volume: скопіювати dump у ./scripts/ і імпортувати звідти
docker compose -f docker-compose.yml -f docker-compose.prod.yml run --rm wpcli wp search-replace 'http://localhost:8090' 'https://<домен>' --all-tables
# uploads (якщо є)
rsync -az wp-content/uploads/ user@server:/opt/podo/wp-content/uploads/
```

Альтернатива без переносу: на чистому проді просто виконати
`docker compose ... run --rm wpcli sh /scripts/seed-content.sh` — сайт наповниться контентом з макета.

## CI/CD (GitHub Actions)

`.github/workflows/deploy.yml` деплоїть на кожен push у `main`.
Секрети репозиторію: `SSH_KEY` (приватний ключ деплою), `SSH_HOST` (`user@host`), опційно `REMOTE_DIR`.

## Бекапи

На сервері: `crontab -e` →
```
0 3 * * * /opt/podo/scripts/backup.sh /opt/podo /opt/backups
```
Щоденний dump БД + tar uploads, ротація 14 днів.

## Пост-деплой чекліст

- [ ] https://домен відкривається з валідним сертифікатом
- [ ] Форма на /kontakty/ шле лист на реальну пошту (WP Mail SMTP → Email Test)
- [ ] Адмінка працює, ACF PRO активний
- [ ] `wp option get blog_public` = 1 (індексація дозволена)
- [ ] **reCAPTCHA v3**: створити ключі (https://www.google.com/recaptcha/admin/create, тип **reCAPTCHA v3**, домен продакшену) і вставити в Налаштування сайту → Заявки. Без ключів захист вимкнений (лишаються honeypot + rate-limit). Після ввімкнення: моніторити score у debug-лозі при скаргах на відхилені заявки (поріг 0.5 — константа PODO_RECAPTCHA_MIN_SCORE у inc/booking.php).
