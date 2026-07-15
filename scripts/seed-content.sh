#!/bin/sh
# Наповнення сайту контентом з макета.
# Запуск: docker compose run --rm wpcli sh /scripts/seed-content.sh
set -e
wp eval-file /scripts/seed-content.php
