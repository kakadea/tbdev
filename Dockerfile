# syntax=docker/dockerfile:1.7
ARG PHP_IMAGE=php:8.2-apache-bookworm
FROM ${PHP_IMAGE}

LABEL org.opencontainers.image.title="TBDev modernized web"
LABEL org.opencontainers.image.description="TBDev modernization workbench"

ENV APACHE_DOCUMENT_ROOT=/var/www/html \
    APP_ENV=lab \
    TZ=UTC

RUN set -eux; \
    a2enmod rewrite headers expires; \
    docker-php-ext-install mysqli pdo_mysql; \
    rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-tbdev.ini
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY . /var/www/html/

RUN set -eux; \
    mkdir -p /var/lib/tbdev/torrents /var/lib/tbdev/uploads /var/log/tbdev; \
    chown -R www-data:www-data /var/lib/tbdev /var/www/html; \
    find /var/www/html -type f -exec chmod 0644 {} +; \
    find /var/www/html -type d -exec chmod 0755 {} +; \
    chmod 0755 /var/www/html

USER root
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD php -r 'exit((int)!@fsockopen("127.0.0.1", 80, $e, $s, 2));'
