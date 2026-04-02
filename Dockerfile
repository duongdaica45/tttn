FROM php:8.2-cli

# Cài đặt đầy đủ extension để Composer không bị văng
RUN apt-get update && apt-get install -y \
    libpq-dev unzip git curl libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy 2 file này trước để tối ưu cache
COPY composer.json composer.lock ./

# Thiết lập giới hạn bộ nhớ và chạy install
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --no-scripts --optimize-autoloader --ignore-platform-reqs --no-interaction

# Sau đó mới copy toàn bộ code
COPY . .

# Tạo thư mục và phân quyền
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD php artisan config:cache && php artisan route:cache && php artisan serve --host=0.0.0.0 --port=10000