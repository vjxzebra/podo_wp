#!/bin/sh
# Деплой на сервер (з локальної машини або CI).
# Використання: ./scripts/deploy.sh user@host [/opt/podo]
set -e

HOST=${1:?"Використання: deploy.sh user@host [remote_dir]"}
DIR=${2:-/opt/podo}
COMPOSE="docker compose -f docker-compose.yml -f docker-compose.prod.yml"

echo "==> Rsync коду на $HOST:$DIR"
rsync -az --delete \
  --exclude '.git' \
  --exclude '.env' \
  --exclude 'db-data' \
  --exclude 'design' \
  --exclude 'wp-content/uploads' \
  --exclude 'wp-content/plugins' \
  --exclude 'wp-content/upgrade' \
  --exclude 'wp-content/languages' \
  --exclude 'wp-content/debug.log' \
  ./ "$HOST:$DIR/"

echo "==> Перезапуск контейнерів і бутстрап"
ssh "$HOST" "cd $DIR \
  && $COMPOSE up -d --remove-orphans \
  && $COMPOSE run --rm wpcli sh /scripts/setup.sh \
  && $COMPOSE run --rm wpcli wp cache flush"

echo "==> ГОТОВО"
