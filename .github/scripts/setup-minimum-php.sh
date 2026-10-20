#!/usr/bin/env bash
set -euo pipefail

# The official image preserves the exact PHP patch release; setup-php selects a current patch.
php -r 'echo PHP_VERSION, PHP_EOL; exit(PHP_VERSION === "8.2.0" ? 0 : 1);'

# The historical image uses Bullseye; use its archive so old package indexes stay installable.
sed -i 's|deb.debian.org/debian-security|archive.debian.org/debian-security|g; s|deb.debian.org/debian|archive.debian.org/debian|g; /bullseye-updates/d' /etc/apt/sources.list
apt-get -o Acquire::Check-Valid-Until=false update
apt-get install -y --no-install-recommends ca-certificates git unzip libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev

docker-php-ext-configure gd --with-freetype --with-jpeg
docker-php-ext-install -j2 bcmath gd intl pdo_mysql zip
printf 'memory_limit=1G\n' > "$PHP_INI_DIR/conf.d/ci.ini"

# Retry temporary download failures, then verify the installer before executing it.
# Retain the PHP 8.2-compatible Composer 2.8 line.
curl --fail --silent --show-error --location --retry 3 --retry-all-errors --retry-delay 2 --connect-timeout 15 --max-time 60 https://composer.github.io/installer.sig --output /tmp/composer-installer.sig
curl --fail --silent --show-error --location --retry 3 --retry-all-errors --retry-delay 2 --connect-timeout 15 --max-time 60 https://getcomposer.org/installer --output /tmp/composer-setup.php
php -r 'exit(hash_equals(trim(file_get_contents("/tmp/composer-installer.sig")), hash_file("sha384", "/tmp/composer-setup.php")) ? 0 : 1);'
php /tmp/composer-setup.php --version=2.8.12 --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-installer.sig /tmp/composer-setup.php
composer --version
