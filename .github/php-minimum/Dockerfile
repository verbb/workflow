FROM php:8.2.0-cli@sha256:fa840989efc52df732278f59377e780f65d6aa8c384bb9724babf190b49f4c35

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer-home \
    COMPOSER_CACHE_DIR=/tmp/composer-cache

COPY scripts/setup-minimum-php.sh /usr/local/bin/setup-minimum-php
RUN bash /usr/local/bin/setup-minimum-php

# Match the extensions and curl release used to qualify the exact minimum.
# Versioned symbols retain the Debian ABI required by the official PHP binary.
RUN apt-get install -y --no-install-recommends libssl-dev libxslt1-dev \
    && docker-php-ext-install -j2 calendar exif pcntl soap xsl

RUN curl --fail --silent --show-error --location --retry 3 --retry-all-errors \
        --retry-delay 2 --connect-timeout 15 --max-time 120 \
        https://curl.se/download/curl-8.10.1.tar.xz --output /tmp/curl.tar.xz \
    && echo '73a4b0e99596a09fa5924a4fb7e4b995a85fda0d18a2c02ab9cf134bebce04ee  /tmp/curl.tar.xz' | sha256sum --check \
    && tar -xf /tmp/curl.tar.xz -C /tmp \
    && cd /tmp/curl-8.10.1 \
    && ./configure --prefix=/opt/curl --with-openssl --enable-versioned-symbols --disable-static --without-libpsl \
    && make -j2 \
    && make install \
    && echo /opt/curl/lib > /etc/ld.so.conf.d/minimum-curl.conf \
    && ldconfig \
    && PKG_CONFIG_PATH=/opt/curl/lib/pkgconfig docker-php-ext-install curl \
    && rm -rf /tmp/curl* /var/lib/apt/lists/*

WORKDIR /var/www/html
RUN php -r 'echo PHP_VERSION, PHP_EOL; exit(PHP_VERSION === "8.2.0" ? 0 : 1);'
