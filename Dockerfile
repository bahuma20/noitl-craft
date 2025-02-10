# Dockerfile
FROM php:8.3-cli

RUN apt-get update -y && apt-get install -y libmcrypt-dev unzip wget

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN docker-php-ext-install zip

WORKDIR /app
COPY . /app

RUN composer install --ignore-platform-reqs
RUN php bin/console asset-map:compile --env=prod

RUN wget https://get.symfony.com/cli/installer -O - | bash
RUN mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

EXPOSE 8000
CMD symfony server:start --port=8000 --allow-all-ip