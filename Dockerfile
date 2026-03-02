FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork

WORKDIR /var/www/html
COPY . /var/www/html

RUN chmod +x /var/www/html/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["/var/www/html/docker-entrypoint.sh"]
