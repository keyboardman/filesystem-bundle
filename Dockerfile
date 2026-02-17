# Image PHP 8.2 avec extensions pour Symfony
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip \
    libzip-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install -j$(nproc) zip \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extensions PHP déjà présentes en 8.2 : mbstring, xml, ctype, iconv

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier d'abord les fichiers de dépendances et les scripts
COPY composer.json composer.lock* /app/
COPY scripts/install-composer.sh scripts/dump-autoload.sh /app/scripts/
RUN chmod +x /app/scripts/*.sh && /app/scripts/install-composer.sh

# Copier le reste du code après l'installation des dépendances
# Cela permet de réutiliser le cache Docker si seul le code source change
COPY . /app/
RUN chmod +x /app/scripts/*.sh && /app/scripts/dump-autoload.sh

# Démo : serveur PHP intégré (port 8000)
# Tests : phpunit

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "tests/App/public", "tests/App/public/index.php"]
