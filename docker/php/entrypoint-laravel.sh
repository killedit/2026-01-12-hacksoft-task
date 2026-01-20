#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for MySQL..."
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception \$e) { exit(1); }"; do
  echo "MySQL not ready, retrying in 3 seconds..."
  sleep 3
done

echo "Waiting for Redis..."
until php -r "try { \$redis = new Redis(); \$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT')); } catch (Exception \$e) { exit(1); }"; do
  echo "Redis not ready, retrying in 3 seconds..."
  sleep 3
done

echo "Regenerating autoloader..."
composer dump-autoload --no-dev --optimize || true

echo "Running migrations & seeders..."
php artisan migrate --force || true
php artisan db:seed --force || true

if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

echo "Laravel setup complete. Starting php-fpm..."
exec php-fpm
