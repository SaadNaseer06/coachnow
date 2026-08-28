#!/bin/bash
# Post-deploy script for cPanel. Runs automatically via .cpanel.yml after git pull.
set -e

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

PHP="${PHP_BIN:-php}"

if [ -f composer.phar ]; then
  $PHP -d allow_url_fopen=On composer.phar install --no-dev --optimize-autoloader --no-interaction
elif command -v composer >/dev/null 2>&1; then
  $PHP -d allow_url_fopen=On "$(command -v composer)" install --no-dev --optimize-autoloader --no-interaction
else
  echo "Composer not found. Downloading composer.phar..."
  curl -sS https://getcomposer.org/installer | $PHP -- --install-dir="$APP_DIR" --filename=composer.phar
  $PHP -d allow_url_fopen=On composer.phar install --no-dev --optimize-autoloader --no-interaction
fi

if [ ! -f .env ]; then
  cp .env.example .env
  $PHP artisan key:generate --force --no-interaction
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
