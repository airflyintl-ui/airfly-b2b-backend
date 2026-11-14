FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Clear Laravel configuration cache
RUN php artisan config:clear
# Create required Laravel directories
RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

# Check Laravel bootstrap
RUN php -d display_errors=1 -r "require 'vendor/autoload.php'; require 'bootstrap/app.php'; echo 'Laravel Bootstrap OK';"

# Render port
EXPOSE 10000

# Start Laravel server
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t public public/index.php"]
