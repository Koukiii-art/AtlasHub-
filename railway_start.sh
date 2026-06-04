#!/bin/bash

# Railway Laravel Startup Script
# Runs on every deploy

set -e

echo "=== Railway Startup ==="

# Ensure storage directories exist with correct permissions
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set storage permissions
chmod -R 775 storage bootstrap/cache

# Create storage symlink (if not exists)
php artisan storage:link --force 2>/dev/null || true

# Remove ALL cached files (nuke stale cache from previous deploys)
rm -rf bootstrap/cache/*.php bootstrap/cache/*.json 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true

# Clear all caches (double ensure)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

# Run migrations
php artisan migrate --force

# Rebuild caches with fresh Railway env values
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "=== Startup Complete ==="

# Start the server
exec php artisan serve --host=0.0.0.0 --port=$PORT
