FROM larueli/php-base-image:8.3

# Copy Laravel project files
COPY . /var/www/html/

USER 0

RUN COMPSER_ALLOW_SUPERUSER=true composer install --ignore-platform-reqs
RUN php bin/console asset-map:compile --env=prod

RUN chmod g=rwx -R /var/www/html

USER 1420:0