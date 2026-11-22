#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

if [ "$1" = "frankenphp" ] || [ "$1" = "supervisord" ]; then
    if [ "$RUN_MIGRATIONS" = "true" ]; then
        echo "Menunggu koneksi database PostgreSQL..."
        MAX_TRIES=30
        COUNT=0
        until php -r '
            try {
                $host = getenv("DB_HOST") ?: "127.0.0.1";
                $port = getenv("DB_PORT") ?: "5432";
                $db   = getenv("DB_DATABASE") ?: "pkp";
                $user = getenv("DB_USERNAME") ?: "pkp";
                $pass = getenv("DB_PASSWORD") ?: "pkp";
                new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
                exit(0);
            } catch (Throwable $e) {
                exit(1);
            }
        ' > /dev/null 2>&1; do
            COUNT=$((COUNT + 1))
            if [ $COUNT -ge $MAX_TRIES ]; then
                echo "Database belum siap setelah $MAX_TRIES detik. Melewati migrasi..."
                break
            fi
            sleep 1
        done

        if [ $COUNT -lt $MAX_TRIES ]; then
            echo "Database terhubung. Menjalankan migrasi..."
            php artisan migrate --force
            if [ "$RUN_SEEDER" = "true" ]; then
                echo "Menjalankan seeder awal..."
                php artisan db:seed --force
            fi
        fi
    fi

    if [ "$APP_ENV" = "production" ] || [ "$APP_ENV" = "staging" ]; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        php artisan event:cache
    else
        php artisan optimize:clear
    fi
fi

exec "$@"
