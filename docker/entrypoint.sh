#!/bin/sh
set -e

echo "=== Initializing Meash Production Container ==="

# 1. Ensure SQLite database file exists and is writable if used
if [ "$DB_CONNECTION" = "sqlite" ] || [ -f "/var/www/html/database/database.sqlite" ]; then
    touch /var/www/html/database/database.sqlite
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
    chmod 664 /var/www/html/database/database.sqlite
fi

# 2. Storage & bootstrap cache permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 3. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# 4. Seed database with initial accounts and services (idempotent)
echo "Ensuring initial database records are seeded..."
php artisan db:seed --force

# 5. Link storage and apply production optimizations
php artisan storage:link || true
echo "Pre-compiling production configurations, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set Telegram Webhook automatically if configured
if [ -n "$TELEGRAM_BOT_TOKEN" ] && [ -n "$APP_URL" ] && [ "$APP_URL" != "http://localhost" ]; then
    echo "Configuring Telegram Webhook for $APP_URL ..."
    php artisan telegram:webhook set || true
fi

# 7. Support dynamic port binding (Render, Railway, Cloud Run, Heroku)
PORT="${PORT:-80}"
if [ "$PORT" != "80" ]; then
    echo "Configuring Nginx to listen on dynamic port $PORT..."
    sed -i "s/listen 80 default_server;/listen $PORT default_server;/g" /etc/nginx/sites-available/default || true
    sed -i "s/listen \[::\]:80 default_server;/listen [::]:$PORT default_server;/g" /etc/nginx/sites-available/default || true
fi

echo "=== Starting Nginx & PHP-FPM Multi-Worker via Supervisor ==="
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
