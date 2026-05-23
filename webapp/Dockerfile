FROM php:8.1-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
    vim \
    rsyslog \
    && docker-php-ext-install mysqli pdo pdo_mysql sockets \
    && a2enmod rewrite

COPY app/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

RUN mkdir -p /var/log/webapp \
    && chown -R www-data:www-data /var/log/webapp

EXPOSE 80

CMD ["apache2-foreground"]
