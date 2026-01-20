#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for MySQL..."
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception \$e) { exit(1); }"; do
  sleep 3
done

echo "Clearing bootstrap caches..."
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php
rm -f bootstrap/cache/config.php

if [ -f "vendor/league/flysystem/src/Filesystem.php" ]; then
    echo "Patching Flysystem..."
    if ! grep -q "isDeferred" vendor/league/flysystem/src/Filesystem.php; then
        sed -i '$d' vendor/league/flysystem/src/Filesystem.php
        cat <<EOF >> vendor/league/flysystem/src/Filesystem.php
    public function isDeferred(): bool { return false; }
    public function register(): void { }
}
EOF
    fi
fi

echo "Publishing assets..."
php artisan filament:assets

echo "Running migrations..."
# --force is required to run migrations in "production" environments
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

php artisan config:cache
php artisan route:cache

echo "Laravel setup complete. Starting php-fpm..."
exec php-fpm