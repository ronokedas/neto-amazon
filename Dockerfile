FROM php:8.2-apache

# Instalar extensoes PHP necessarias
RUN docker-php-ext-install pdo pdo_mysql mysqli && \
    docker-php-ext-enable pdo pdo_mysql mysqli

# Instalar utilitarios uteis
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependencias do Composer (se composer.json existir)
WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-dev || true; fi

# Ajustar permissoes
RUN mkdir -p /var/www/html/uploads /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads \
    && chmod -R 777 /var/www/html/logs

# Configurar PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Porta
EXPOSE 80

CMD ["apache2-foreground"]