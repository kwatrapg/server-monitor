# Sentruo — production image (PHP 8.3 + Apache).
FROM php:8.3-apache

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev libonig-dev libpng-dev libzip-dev unzip iputils-ping whois; \
    docker-php-ext-install -j"$(nproc)" pdo_mysql curl mbstring gd zip; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Hardened PHP defaults + Apache modules. The shipped .htaccess (AllowOverride All)
# denies every non-public path; keep DocumentRoot at the app root.
RUN { \
      echo 'expose_php=Off'; \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'session.use_strict_mode=1'; \
      echo 'session.cookie_httponly=1'; \
      echo 'session.cookie_samesite=Lax'; \
      echo 'allow_url_fopen=Off'; \
      echo 'allow_url_include=Off'; \
      echo 'opcache.enable=1'; \
      echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/sentruo.ini; \
    a2enmod headers rewrite; \
    sed -ri 's!/var/www/html!/var/www/sentruo!g' \
      /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf; \
    printf '%s\n' \
      '<Directory /var/www/sentruo>' \
      '  AllowOverride All' \
      '  Require all granted' \
      '  Options -Indexes -MultiViews' \
      '</Directory>' > /etc/apache2/conf-available/sentruo.conf; \
    a2enconf sentruo

WORKDIR /var/www/sentruo

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --no-scripts

COPY . .
RUN chown -R www-data:www-data /var/www/sentruo \
 && find /var/www/sentruo -type d -exec chmod 750 {} \; \
 && find /var/www/sentruo -type f -exec chmod 640 {} \; \
 && chmod 755 /var/www/sentruo/bin/*.sh 2>/dev/null || true

# Provide .env at runtime: compose env_file, a bind-mount to /var/www/sentruo/.env,
# or real environment variables (which always win). Never bake secrets into the image.

HEALTHCHECK --interval=30s --timeout=5s --retries=5 \
  CMD php -r '$c=@file_get_contents("http://127.0.0.1/?route=signin");exit($c!==false?0:1);'

EXPOSE 80
