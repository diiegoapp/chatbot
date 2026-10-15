FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    sqlite \
    git \
    curl \
    && docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev \
    && npm ci \
    && npm run build \
    && rm -rf node_modules

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["/start.sh"]
