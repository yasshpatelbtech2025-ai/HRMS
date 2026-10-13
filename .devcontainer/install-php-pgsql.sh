#!/usr/bin/env bash
# Builds the pgsql + pdo_pgsql extensions for the active PHP (a source build in
# the universal image, so apt's php8.x-pgsql packages don't apply to it).
set -euo pipefail

if php -m | grep -x pdo_pgsql >/dev/null; then
    echo "pdo_pgsql already enabled for PHP $(php -r 'echo PHP_VERSION;')"
    exit 0
fi

SCAN_DIR=$(php --ini | sed -n 's/^Scan for additional .ini files in: *//p' | tr -d '"')  # PHP 8.5 quotes the path
EXT_DIR=$(php -r 'echo ini_get("extension_dir");')

# Already compiled but not switched on (the .ini can go missing while the .so files remain).
if [ -f "$EXT_DIR/pgsql.so" ] && [ -f "$EXT_DIR/pdo_pgsql.so" ]; then
    printf 'extension=pgsql\nextension=pdo_pgsql\n' > "$SCAN_DIR/20-pgsql.ini"
    php -m | grep -E '^(pgsql|pdo_pgsql)$'
    exit 0
fi

if ! dpkg -s libpq-dev >/dev/null 2>&1; then
    sudo apt-get update -y && sudo apt-get install -y libpq-dev
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_BIN=$(dirname "$(readlink -f "$(command -v php)")")
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

printf 'extension=pgsql\nextension=pdo_pgsql\n' > "$SCAN_DIR/20-pgsql.ini"
php -m | grep -E '^(pgsql|pdo_pgsql)$'
