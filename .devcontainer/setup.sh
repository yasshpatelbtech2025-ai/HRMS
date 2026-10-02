#!/usr/bin/env bash
# One-shot, idempotent Codespace setup: driver, packages, assets, database.
set -uo pipefail
cd "$(dirname "$0")/.."

bash .devcontainer/install-php-pgsql.sh || echo "WARNING: could not enable pdo_pgsql"

[ -f .env ] || cp .env.example .env
composer install --no-interaction --prefer-dist
grep -q '^APP_KEY=.\+' .env || php artisan key:generate --no-interaction
npm install --no-audit --no-fund && npm run build

# DB_* come from Codespaces secrets and override .env.
if php artisan migrate --force --no-interaction; then
    echo "Database is up to date."
else
    echo "WARNING: migrate failed - check the DB_* Codespaces secrets and that pdo_pgsql is enabled."
fi
