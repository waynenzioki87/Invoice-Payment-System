#!/bin/sh
set -eu

chown -R www-data:www-data storage bootstrap/cache
php artisan config:cache

exec apache2-foreground
