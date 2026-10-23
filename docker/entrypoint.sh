#!/bin/sh
set -eu

app_dir=/var/www/html
database_file="${DB_DATABASE:-$app_dir/database/database.sqlite}"
PORT="${PORT:-10000}"
export PORT

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY precisa ser configurada." >&2
    exit 1
fi

mkdir -p \
    "$(dirname "$database_file")" \
    "$app_dir/storage/logs" \
    "$app_dir/storage/framework/cache/data" \
    "$app_dir/storage/framework/sessions" \
    "$app_dir/storage/framework/views" \
    "$app_dir/bootstrap/cache" \
    /tmp/nginx-http.d \
    /tmp/nginx-client-body \
    /tmp/nginx-proxy \
    /tmp/nginx-fastcgi \
    /tmp/nginx-uwsgi \
    /tmp/nginx-scgi

if [ ! -f "$database_file" ]; then
    touch "$database_file"
fi

chown -R www-data:www-data \
    "$app_dir/database" \
    "$app_dir/storage" \
    "$app_dir/bootstrap/cache" \
    /tmp/nginx-http.d \
    /tmp/nginx-client-body \
    /tmp/nginx-proxy \
    /tmp/nginx-fastcgi \
    /tmp/nginx-uwsgi \
    /tmp/nginx-scgi

cd "$app_dir"

su-exec www-data php artisan config:clear
su-exec www-data php artisan migrate --force
su-exec www-data php artisan db:seed --class='Database\Seeders\DemoUserSeeder' --force
su-exec www-data php artisan config:cache
su-exec www-data php artisan route:cache
su-exec www-data php artisan view:cache

envsubst '${PORT}' \
    < /etc/nginx/http.d/default.conf.template \
    > /tmp/nginx-http.d/default.conf

php-fpm -F &
php_fpm_pid=$!

nginx -e /dev/stderr -g 'daemon off;' &
nginx_pid=$!

shutdown_requested=0

shutdown() {
    shutdown_requested=1
    kill -QUIT "$nginx_pid" "$php_fpm_pid" 2>/dev/null || true
}

trap shutdown INT TERM

while kill -0 "$php_fpm_pid" 2>/dev/null && kill -0 "$nginx_pid" 2>/dev/null; do
    sleep 1
done

if [ "$shutdown_requested" -eq 0 ]; then
    echo "php-fpm ou nginx encerrou inesperadamente." >&2
    kill -TERM "$nginx_pid" "$php_fpm_pid" 2>/dev/null || true
fi

wait "$nginx_pid" 2>/dev/null || true
wait "$php_fpm_pid" 2>/dev/null || true

if [ "$shutdown_requested" -eq 0 ]; then
    exit 1
fi
