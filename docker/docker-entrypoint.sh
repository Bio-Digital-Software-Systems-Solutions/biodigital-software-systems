#!/bin/sh
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
while ! nc -z mysql 3306; do
  sleep 1
done
echo "MySQL is ready!"

# Run Laravel setup commands
if [ "$APP_ENV" = "production" ]; then
    echo "Running production setup..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
else
    echo "Running development setup..."
    php artisan config:clear
    php artisan cache:clear
fi

# Run migrations if needed
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force
fi

# Create storage link if it doesn't exist
php artisan storage:link 2>/dev/null || true

# Execute the main command
exec "$@"
