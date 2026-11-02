#!/bin/sh
set -e

# SQLite lives on a volume so quotes survive container restarts and rebuilds.
DB_FILE="${DB_DATABASE:-database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
[ -f "$DB_FILE" ] || touch "$DB_FILE"

# Publish this build's static assets (Filament CSS/JS etc.) to the volume Nginx serves.
if [ -d /var/www/public-shared ]; then
    cp -R public/. /var/www/public-shared/
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
