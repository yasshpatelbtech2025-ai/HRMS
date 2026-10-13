#!/usr/bin/env bash
# Builds the PHP extensions the app needs (pgsql, pdo_pgsql, zip, gd) for the PHP in the
# Codespaces universal image. That PHP is a source build, so apt's php8.x-* packages don't
# apply. Safe to run repeatedly: built extensions are only re-enabled, never rebuilt.
set -euo pipefail

WANTED=(pgsql pdo_pgsql zip gd)
loaded() { php -m | grep -x "$1" >/dev/null; }   # no grep -q: with pipefail it can report a false miss

missing=()
for ext in "${WANTED[@]}"; do loaded "$ext" || missing+=("$ext"); done
if [ ${#missing[@]} -eq 0 ]; then
    echo "PHP extensions ready: ${WANTED[*]}"
    exit 0
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
PHP_BIN=$(dirname "$(readlink -f "$(command -v php)")")
EXT_DIR=$(php -r 'echo ini_get("extension_dir");')
SCAN_DIR=$(php --ini | sed -n 's/^Scan for additional .ini files in: *//p' | tr -d '"')  # PHP 8.5 quotes the path

to_build=()
for ext in "${missing[@]}"; do [ -f "$EXT_DIR/$ext.so" ] || to_build+=("$ext"); done

if [ ${#to_build[@]} -gt 0 ]; then
    sudo apt-get update -y
    sudo apt-get install -y libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype-dev libwebp-dev

    WORK=$(mktemp -d)
    trap 'rm -rf "$WORK"' EXIT
    curl -fsSL "https://www.php.net/distributions/php-${PHP_VERSION}.tar.gz" \
        | tar xz -C "$WORK" $(printf "php-${PHP_VERSION}/ext/%s " "${to_build[@]}")

    for ext in "${to_build[@]}"; do
        flags=()
        [ "$ext" = gd ] && flags=(--with-jpeg --with-freetype --with-webp)
        (
            cd "$WORK/php-${PHP_VERSION}/ext/$ext"
            "$PHP_BIN/phpize"
            ./configure --with-php-config="$PHP_BIN/php-config" "${flags[@]}"
            make -j"$(nproc)"
            make install
        )
    done
fi

for ext in "${WANTED[@]}"; do echo "extension=$ext"; done > "$SCAN_DIR/20-app-extensions.ini"
rm -f "$SCAN_DIR/20-pgsql.ini"   # replaced by the file above
for ext in "${WANTED[@]}"; do loaded "$ext" && echo "enabled: $ext"; done
