FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

# Render web services commonly bind to port 10000.
RUN sed -ri 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:10000>/g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000
