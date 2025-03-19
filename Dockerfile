# run composer in separated layer for improve image caching
FROM composer:lts AS build

ENV BUILD_DIR=/build/food-ordering-web-app

RUN mkdir -p BUILD_PATH
WORKDIR $BUILD_DIR

COPY composer.json $BUILD_DIR/composer.json
COPY composer.lock $BUILD_DIR/composer.lock

RUN composer install

FROM php:8.2-apache

ENV BUILD_DIR=/build/food-ordering-web-app
ENV SERVER_PATH=/var/www/html

# enable mod_rewrite and pdo extension for php
RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql

RUN mkdir -p $SERVER_PATH
WORKDIR $SERVER_PATH

COPY public/ $SERVER_PATH/public/
COPY src/ $SERVER_PATH/src/
COPY --from=build $BUILD_DIR/vendor $SERVER_PATH/vendor
COPY .htaccess/ $SERVER_PATH/.htaccess

RUN chown -R www-data:www-data /var/www/html

LABEL maintainer="Miłosz Gilga <miloszgilga@gmail.pl>"

EXPOSE 80
CMD ["apache2-foreground"]
