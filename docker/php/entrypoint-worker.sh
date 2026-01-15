#!/bin/sh
set -e

cd /var/www/html

# Wait for MySQL
echo "Waiting for MySQL..."
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception \$e) { exit(1); }"; do
  sleep 3
done

# Wait for Redis
echo "Waiting for Redis..."
until php -r "try { \$redis = new Redis(); \$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT')); } catch (Exception \$e) { exit(1); }"; do
  sleep 3
done

# Start the command passed to container (e.g., queue worker or scheduler)
exec "$@"
