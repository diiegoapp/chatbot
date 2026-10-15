#!/bin/sh
set -e

# Create SQLite database if missing
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations
php /var/www/html/artisan migrate --force

# Generate key if missing
php /var/www/html/artisan key:generate --no-interaction 2>/dev/null || true

# Start PHP-FPM in background
php-fpm -D

# Start Nginx in foreground
nginx -g 'daemon off;'
