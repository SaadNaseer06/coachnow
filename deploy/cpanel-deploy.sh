#!/bin/bash
# Run on cPanel after git pull (Terminal or .cpanel.yml).
# Usage: bash deploy/cpanel-deploy.sh
# Set APP_DIR to your Laravel root on the server before running, e.g.:
#   export APP_DIR=/home/youruser/coachnow

set -e

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$APP_DIR"

echo "==> Deploying CoachNow in $APP_DIR"

if [ ! -f .env ]; then
  echo "ERROR: .env not found. Copy .env.example to .env and configure APP_KEY, APP_URL, DB_* first."
  exit 1
fi

if command -v composer >/dev/null 2>&1; then
  composer install --no-dev --optimize-autoloader --no-interaction
else
  echo "WARNING: composer not in PATH. Install dependencies manually."
fi

php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan storage:link --no-interaction 2>/dev/null || true

chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

echo "==> Deploy complete."
