# --- Этап 1: Сборка PHP зависимостей (чтобы получить папку vendor) ---
FROM composer:2 as deps
WORKDIR /app
COPY composer.json composer.lock ./
# Устанавливаем пакеты, чтобы появилась папка vendor/tightenco/ziggy
RUN composer install --ignore-platform-reqs --no-interaction --no-scripts --prefer-dist

# --- Этап 2: Сборка фронтенда (Vue + Vite) ---
FROM node:20 as frontend
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps

# Копируем исходный код
COPY . .

# МАГИЯ: Копируем папку vendor из первого этапа, чтобы Vite её увидел!
COPY --from=deps /app/vendor /app/vendor

RUN npm run build

# --- Этап 3: Финальная сборка бэкенда ---
FROM php:8.3-fpm

# Устанавливаем системные либы
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev default-mysql-client \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копируем проект
COPY . .

# Копируем собранный фронтенд из этапа 2
COPY --from=frontend /app/public/build /var/www/public/build

# Устанавливаем зависимости начисто для продакшена
RUN composer install --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]