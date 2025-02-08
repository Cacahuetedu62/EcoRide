FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libssl-dev \
    pkg-config

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Installation de l'extension MongoDB
RUN pecl install mongodb && \
    docker-php-ext-enable mongodb

# Activation du module rewrite d'Apache
RUN a2enmod rewrite

# Configuration PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Configuration du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers du projet
COPY . .

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Installation des dépendances Composer si composer.json existe
RUN if [ -f "composer.json" ]; then \
        COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction; \
    fi