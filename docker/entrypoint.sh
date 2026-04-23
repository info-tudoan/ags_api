#!/bin/sh
set -e

# Map custom env vars to Laravel standard names
export DB_CONNECTION=mysql
export DB_HOST="${DATA_BASE_HOST:-127.0.0.1}"
export DB_PORT="${DATA_BASE_PORT:-3306}"
export DB_USERNAME="${DATA_BASE_USERNAME:-root}"
export DB_PASSWORD="${DATA_BASE_PASSWORD:-}"
export DB_DATABASE="${DATA_BASE_DB:-laravel}"

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    export APP_KEY=$(php artisan key:generate --show --no-ansi)
fi

# Write .env file from environment
cat > /var/www/html/.env <<EOF
APP_NAME="${APP_NAME:-AGS API}"
APP_ENV="${APP_ENV:-production}"
APP_KEY=${APP_KEY}
APP_DEBUG="${APP_DEBUG:-false}"
APP_URL="${APP_URL:-http://localhost}"

LOG_CHANNEL=stderr
LOG_LEVEL="${LOG_LEVEL:-error}"

DB_CONNECTION=mysql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

JWT_SECRET="${JWT_SECRET:-}"
JWT_TTL="${JWT_TTL:-60}"

MAIL_MAILER="${MAIL_MAILER:-log}"
EOF

cd /var/www/html

# Run migrations
php artisan migrate --force

# Cache config for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix storage permissions after any mounts
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec "$@"
