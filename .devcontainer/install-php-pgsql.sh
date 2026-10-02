#!/usr/bin/env bash
# Builds the pgsql + pdo_pgsql extensions for the active PHP (a source build in
# the universal image, so apt's php8.x-pgsql packages don't apply to it).
set -euo pipefail

if php -m | grep -qx pdo_pgsql; then
    echo "pdo_pgsql already enabled for PHP $(php -r 'echo PHP_VERSION;')"
    exit 0
fi

if ! dpkg -s libpq-dev >/dev/null 2>&1; then
    sudo apt-get update -y && sudo apt-get install -y libpq-dev
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_BIN=$(dirname "$(readlink -f "$(command -v php)")")
SCAN_DIR=$(php --ini | sed -n 's/^Scan for additional .ini files in: *//p')
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

curl -fsSL "https://www.php.net/distributions/php-${PHP_VERSION}.tar.gz" \
    | tar xz -C "$WORK" "php-${PHP_VERSION}/ext/pgsql" "php-${PHP_VERSION}/ext/pdo_pgsql"

for ext in pgsql pdo_pgsql; do
    (
        cd "$WORK/php-${PHP_VERSION}/ext/$ext"
        "$PHP_BIN/phpize"
        ./configure --with-php-config="$PHP_BIN/php-config"
        make -j"$(nproc)"
        make install
    )
done

# The scan directory may not exist yet and may be root-owned.
INI_LINES=$'extension=pgsql\nextension=pdo_pgsql\n'
if mkdir -p "$SCAN_DIR" 2>/dev/null && printf '%s' "$INI_LINES" > "$SCAN_DIR/20-pgsql.ini" 2>/dev/null; then
    :
else
    sudo mkdir -p "$SCAN_DIR"
    printf '%s' "$INI_LINES" | sudo tee "$SCAN_DIR/20-pgsql.ini" >/dev/null
fi
php -m | grep -E '^(pgsql|pdo_pgsql)$'
