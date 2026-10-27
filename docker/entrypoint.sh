#!/bin/sh
set -e

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database only in local environment
if [ "$APP_ENV" = "local" ]; then
    echo "Seeding local database..."
    php artisan db:seed --force
fi

# Link storage
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache in foreground
echo "Starting Apache web server..."
exec apache2-foreground
