#!/usr/bin/env bash
# Standard production update sequence (DEPLOY.md bagian 14). Menarik kode
# terbaru, rebuild image jika perlu, migrate, lalu WAJIB restart app/queue/
# scheduler/nginx — bukan langkah opsional, lihat penjelasan di DEPLOY.md
# untuk kenapa `up -d --build` dan `artisan optimize` saja tidak cukup
# (opcache.validate_timestamps=0 di php.prod.ini, dan nginx meng-cache IP
# internal container "app" sekali saat startup lewat fastcgi_pass).
#
# Usage (di mini PC, dari root project): ./scripts/update-production.sh
set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml)

echo "==> git pull"
git pull

echo
echo "==> Rebuild & (re)start container jika ada perubahan image/config"
"${COMPOSE[@]}" up -d --build

echo
echo "==> composer install (produksi, tanpa dev dependencies)"
"${COMPOSE[@]}" exec -T app composer install --no-dev --optimize-autoloader

echo
echo "==> Menjalankan migration yang belum jalan"
"${COMPOSE[@]}" exec -T app php artisan migrate --force

echo
echo "==> Cache konfigurasi/route/view Laravel"
"${COMPOSE[@]}" exec -T app php artisan optimize

echo
echo "==> Restart app, queue, scheduler, nginx (wajib — lihat DEPLOY.md #14)"
"${COMPOSE[@]}" restart app queue scheduler nginx

echo
echo "Selesai. Cek https://apic.sadadewa.com/admin untuk verifikasi."
