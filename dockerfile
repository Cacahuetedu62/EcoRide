FROM php:8.2-apache

# Installation des extensions PHP
RUN docker-php-ext-install pdo pdo_mysql

# Configuration sécurité
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "expose_php = Off" >> /usr/local/etc/php/php.ini

# Activation des modules Apache nécessaires
RUN a2enmod headers rewrite

# Configuration Apache
COPY security-headers.conf /etc/apache2/conf-enabled/security-headers.conf
COPY .htaccess /var/www/html/.htaccess


# Copie des fichiers
COPY . /var/www/html

EXPOSE 80