# Utilisez une image PHP de base sans Apache 
FROM php:8.2-cli  

# Installation des dépendances PHP et des extensions nécessaires 
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

# Copie des fichiers du projet 
COPY . .  

# Permissions 
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app  

# Installation des dépendances Composer 
RUN if [ -f "composer.json" ]; then \
        COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction; \
    fi  

# Exposer le port dynamique de Heroku 
EXPOSE 5000  

# Commande de démarrage pour utiliser PHP intégré 
CMD php -S 0.0.0.0:$PORT public/index.php