FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libzip-dev zip pkg-config libssl-dev \
    libcurl4-openssl-dev git unzip

# Installation de Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installation des extensions PHP nécessaires
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip pdo pdo_mysql curl

# Configuration d'Apache/PHP
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf \
    && echo "expose_php = Off" >> $PHP_INI_DIR/conf.d/security.ini \
    && a2enmod headers rewrite

# Définir le répertoire de travail dans le conteneur
WORKDIR /var/www/html

# Copie des fichiers composer.json et composer.lock dans le conteneur
COPY ./composer.json ./composer.lock /var/www/html/

# Installation des dépendances via Composer
RUN composer install --no-dev --optimize-autoloader

# Copie du reste du projet (les fichiers du projet)
COPY . .

# Configuration supplémentaire pour Apache
COPY security-headers.conf /etc/apache2/conf-enabled/
COPY .htaccess .

# Exposer le port 80
EXPOSE 80
