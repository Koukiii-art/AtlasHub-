#!/bin/bash

set -e

echo "=== Railway Startup ==="

# Remove any baked-in .env to prevent local dev values overriding Railway variables
rm -f .env

# Debug: Print environment variables  
echo "=== Environment Variables (DB/MySQL/Railway) ==="
env | grep -E "(^DB_|^MYSQL|^PORT|^RAILWAY)" | sort || echo "No DB/MYSQL/RAILWAY variables found"
echo "=== End Variables ==="

# Check if MySQL variables exist
if env | grep -q "^MYSQLHOST="; then
    echo "✓ MYSQLHOST found in environment"
else
    echo "⚠ WARNING: MYSQLHOST not found - ensure services are linked in Railway!"
fi

# Clean unresolved Railway template variables
if [[ "${MYSQLHOST:-}" == \$\{\{* ]]; then unset MYSQLHOST; fi
if [[ "${MYSQL_HOST:-}" == \$\{\{* ]]; then unset MYSQL_HOST; fi
if [[ "${DB_HOST:-}" == \$\{\{* ]]; then unset DB_HOST; fi

# Auto-detect MySQL when variables are present
if [ -z "${DB_CONNECTION:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_CONNECTION=mysql
fi

# Initialize DB variables
export DB_HOST="${DB_HOST:-${MYSQL_HOST:-${MYSQLHOST:-}}}"
export DB_PORT="${DB_PORT:-${MYSQL_PORT:-${MYSQLPORT:-3306}}}"
export DB_DATABASE="${DB_DATABASE:-${MYSQL_DATABASE:-${MYSQLDATABASE:-railway}}}"
export DB_USERNAME="${DB_USERNAME:-${MYSQL_USER:-${MYSQLUSER:-root}}}"
export DB_PASSWORD="${DB_PASSWORD:-${MYSQL_PASSWORD:-${MYSQLPASSWORD:-}}}"

echo "Resolved database config:"
echo "  Host: ${DB_HOST}"
echo "  Port: ${DB_PORT}"
echo "  Database: ${DB_DATABASE}"
echo "  User: ${DB_USERNAME}"

MYSQL_READY=0

if [ "${DB_CONNECTION:-}" = "mysql" ] && [ -n "${DB_HOST}" ]; then
    echo "Waiting for MySQL to be ready at ${DB_HOST}:${DB_PORT}..."
    max_attempts=30
    attempt=1
    while ! php -r "set_error_handler(function(){return true;}); \$c = fsockopen('${DB_HOST}', ${DB_PORT}, \$err, \$errstr, 2); if(\$c){fclose(\$c);exit(0);}exit(1);" 2>/dev/null; do
        if [ $attempt -ge $max_attempts ]; then
            echo "ERROR: Could not connect to MySQL after $max_attempts attempts"
            echo "Verify: 1) MySQL service is running, 2) Services are linked in Railway"
            exit 1
        fi
        echo "  Attempt $attempt/$max_attempts..."
        sleep 3
        attempt=$((attempt + 1))
    done
    echo "✓ MySQL is ready!"
    MYSQL_READY=1
else
    echo "⚠ Skipping MySQL check (DB_CONNECTION not mysql or DB_HOST empty)"
fi

# Set APP_KEY if missing
if [ -z "${APP_KEY:-}" ]; then
    echo "Generating APP_KEY..."
    export APP_KEY=$(php artisan key:generate --show --force)
fi

# Ensure storage directories
mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Run Laravel setup only if MySQL is ready
if [ $MYSQL_READY -eq 1 ]; then
    echo "Running Laravel setup (MySQL ready)..."
    
    php artisan storage:link --force 2>/dev/null || true
    rm -rf bootstrap/cache/*.php bootstrap/cache/*.json storage/framework/cache/data/* 2>/dev/null || true
    
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
    
    php artisan config:cache
    php artisan route:cache
    
    echo "✓ Laravel setup complete"
else
    echo "⚠ Skipping Laravel artisan commands (MySQL not ready)"
fi

# Start server
PORT=${PORT:-8000}
echo "✓ Starting Laravel on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port=${PORT}
