#!/bin/bash
# Post-deploy: Composer packages + Laravel artisan (runs on every push via GitHub Actions).
set -e

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

log() { echo "[deploy] $*"; }

find_php() {
  for candidate in \
    "${PHP_BIN:-}" \
    /usr/local/bin/ea-php82 \
    /usr/local/bin/php82 \
    /usr/local/bin/php \
    php; do
    [ -z "$candidate" ] && continue
    if command -v "$candidate" >/dev/null 2>&1; then
      if "$candidate" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' 2>/dev/null; then
        echo "$candidate"
        return 0
      fi
    fi
  done
  return 1
}

PHP="$(find_php)" || { log "ERROR: PHP 8.2+ not found"; exit 1; }
log "Using PHP: $PHP ($($PHP -v | head -1))"

run_composer() {
  if [ -f composer.phar ]; then
    $PHP -d allow_url_fopen=On composer.phar "$@"
  elif command -v composer >/dev/null 2>&1; then
    $PHP -d allow_url_fopen=On "$(command -v composer)" "$@"
  else
    log "Downloading composer.phar..."
    curl -sS https://getcomposer.org/installer | $PHP -- --install-dir="$APP_DIR" --filename=composer.phar
    $PHP -d allow_url_fopen=On composer.phar "$@"
  fi
}

log "Installing/updating PHP packages (composer install)..."
run_composer install --no-dev --optimize-autoloader --no-interaction
run_composer dump-autoload --optimize --no-interaction

if [ ! -f .env ]; then
  log "Creating .env from .env.example..."
  cp .env.example .env
  $PHP artisan key:generate --force --no-interaction
fi

test -f database/database.sqlite || touch database/database.sqlite

log "Running database migrations..."
$PHP artisan migrate --force --no-interaction

log "Clearing caches..."
$PHP artisan optimize:clear --no-interaction
$PHP artisan cache:clear --no-interaction

log "Rebuilding caches..."
$PHP artisan optimize --no-interaction

log "Linking storage..."
$PHP artisan storage:link --no-interaction 2>/dev/null || true

chmod -R ug+rwx storage bootstrap/cache database 2>/dev/null || true

log "Deploy complete."
