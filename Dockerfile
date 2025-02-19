# Utilisez une image PHP de base sans Apache 
FROM php:8.2-cli  

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

# Configuration PHP 
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"  

# Configuration du répertoire de travail 
WORKDIR /app  

# Copie de tous les fichiers nécessaires
COPY composer.json composer.lock* ./
COPY . /app

# Assurez-vous que les dossiers essentiels sont bien copiés
COPY lib /app/lib
COPY public /app/public
# Supprimez ou commentez cette ligne si src n'existe pas
# COPY src /app/src
COPY vendor /app/vendor

# Permissions 
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app  

# Installation des dépendances Composer 
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader

# Exposer le port dynamique de Heroku 
EXPOSE 5000  

# Commande de démarrage pour utiliser PHP intégré 
CMD php -S 0.0.0.0:$PORT public/router.php

