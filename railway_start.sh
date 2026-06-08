#!/bin/bash

set -e

echo "=== Railway Startup ==="

# Remove any baked-in .env to prevent local dev values (DB_HOST=127.0.0.1, etc.)
# from overriding Railway-provided environment variables (MYSQLHOST, etc.)
rm -f .env

# Use Railway MySQL automatically when the database variables are present.
if [ -z "${DB_CONNECTION:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_CONNECTION=mysql
fi

# Ensure APP_KEY is set - generate one if Railway doesn't have it configured.
if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY not found in Railway environment - generating temporary key"
    echo "Set APP_KEY as a Railway variable to persist across deploys"
    export APP_KEY=$(php artisan key:generate --show --force)
fi

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

# Clear config, route, and view caches. Do not run cache:clear here; CACHE_STORE=database
# requires a DB connection that may not be ready at this point)
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Rebuild config cache after migrations. Do not route:cache; closure routes exist.
php artisan config:cache

echo "=== Startup Complete ==="

exec php artisan serve --host=0.0.0.0 --port=$PORT
