# Image PHP 8.2 avec extensions pour Symfony
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip \
    libzip-dev \
    && docker-php-ext-install -j$(nproc) zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensions PHP déjà présentes en 8.2 : mbstring, xml, ctype, iconv

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock* /app/
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . /app/
RUN composer dump-autoload --optimize

# Démo : serveur PHP intégré (port 8000)
# Tests : phpunit

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "tests/App/public", "tests/App/public/index.php"]
