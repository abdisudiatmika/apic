# Deploy ke Mini PC — HRIS APIC

Panduan langkah demi langkah menjalankan aplikasi ini di mini PC pengguna (sudah
terpasang Docker, Cloudflare, dan Tailscale), diakses publik lewat domain di akun
Cloudflare via Cloudflare Tunnel. Ikuti urutan di bawah — beberapa langkah saling
bergantung (migrasi butuh `vendor/` sudah ter-install, tunnel butuh container sudah
jalan, dst).

Beda penting dari `README.md` (yang untuk lokal): mini PC ini clone **baru**, jadi
tidak ada `vendor/`, `.env`, atau `APP_KEY` yang sudah ada dari sebelumnya — dan
**jangan pernah menjalankan seeder demo** (`DemoDataSeeder`) di sini, karena
membuat 5 akun dengan password yang sudah pernah ditampilkan di histori kerja
proyek ini (`DemoHR#2026`, dst.) — aman untuk lokal, bahaya kalau publik.

## 1. Prasyarat di mini PC

- Docker & Docker Compose v2 sudah terpasang dan menyala (`docker compose version`).
- `git` terpasang.
- Domain sudah ada sebagai zone aktif di akun Cloudflare (sudah dikonfirmasi ada).

## 2. Clone repository

```bash
git clone https://github.com/abdisudiatmika/apic.git
cd apic
```

## 3. Setup Cloudflare Tunnel (dashboard, sebelum lanjut)

