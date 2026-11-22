FROM php:8.3-apache

# 1. Install PHP extensions (including raphf + pecl_http) and Composer via php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer pdo_mysql opcache intl http ctype iconv

# 2. Enable Apache mod_rewrite & point DocumentRoot to /var/www/html/public
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 3. Copy composer files & install production dependencies (cached layer)
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# 4. Copy all local project files directly with www-data ownership
COPY --chown=www-data:www-data . .

# 5. Finalize autoloader, download importmap JS vendors, compile AssetMapper, and fix permissions
RUN APP_ENV=prod composer dump-autoload --optimize --no-dev \
    && php bin/console importmap:install --env=prod \
    && php bin/console asset-map:compile --env=prod \
    && chown -R www-data:www-data /var/www/html