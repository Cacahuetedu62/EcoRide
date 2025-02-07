FROM php:8.2-apache

# Installation dépendances système
RUN apt-get update && apt-get install -y \
    libzip-dev zip pkg-config libssl-dev \
    libcurl4-openssl-dev git unzip

# Installation Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Extensions PHP
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip pdo pdo_mysql curl

# Configuration Apache/PHP
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "expose_php = Off" >> $PHP_INI_DIR/conf.d/security.ini \
    && a2enmod headers rewrite

WORKDIR /var/www/html

# Configuration Apache et fichiers
COPY composer.* ./
RUN composer install --no-dev --optimize-autoloader
COPY . .
COPY security-headers.conf /etc/apache2/conf-enabled/
COPY .htaccess .

EXPOSE 80