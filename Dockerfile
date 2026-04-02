# Stage 1: Sử dụng PHP CLI để build
FROM php:8.2-cli

# 1. Cài đặt các thư viện hệ thống cần thiết cho Laravel & Composer
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Cài đặt Composer phiên bản mới nhất
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Thiết lập biến môi trường để tránh lỗi bộ nhớ (RAM) trên Render
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app

# 4. Copy file cấu hình trước để tận dụng Docker Cache
COPY composer.json composer.lock ./

# 5. Chạy cài đặt phụ thuộc (Bỏ qua scripts để tránh lỗi khi chưa có code)
# Thêm --ignore-platform-reqs nếu máy local và server khác phiên bản PHP
RUN composer install --no-dev --no-scripts --optimize-autoloader --ignore-platform-reqs

# 6. Copy toàn bộ mã nguồn vào container
COPY . .

# 7. Xử lý quyền hạn cho Laravel (Render cần quyền ghi vào storage)
RUN mkdir -p storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

# 8. Render sử dụng port mặc định là 10000
EXPOSE 10000

# 9. Lệnh khởi chạy: Dọn dẹp cache và start server
# Lưu ý: Không chạy 'key:generate' ở đây, hãy thêm APP_KEY vào Environment Variables trên Render
CMD php artisan config:cache && \
    php artisan route:cache && \
    php artisan serve --host=0.0.0.0 --port=10000