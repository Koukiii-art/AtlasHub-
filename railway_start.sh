#!/bin/bash

set -e

echo "=== Railway Startup ==="

# Remove any baked-in .env to prevent local dev values (DB_HOST=127.0.0.1, etc.)
# from overriding Railway-provided environment variables (MYSQLHOST, etc.)
rm -f .env

# Debug: Print all environment variables starting with DB_ or MYSQL
echo "Environment variables:"
env | grep -E "(^DB_|^MYSQL)" | head -20 || true

# Use Railway MySQL automatically when the database variables are present.
if [ -z "${DB_CONNECTION:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_CONNECTION=mysql
fi

if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    export DB_HOST="${DB_HOST:-${MYSQL_HOST:-${MYSQLHOST:-}}}"
    export DB_PORT="${DB_PORT:-${MYSQL_PORT:-${MYSQLPORT:-3306}}}"
    export DB_DATABASE="${DB_DATABASE:-${MYSQL_DATABASE:-${MYSQLDATABASE:-railway}}}"
    export DB_USERNAME="${DB_USERNAME:-${MYSQL_USER:-${MYSQLUSER:-root}}}"
    export DB_PASSWORD="${DB_PASSWORD:-${MYSQL_PASSWORD:-${MYSQLPASSWORD:-}}}"

    if [ -z "${DB_HOST:-}" ]; then
        echo "ERROR: DB_CONNECTION=mysql but no DB_HOST/MYSQLHOST is set on the Railway app service."
        echo "Missing environment variables. Check Railway Variables tab."
        echo "Required: MYSQLHOST, MYSQLPORT, MYSQLDATABASE, MYSQLUSER, MYSQLPASSWORD"
        sleep 5
        exit 1
    fi

    echo "Database config: host=${DB_HOST}, port=${DB_PORT}, database=${DB_DATABASE}, user=${DB_USERNAME}"
    
    # Wait for MySQL to be ready using nc (netcat) - simple TCP connection test
    echo "Waiting for MySQL to be ready at ${DB_HOST}:${DB_PORT}..."
    max_attempts=30
    attempt=1
    while ! nc -z "${DB_HOST}" "${DB_PORT}" 2>/dev/null; do
        if [ $attempt -ge $max_attempts ]; then
            echo "ERROR: Could not connect to MySQL after $max_attempts attempts"
            exit 1
        fi
        echo "  Attempt $attempt/$max_attempts - MySQL not ready yet, retrying in 2s..."
        sleep 2
        attempt=$((attempt + 1))
    done
    echo "✓ MySQL is ready!"
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
if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
    echo "✓ Migrations completed"
fi

# Cache config for production
php artisan config:cache
php artisan route:cache

# Start the application
echo "✓ Starting Laravel application..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}

# Run migrations
php artisan migrate --force

# Rebuild config cache after migrations. Do not route:cache; closure routes exist.
php artisan config:cache

echo "=== Startup Complete ==="

exec php artisan serve --host=0.0.0.0 --port=$PORT
