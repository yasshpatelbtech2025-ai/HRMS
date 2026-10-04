#!/usr/bin/env bash
# Update SRYMPEX to the latest code on main. Run on the server as the app user:
#   sudo -u srympex /var/www/srympex/deploy/deploy.sh
set -euo pipefail
cd /var/www/srympex

echo "→ Maintenance page on"
php artisan down --retry=30 || true
trap 'php artisan up' EXIT

echo "→ Fetching code"
git fetch --quiet origin main
git reset --hard origin/main

echo "→ PHP libraries"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --quiet

echo "→ Building CSS/JS"
npm ci --silent
npm run build --silent

echo "→ Database changes"
php artisan migrate --force

echo "→ Caching config, routes and views"
php artisan optimize

echo "→ Restarting the background worker and PHP"
php artisan queue:restart
sudo /usr/bin/systemctl reload php8.4-fpm

echo "✓ Deployed $(git log -1 --format='%h %s')"
