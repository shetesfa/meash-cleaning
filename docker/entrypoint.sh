#!/bin/sh
set -e

# Ensure SQLite database file exists and is writable by Apache
if [ "$DB_CONNECTION" = "sqlite" ] || [ -f "/var/www/html/database/database.sqlite" ]; then
    touch /var/www/html/database/database.sqlite
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
    chmod 664 /var/www/html/database/database.sqlite
fi

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Seed database only in local environment
if [ "$APP_ENV" = "local" ]; then
    echo "Seeding local database..."
    php artisan db:seed --force
fi

# Link storage and optimize cache
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set Telegram Webhook automatically if APP_URL is present
if [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$APP_URL" ] && [ "$APP_URL" != "http://localhost" ]; then
    echo "Configuring Telegram Webhook for $APP_URL ..."
    php artisan telegram:webhook set || true
fi

# Start Apache in foreground
echo "Starting Apache web server..."
exec apache2-foreground
