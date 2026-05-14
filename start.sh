#!/bin/bash

# Function to handle script termination
cleanup() {
    echo "Shutting down servers..."
    kill $(jobs -p)
    exit
}

trap cleanup SIGINT SIGTERM

echo "🚀 Starting Laravel development environment..."

# Start PHP Artisan Serve
php artisan serve --port=8000 &
PHP_PID=$!

# Start PNPM Dev
pnpm run dev &
PNPM_PID=$!

echo "✅ Servers are starting..."
echo "🔗 Laravel: http://localhost:8000"

# Wait for background processes
wait
