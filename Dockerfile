# Production image for DajPrezent.pl — nginx + php-fpm + queue worker +
# scheduler in a single container, managed by supervisord. Designed to be
# built and run as-is by Coolify (Dockerfile build pack) on a Hetzner VPS.

########################################
# 1. Frontend assets (Vite build)
########################################
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

########################################
# 2. Runtime image
########################################
FROM php:8.4-fpm-alpine AS runtime

# Runtime shared libraries (kept in the final image).
RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        tzdata \
        icu-libs \
        libzip \
        libpng \
        libjpeg-turbo \
        freetype \
        libxml2 \
        oniguruma \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libxml2-dev \
        oniguruma-dev \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

# Composer binary only — dependencies are installed below, under this same
# PHP runtime, so extension/version requirements always match what actually
# ships (running `composer install` from the separate `composer:2` image
# would resolve against a *different* PHP, silently skipping ext checks and
# executing composer.json's post-autoload-dump artisan hooks with no env).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 www && adduser -G www -u 1000 -D -H www \
    && mkdir -p /run/php /run/nginx /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi \
    && chown -R www:www /run/php /run/nginx /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /var/lib/nginx /var/log/nginx

WORKDIR /var/www/html

COPY --chown=www:www . .
COPY --chown=www:www --from=frontend /app/public/build ./public/build

# post-autoload-dump (artisan package:discover / filament:upgrade) needs a
# booted app with real env vars, which only exist at container start under
# Coolify — so skip composer's scripts here and run them from entrypoint.sh.
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --optimize-autoloader \
        --classmap-authoritative \
        --prefer-dist \
    && chmod -R 775 storage bootstrap/cache \
    && rm -rf docker Dockerfile docker-compose.yml .dockerignore

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/scheduler.sh /usr/local/bin/scheduler.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/scheduler.sh

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf", "-n"]
