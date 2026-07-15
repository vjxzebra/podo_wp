#!/bin/sh
# Щоденний бекап БД та uploads (запускати на сервері кроном).
# cron: 0 3 * * * /opt/podo/scripts/backup.sh /opt/podo /opt/backups
set -e

DIR=${1:-/opt/podo}
OUT=${2:-/opt/backups}
KEEP=14

mkdir -p "$OUT"
cd "$DIR"
STAMP=$(date +%F)

docker compose exec -T db sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' | gzip > "$OUT/db-$STAMP.sql.gz"
tar -czf "$OUT/uploads-$STAMP.tar.gz" wp-content/uploads 2>/dev/null || true

# Ротація: лишаємо останні $KEEP
ls -1t "$OUT"/db-*.sql.gz 2>/dev/null | tail -n +$((KEEP + 1)) | xargs -r rm
ls -1t "$OUT"/uploads-*.tar.gz 2>/dev/null | tail -n +$((KEEP + 1)) | xargs -r rm

echo "OK: $OUT/db-$STAMP.sql.gz"
