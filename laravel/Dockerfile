FROM php:8.3-fpm

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN usermod -u ${USER_ID} www-data && groupmod -g ${GROUP_ID} www-data

RUN apt-get update && apt-get install -y \
    git curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    redis-tools \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY laravel/composer.json composer.json
COPY laravel/composer.lock composer.lock

RUN composer install --no-scripts --optimize-autoloader --prefer-dist

COPY laravel/ /var/www/html/

COPY docker/php/conf.d/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY docker/php/entrypoint-laravel.sh /usr/local/bin/entrypoint-laravel.sh
COPY docker/php/entrypoint-worker.sh /usr/local/bin/entrypoint-worker.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod +x /usr/local/bin/entrypoint-laravel.sh \
    && chmod +x /usr/local/bin/entrypoint-worker.sh

RUN echo "alias ll='ls -l'" >> /root/.bashrc

EXPOSE 9000

# Default entrypoint is set in docker-compose
