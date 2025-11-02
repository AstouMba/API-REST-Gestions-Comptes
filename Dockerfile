# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP sans scripts post-install
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

# Installer les extensions PHP nécessaires
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# # Créer un fichier .env minimal pour le build

RUN echo "APP_NAME=\"LINGUERE BANK\"" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_KEY=" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "APP_URL=http://localhost" >> .env && \
    echo "" >> .env && \
    echo "LOG_CHANNEL=stack" >> .env && \
    echo "LOG_LEVEL=error" >> .env && \
    echo "" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=switchyard.proxy.rlwy.net" >> .env && \
    echo "DB_PORT=22380" >> .env && \
    echo "DB_DATABASE=railway" >> .env && \
    echo "DB_USERNAME=postgres" >> .env && \
    echo "DB_PASSWORD=EmLFoXLAGGnvTdvYWNWeWSMIwZwgzBJW" >> .env && \
    echo "" >> .env && \
    echo "CACHE_DRIVER=file" >> .env && \
    echo "SESSION_DRIVER=file" >> .env && \
    echo "QUEUE_CONNECTION=sync" >> .env && \
    echo "" >> .env && \
    echo "API_NAME=mbow.astou" >> .env && \
    echo "" >> .env && \
    echo "PASSPORT_PASSWORD_CLIENT_ID=3" >> .env && \
    echo "PASSPORT_PASSWORD_CLIENT_SECRET=w5BPfEnxq9fqr2UORF2iFLs5jkgxciuGDC2riVqK" >> .env && \
    echo "PASSPORT_PRIVATE_KEY=\"-----BEGIN PRIVATE KEY-----\n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\nNBgo/8pp67iVfTQSadJHlvE6BIQwask=\n-----END PRIVATE KEY-----\"" >> .env && \
    echo "PASSPORT_PUBLIC_KEY=\"-----BEGIN PUBLIC KEY-----\nMIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAnVQSSBndssJuWxj2A3rj\nW7PkMSgJlet

# Changer les permissions du fichier .env pour l'utilisateur laravel
RUN chown laravel:laravel .env

# Générer la clé d'application et optimiser
USER laravel
RUN php artisan key:generate --force && \
    php artisan config:cache && \ 
    php artisan route:cache && \
    php artisan view:cache 
USER root

# Copier le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Passer à l'utilisateur non-root
USER laravel

# Exposer le port 8000
EXPOSE 8000

# Commande par défaut
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
