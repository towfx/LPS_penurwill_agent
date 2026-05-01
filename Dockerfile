# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json pnpm-lock.yaml ./

# Install dependencies and build
RUN npm install -g pnpm && \
    pnpm install --frozen-lockfile && \
    pnpm run build

# Stage 2: Build PHP application
FROM php:8.2-fpm-alpine AS php-builder

WORKDIR /app

# Install system dependencies
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    ctype \
    fileinfo \
    json \
    mbstring \
    pdo \
    pdo_mysql \
    tokenizer \
    xml \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader

# Stage 3: Production image
FROM php:8.2-fpm-alpine

WORKDIR /app

# Install runtime dependencies only
RUN apk add --no-cache \
    curl \
    mysql-client \
    oniguruma \
    libzip

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    ctype \
    fileinfo \
    json \
    mbstring \
    pdo \
    pdo_mysql \
    tokenizer \
    xml \
    zip

# Copy PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/laravel.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/www.conf

# Install netcat for entrypoint script
RUN apk add --no-cache netcat

# Copy application from builder stages
COPY --from=php-builder --chown=www-data:www-data /app/vendor ./vendor
COPY --from=php-builder --chown=www-data:www-data /app/composer.lock ./composer.lock
COPY --chown=www-data:www-data . .
COPY --from=frontend-builder --chown=www-data:www-data /app/public/build ./public/build

# Copy entrypoint script
COPY --chown=www-data:www-data docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create necessary directories with proper permissions
RUN mkdir -p storage/logs bootstrap/cache && \
    chmod -R 755 storage bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap

# Expose PHP-FPM port
EXPOSE 9000

# Health check
HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD curl -f http://localhost:9000/ping || exit 1

# Run as www-data user
USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
