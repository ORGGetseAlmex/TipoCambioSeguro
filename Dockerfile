FROM php:8.1-apache
WORKDIR /var/www/html
COPY . /var/www/html
RUN docker-php-ext-install mysqli && \
    chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]
