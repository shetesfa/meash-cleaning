#!/bin/sh
set -e

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database if users are empty
php artisan db:seed --force || true

# Cache Laravel configurations
echo "Caching config & routes..."
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Apache in foreground
echo "Starting Apache web server..."
exec apache2-foreground
