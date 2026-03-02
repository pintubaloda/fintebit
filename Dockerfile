FROM php:8.2-cli

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html
COPY . /var/www/html

RUN chmod +x /var/www/html/docker-entrypoint.sh

EXPOSE 8080

CMD ["/var/www/html/docker-entrypoint.sh"]
