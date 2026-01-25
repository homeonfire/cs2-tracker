# --- Этап 1: Сборка PHP зависимостей (для vendor) ---
FROM composer:2 as deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --ignore-platform-reqs --no-interaction --no-scripts --prefer-dist

# --- Этап 2: Сборка фронтенда (Vue + Vite) ---
FROM node:20 as frontend
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps

COPY . .
# Копируем vendor, чтобы Ziggy заработал
COPY --from=deps /app/vendor /app/vendor

RUN npm run build

# --- Этап 3: Финальная сборка бэкенда ---
FROM php:8.3-fpm

# 1. Добавляем libicu-dev (нужен для intl)
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev default-mysql-client libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Добавляем intl в список расширений
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .
COPY --from=frontend /app/public/build /var/www/public/build

# 3. Добавляем --ignore-platform-reqs, чтобы игнорировать несовпадения версий PHP
RUN composer install --optimize-autoloader --no-dev --ignore-platform-reqs

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]