1. Buka [Cloudflare Zero Trust dashboard](https://one.dash.cloudflare.com/) → **Networks → Tunnels → Create a tunnel** → pilih connector **Cloudflared**.
2. Beri nama tunnel (mis. `hris-apic`), lanjut ke halaman instalasi — **salin token**
   yang muncul di perintah instalasi (string panjang setelah `--token`). Ini
   satu-satunya kredensial yang dibutuhkan, tidak perlu file sertifikat apa pun.
3. Masih di halaman setup tunnel yang sama, buka tab **Public Hostname** → tambah:
   - **Subdomain/Domain**: subdomain yang diinginkan (mis. `hris.namadomainanda.com`)
   - **Service Type**: `HTTP`
   - **URL**: `nginx:80` (nama service Docker, bukan alamat IP — cloudflared dan
     nginx berbagi network `hris` yang sama, jadi nama ini otomatis resolve)
4. Simpan. Tunnel ini baru benar-benar tersambung setelah container `cloudflared`
   jalan (langkah 6) — belum perlu jalan sekarang.

## 4. Setup `.env` root (level Docker Compose)

```bash
cp .env.example .env
```

Edit `.env`, isi:
- `UID` / `GID` — jalankan `id -u` dan `id -g` di mini PC, isi nilai aslinya (bukan nilai contoh).
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD` — password acak yang kuat, **beda satu sama lain**.
- `CLOUDFLARE_TUNNEL_TOKEN` — token dari langkah 3.2 di atas.
- **Hapus atau kosongkan baris `COMPOSE_PROFILES=dev`** — kalau dibiarkan, Mailpit
  (mail catcher lokal, bukan untuk produksi) ikut jalan di server publik.

## 5. Setup `.env` aplikasi Laravel (`src/.env`)

```bash
cp src/.env.example src/.env
```

Edit `src/.env` — ini beda cukup banyak dari default lokal:

```dotenv
APP_NAME="HRIS APIC"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hris.namadomainanda.com   # domain dari langkah 3.3, dengan https://

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hris_apic          # sama seperti di .env root
DB_USERNAME=hris_user          # sama seperti di .env root
DB_PASSWORD=                   # sama seperti DB_PASSWORD di .env root

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true     # wajib: cookie sesi hanya dikirim lewat HTTPS
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PORT=6379

# Ganti dengan SMTP nyata — Mailpit tidak ikut di produksi (lihat docker-compose.prod.yml)
# MAIL_SCHEME=smtp untuk STARTTLS di port 587 (paling umum), atau smtps untuk
# TLS langsung di port 465 — cek dokumentasi provider SMTP Anda.
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@namadomainanda.com"
MAIL_FROM_NAME="HRIS APIC"
```

`APP_KEY` **jangan** diisi manual — dibuat otomatis di langkah 7.

## 6. Build & jalankan container

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Ini menjalankan `app`, `queue`, `scheduler`, `nginx`, `mysql`, `redis`, dan
`cloudflared` — tanpa Mailpit, tanpa port MySQL/nginx yang diekspos ke host (lihat
komentar di `docker-compose.prod.yml` untuk detail tiap perbedaan dari setup lokal).

## 7. Install dependency & siapkan aplikasi

Clone baru belum punya `vendor/` — install dulu sebelum artisan command apa pun bisa jalan:

```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate --force
```

## 8. Migrasi & seed (TANPA data demo)

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RoleSeeder --force
docker compose exec app php artisan db:seed --class=LeaveTypeSeeder --force
```

**Jangan jalankan** `php artisan db:seed` tanpa `--class` (itu menjalankan
`DatabaseSeeder`, yang juga memanggil `DemoDataSeeder` — akun-akun demo publik
seperti dijelaskan di bagian atas dokumen ini).

## 9. Buat akun HR/Administrator pertama

Belum ada akun manusia sungguhan sampai langkah ini — buat lewat tinker:

```bash
docker compose exec app php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Nama Anda',
    'email' => 'anda@namadomainanda.com',
    'password' => 'password-yang-sangat-kuat-dan-unik',
]);
$user->assignRole('hr'); // atau 'administrator' — lihat app/Models/User::canAccessPanel()
exit
```

Ganti role sesuai kebutuhan (`administrator`, `hr`, atau `direksi` untuk akses
panel admin — `atasan`/`pegawai` untuk panel portal, biasanya dibuat lewat resource
Data Pegawai setelah login pertama, bukan lewat tinker).

## 10. Cache production & optimasi

```bash
docker compose exec app php artisan optimize
```

## 11. Verifikasi

- Buka `https://hris.namadomainanda.com/admin/login` dari browser — harus muncul
  halaman login (bukan error, bukan "502 Bad Gateway" dari Cloudflare — kalau
  muncul itu berarti tunnel belum tersambung ke `nginx:80`, cek
  `docker compose logs cloudflared`).
- Login dengan akun dari langkah 9 → harus diarahkan ke setup 2FA wajib (lihat
  `README.md` bagian Keamanan) → selesaikan enrollment dengan aplikasi authenticator
  sungguhan (Google Authenticator/Authy), bukan cara manual seperti saat pengujian
  lokal.
- `docker compose ps` — pastikan semua container `running`, `cloudflared` khususnya
  (`hris_cloudflared`) tidak restart-looping.
- `curl -sI https://hris.namadomainanda.com/admin/login | grep -i strict-transport`
  — pastikan header HSTS muncul.
- Jalankan `./scripts/security-check.sh` — pastikan `composer audit` bersih.

## 12. Backup terjadwal

`scripts/backup-database.sh` sudah teruji (lihat README), tapi belum otomatis
terjadwal — tambahkan ke crontab mini PC:

```bash
crontab -e
```

```cron
0 2 * * * cd /path/ke/apic && ./scripts/backup-database.sh >> /var/log/hris-backup.log 2>&1
```

## 13. Update aplikasi di kemudian hari

```bash
git pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
```

## Checklist ringkas

- [ ] Tunnel Cloudflare dibuat, Public Hostname → `nginx:80`
- [ ] `.env` root: `UID`/`GID` asli, password kuat, `CLOUDFLARE_TUNNEL_TOKEN` terisi, `COMPOSE_PROFILES` **tidak** `dev`
- [ ] `src/.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` https, `SESSION_SECURE_COOKIE=true`, SMTP nyata
- [ ] `composer install --no-dev`, `key:generate`
- [ ] Migrasi + seed **RoleSeeder & LeaveTypeSeeder saja** (bukan `DemoDataSeeder`)
- [ ] Akun HR/Administrator pertama dibuat manual, 2FA aktif
- [ ] `security-check.sh` bersih
- [ ] Backup terjadwal lewat cron
