FROM php:8.2-apache

# Mettre à jour les paquets et installer Ruby, Bundler et autres dépendances
RUN apt-get update && apt-get install -y \
    libzip-dev zip pkg-config libssl-dev \
    libcurl4-openssl-dev git unzip ruby-full \
    && gem install bundler

# Copier Composer depuis l'image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installer MongoDB (si nécessaire) et autres extensions PHP
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip pdo pdo_mysql curl

# Désactiver les modules MPM en conflit (si nécessaire)
RUN a2dismod mpm_event mpm_worker mpm_prefork

# Recharger Apache
RUN service apache2 restart
