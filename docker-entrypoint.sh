#!/bin/bash
set -e

echo "== TDMU UnionTrack: khoi dong container =="

php artisan config:clear
php artisan package:discover --ansi

echo "-- Chay migrate (chi ap dung migration con thieu, khong xoa du lieu) --"
php artisan migrate --force

echo "-- Seed du lieu mau neu database dang trong (chi chay 1 lan duy nhat) --"
php artisan app:seed-once

echo "-- Dong bo ten 16 to Cong doan thuc te (an toan chay lai nhieu lan) --"
php artisan app:sync-real-union-groups

echo "-- Seed du lieu cho tinh nang moi: Ban chuyen mon, tieu chi thi dua, ke hoach hoat dong --"
php artisan app:seed-new-features

php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== San sang, lang nghe tren cong ${PORT:-8080} =="
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
