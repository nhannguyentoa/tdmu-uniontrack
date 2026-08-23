#!/bin/bash
set -e

echo "== TDMU UnionTrack: khoi dong container =="

php artisan config:clear
php artisan package:discover --ansi

echo "-- Chay migrate:fresh --seed (reset ve du lieu mau moi lan khoi dong, do goi mien phi khong luu tru lau dai) --"
php artisan migrate:fresh --seed --force

php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== San sang, lang nghe tren cong ${PORT:-8080} =="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
