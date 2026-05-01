#!/bin/sh
set -e

echo "🚀 Starting Penurwill Agent application..."

# Wait for database to be ready
echo "⏳ Waiting for MySQL to be ready..."
until nc -z -v -w30 mysql 3306; do
    echo "MySQL is unavailable - sleeping"
    sleep 1
done
echo "✅ MySQL is ready!"

# Run migrations
echo "🔄 Running database migrations..."
php artisan migrate --force

# Cache configuration
echo "💾 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear any old caches
echo "🧹 Clearing old caches..."
php artisan cache:clear
php artisan view:clear

echo "✅ Application setup complete!"
echo "🎉 Starting PHP-FPM..."

exec php-fpm
