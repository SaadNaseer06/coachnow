#!/bin/bash
# Post-deploy script for cPanel. Run from project root after git pull.
set -e

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

PHP="${PHP_BIN:-php}"

if [ -f composer.phar ]; then
  $PHP -d allow_url_fopen=On composer.phar install --no-dev --optimize-autoloader --no-interaction
elif command -v composer >/dev/null 2>&1; then
  $PHP -d allow_url_fopen=On "$(command -v composer)" install --no-dev --optimize-autoloader --no-interaction
else
  echo "Composer not found. Run: curl -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar"
  exit 1
fi

test -f database/database.sqlite || touch database/database.sqlite

$PHP artisan migrate --force --no-interaction
$PHP artisan config:clear --no-interaction
$PHP artisan cache:clear --no-interaction
$PHP artisan view:clear --no-interaction
$PHP artisan config:cache --no-interaction
$PHP artisan route:cache --no-interaction
$PHP artisan view:cache --no-interaction
$PHP artisan storage:link --no-interaction 2>/dev/null || true

chmod -R ug+rwx storage bootstrap/cache database 2>/dev/null || true

echo "Deploy complete."
