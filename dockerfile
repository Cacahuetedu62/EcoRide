FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libzip-dev zip pkg-config libssl-dev \
    libcurl4-openssl-dev git unzip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN pecl install mongodb \
    && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip pdo pdo_mysql curl

RUN a2dismod mpm_event mpm_worker && \
    a2enmod mpm_prefork && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
CMD ["apache2-foreground"]