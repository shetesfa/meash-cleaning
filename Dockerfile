FROM php:8.2-fpm

# 1. Install System Dependencies & Tools
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    ca-certificates \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Install Node.js 20.x & npm for Vite asset compilation
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Copy Server Configurations (Nginx, PHP-FPM, OPcache, Supervisor)
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 5. Set Working Directory
WORKDIR /var/www/html

# 6. Copy Dependency Manifests First (Leverages Docker Layer Caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package*.json ./
RUN npm ci

# 7. Copy Application Source Code
COPY . /var/www/html

# 8. Finalize Composer Autoloader and Compile Vite Frontend Assets
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && npm run build \
    && rm -rf node_modules \
    && rm -rf ~/.npm

# 9. Set Permissions for Storage and Cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x /var/www/html/docker/entrypoint.sh

# 10. Expose HTTP Port
EXPOSE 80

# 11. Run High-Performance Production Entrypoint
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
