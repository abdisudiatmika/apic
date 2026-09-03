# syntax=docker/dockerfile:1

# ---------- Stage 1: Composer binary only ----------
FROM composer:2 AS composer

# ---------- Stage 2: PHP-FPM runtime ----------
FROM php:8.4-fpm-alpine AS app

# nodejs/npm stay installed permanently (not a build-only stage) because
# ./src is bind-mounted over /var/www/html at runtime (see docker-compose.yml) —
# there is no "bake compiled assets into the image" step that would survive that
# mount, so `npm run build` has to be run against the live container after it's
# up (docker compose exec app npm run build / scripts/update-production.sh),
# the same way composer/artisan commands already are.
#
# Runtime shared libraries stay installed permanently; the matching "-dev" headers
# and build toolchain are added as a virtual group and removed after compiling the
# extensions, so the compiled .so files still have their runtime deps at container
# start (a plain "apk del <the -dev packages>" also cascade-removes the runtime libs
# that were only pulled in as their dependencies — that was the earlier bug here).
RUN apk add --no-cache \
        bash \
        curl \
        freetype \
        icu-libs \
        icu-data-full \
        libjpeg-turbo \
        libpng \
        libzip \
        oniguruma \
        zip \
        unzip \
        nodejs \
        npm \
    && apk add --no-cache --virtual .build-deps \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk del --no-cache .build-deps

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Hardened PHP defaults for this image (dev-safe; production overrides come later)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Run as a non-root user. UID/GID default to 1000 so bind-mounted files on the host
# stay editable without root-owned files leaking back onto the host filesystem.
# The host GID may already belong to a built-in Alpine group (e.g. macOS "staff" is
# 20, which collides with Alpine's "dialout") — reuse it by name instead of failing.
ARG UID=1000
ARG GID=1000
RUN if getent group "${GID}" >/dev/null 2>&1; then \
        GROUP_NAME=$(getent group "${GID}" | cut -d: -f1); \
    else \
        addgroup -g "${GID}" appuser; \
        GROUP_NAME=appuser; \
    fi \
    && adduser -D -u "${UID}" -G "${GROUP_NAME}" appuser \
    && mkdir -p /var/www/html \
    && chown -R appuser:"${GROUP_NAME}" /var/www/html

WORKDIR /var/www/html
USER appuser

EXPOSE 9000
CMD ["php-fpm"]
