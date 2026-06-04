#!/bin/bash

set -e

echo "=== Railway Startup ==="

# Ensure storage directories exist
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

chmod -R 775 storage bootstrap/cache

php artisan storage:link --force 2>/dev/null || true

# Nuke stale cache files from disk
rm -rf bootstrap/cache/*.php bootstrap/cache/*.json 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true

# Clear all caches (safe order)
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild config cache only (NOT route:cache — closure routes exist)
php artisan config:cache

# Run migrations
php artisan migrate --force

echo "=== Startup Complete ==="

exec php artisan serve --host=0.0.0.0 --port=$PORT
