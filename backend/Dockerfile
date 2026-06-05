FROM php:8.2-fpm

# 系统依赖（尽量精简，避免拉取过多包）
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        ca-certificates \
        curl \
    && rm -rf /var/lib/apt/lists/*

# 安装 Node.js（使用 NodeSource 源，避免官方源拉取失败）
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# PHP 扩展
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath

# Composer - install directly instead of copying from image
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

WORKDIR /var/www/html

# Nginx config
RUN rm -f /etc/nginx/sites-enabled/default
COPY nginx/backend.conf /etc/nginx/sites-available/backend.conf
RUN ln -sf /etc/nginx/sites-available/backend.conf /etc/nginx/sites-enabled/backend.conf

# Default env (override via docker-compose)
ENV APP_ENV=local \
    APP_DEBUG=true

EXPOSE 80

# Copy init script
COPY backend/init.sh /usr/local/bin/init-laravel.sh
RUN chmod +x /usr/local/bin/init-laravel.sh

# Start php-fpm + nginx + init (init runs in background, nginx stays in foreground)
CMD ["/bin/sh", "-c", "php-fpm -D && (/usr/local/bin/init-laravel.sh > /proc/1/fd/1 2>&1 &) && nginx -g 'daemon off;'"]
