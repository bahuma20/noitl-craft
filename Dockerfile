FROM larueli/php-base-image:8.3

# Copy Laravel project files
COPY . /var/www/html/
COPY site.conf /etc/apache2/sites-available/000-default.conf

USER 0

RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --ignore-platform-reqs
RUN php bin/console asset-map:compile --env=prod

RUN chmod g=rwx -R /var/www/html

USER 1420:0