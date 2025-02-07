FROM php:8.2-apache

# Installation des dépendances et extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    pkg-config \
    libssl-dev \
    libcurl4-openssl-dev \
    && pecl install mongodb \
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

# Copie des fichiers
COPY . /var/www/html

EXPOSE 80