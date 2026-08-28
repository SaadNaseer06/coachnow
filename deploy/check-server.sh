#!/bin/bash
# Run on cPanel Terminal from your Laravel project root:
#   cd ~/public_html/coachnow.serverlinktestwebsites.com
#   bash deploy/check-server.sh

set -u

echo "=== CoachNow server check ==="
echo "PWD: $(pwd)"
echo ""

echo "--- PHP ---"
if command -v php >/dev/null 2>&1; then
  php -v 2>&1 | head -1
else
  echo "ERROR: php not in PATH"
fi

for candidate in /usr/local/bin/ea-php82 /usr/local/bin/ea-php83 /opt/cpanel/ea-php82/root/usr/bin/php; do
  if [ -x "$candidate" ]; then
    echo "Found: $candidate -> $($candidate -v 2>&1 | head -1)"
  fi
done
echo ""

echo "--- Project files ---"
for f in artisan composer.json .env index.php public/index.php vendor/autoload.php; do
  if [ -e "$f" ]; then echo "OK  $f"; else echo "MISSING  $f"; fi
done
echo ""

echo "--- SQLite ---"
if [ -f database/database.sqlite ]; then
  ls -la database/database.sqlite
else
  echo "MISSING database/database.sqlite (create it for SQLite)"
fi
echo ""

echo "--- Permissions ---"
ls -ld storage bootstrap/cache database 2>/dev/null || true
echo ""

echo "--- Artisan test ---"
if [ -f vendor/autoload.php ]; then
  php -d display_errors=1 artisan --version 2>&1 || echo "artisan failed (see above)"
else
  echo "Skip artisan — run: composer install --no-dev --optimize-autoloader"
fi

echo ""
echo "=== Done ==="
