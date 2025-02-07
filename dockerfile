FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
   libzip-dev \
   zip \
   pkg-config \
   libssl-dev \
   libcurl4-openssl-dev \
   git \
   unzip

# Installation de Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Installation des extensions PHP
RUN pecl install mongodb \
   && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini \
   && docker-php-ext-enable mongodb \
   && docker-php-ext-install zip pdo pdo_mysql curl

# Configuration sécurité
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
   && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
   && echo "expose_php = Off" >> $PHP_INI_DIR/conf.d/security.ini

# Activation des modules Apache
RUN a2enmod headers rewrite

# Configuration Apache
COPY security-headers.conf /etc/apache2/conf-enabled/security-headers.conf
COPY .htaccess /var/www/html/.htaccess

# Installation des dépendances Composer
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Copie des fichiers
COPY . /var/www/html
RUN composer dump-autoload --optimize

EXPOSE 80