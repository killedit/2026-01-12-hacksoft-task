#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for MySQL..."
until php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); } catch (Exception \$e) { exit(1); }"; do
  sleep 3
done

echo "Waiting for Redis..."
until php -r "try { \$redis = new Redis(); \$redis->connect(getenv('REDIS_HOST'), getenv('REDIS_PORT')); } catch (Exception \$e) { exit(1); }"; do
  sleep 3
done

if [ -f "vendor/league/flysystem/src/Filesystem.php" ]; then
    echo "Patching Flysystem in Worker..."
    # Only patch if the method isn't already there to avoid double-patching
    if ! grep -q "isDeferred" vendor/league/flysystem/src/Filesystem.php; then
        sed -i '$d' vendor/league/flysystem/src/Filesystem.php
        cat <<EOF >> vendor/league/flysystem/src/Filesystem.php
    public function isDeferred(): bool { return false; }
    public function register(): void { }
}
EOF
    fi
fi

rm -f bootstrap/cache/services.php bootstrap/cache/packages.php

echo "Starting Worker: $@"
exec "$@"