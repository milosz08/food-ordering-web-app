# run composer in separated layer for improve image caching
FROM composer:lts AS build

ENV BUILD_DIR=/build/food-ordering-web-app

RUN mkdir -p $BUILD_DIR
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

RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/*:80/*:8080/' /etc/apache2/sites-available/000-default.conf

RUN mkdir -p $SERVER_PATH
WORKDIR $SERVER_PATH

COPY --chown=www-data:www-data public/ $SERVER_PATH/public/
COPY --chown=www-data:www-data src/ $SERVER_PATH/src/
COPY --chown=www-data:www-data --from=build $BUILD_DIR/vendor $SERVER_PATH/vendor
COPY --chown=www-data:www-data .htaccess/ $SERVER_PATH/.htaccess

RUN chown -R www-data:www-data /var/www/html

RUN mkdir -p /var/run/apache2 /var/log/apache2 && \
    chown -R www-data:www-data /var/run/apache2 /var/log/apache2 /var/www/html

LABEL maintainer="Miłosz Gilga <miloszgilga@gmail.pl>"

EXPOSE 8080

USER www-data

CMD ["apache2-foreground"]
