# Dockerfile
FROM php:8.3-cli

RUN apt-get update -y && apt-get install -y libmcrypt-dev unzip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install zip

WORKDIR /app
COPY . /app

RUN composer install

EXPOSE 8000
CMD php bin/console server:run 0.0.0.0:8000