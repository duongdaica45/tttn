# FROM php:8.2-cli

# # Cài đặt các extension cần thiết cho PostgreSQL và Mail
# RUN apt-get update && apt-get install -y \
#     libpq-dev unzip git curl libzip-dev libonig-dev libxml2-dev \
#     && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath xml \
#     && apt-get clean && rm -rf /var/lib/apt/lists/*

# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# WORKDIR /app

# # Copy file cấu hình trước
# COPY composer.json composer.lock ./

# # Chống lỗi Exit Code 2 do thiếu RAM trên Render
# ENV COMPOSER_ALLOW_SUPERUSER=1
# ENV COMPOSER_MEMORY_LIMIT=-1
# RUN composer install --no-dev --no-scripts --optimize-autoloader --ignore-platform-reqs

# COPY . .

# # Tạo cấu trúc thư mục storage nếu chưa có (tránh lỗi chmod)
# RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
#     && chown -R www-data:www-data /app \
#     && chmod -R 775 storage bootstrap/cache

# # Render dùng port 10000
# EXPOSE 10000

# # Lệnh chạy: Tạo cache cấu hình và khởi động server
# CMD php artisan config:cache && \
#     php artisan route:cache && \
#     php artisan serve --host=0.0.0.0 --port=10000

FROM php:8.2-cli

# 1. Cài đặt extension (Đã chuẩn)
RUN apt-get update && apt-get install -y \
    libpq-dev unzip git curl libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql zip mbstring bcmath xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 2. Tận dụng Cache (Đã chuẩn)
COPY composer.json composer.lock ./

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --no-scripts --optimize-autoloader --ignore-platform-reqs

# 3. Copy toàn bộ source code
COPY . .

# 4. Phân quyền (Đảm bảo thư mục tồn tại để không lỗi chmod)
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 10000

# 5. Lệnh chạy (Đổi cache thành clear để ổn định hơn trên môi trường mới)
CMD php artisan config:clear && \
    php artisan route:clear && \
    php artisan serve --host=0.0.0.0 --port=10000