# Deploy ke Mini PC — HRIS APIC

Panduan langkah demi langkah menjalankan aplikasi ini di mini PC pengguna (sudah
terpasang Docker, Cloudflare, dan Tailscale), diakses publik lewat domain di akun
Cloudflare via Cloudflare Tunnel — **memakai tunnel yang sudah aktif di mini PC ini**
(mis. "server-abdi", yang sudah punya route lain), bukan bikin tunnel baru. Ikuti
urutan di bawah — beberapa langkah saling bergantung (migrasi butuh `vendor/` sudah
ter-install, route Cloudflare butuh container sudah jalan di port yang benar, dst).

Beda penting dari `README.md` (yang untuk lokal): mini PC ini clone **baru**, jadi
tidak ada `vendor/`, `.env`, atau `APP_KEY` yang sudah ada dari sebelumnya — dan
**jangan pernah menjalankan seeder demo** (`DemoDataSeeder`) di sini, karena
membuat 5 akun dengan password yang sudah pernah ditampilkan di histori kerja
proyek ini (`DemoHR#2026`, dst.) — aman untuk lokal, bahaya kalau publik.

## 1. Prasyarat di mini PC

- Docker & Docker Compose sudah terpasang dan menyala (`docker compose version`).
- `git` terpasang.
- Domain sudah ada sebagai zone aktif di akun Cloudflare (sudah dikonfirmasi ada).
- Sudah ada Cloudflare Tunnel connector aktif di mini PC ini untuk aplikasi lain
  (sudah dikonfirmasi ada — terlihat dari route yang sudah terdaftar di dashboard
  Zero Trust).

## 2. Clone repository

```bash
git clone https://github.com/abdisudiatmika/apic.git
cd apic
```

## 3. Cek cara connector Cloudflare Tunnel yang sudah ada berjalan

HRIS akan numpang di tunnel yang sudah ada, bukan bikin tunnel baru — tapi caranya
menambahkan route sedikit beda tergantung cara connector itu sendiri terpasang.
Jalankan di mini PC:

```bash
# Opsi A: connector terpasang sebagai service native (systemd)
systemctl status cloudflared

# Opsi B: connector jalan sebagai container Docker
docker ps | grep cloudflared

# Jika Opsi B, cek network mode container-nya:
docker inspect <nama_container_cloudflared> --format '{{.HostConfig.NetworkMode}}'
```

Catat hasilnya — dipakai di langkah 8:
- **systemd**, atau Docker dengan network mode **`host`** → connector berbagi
  network stack dengan mini PC itu sendiri, route diarahkan ke `localhost:PORT`.
- Docker dengan network mode **lain** (mis. `bridge` sendiri) → route perlu
  diarahkan ke `host.docker.internal:PORT` (hanya berfungsi jika container itu
  dijalankan dengan `--add-host=host.docker.internal:host-gateway`, atau connector
  di-restart dengan opsi itu ditambahkan).

## 4. Setup `.env` root (level Docker Compose)

```bash
cp .env.example .env
```

Edit `.env`, isi:
- `UID` / `GID` — jalankan `id -u` dan `id -g` di mini PC, isi nilai aslinya (bukan nilai contoh).
- `DB_PASSWORD` dan `DB_ROOT_PASSWORD` — password acak yang kuat, **beda satu sama lain**.
- `HRIS_PORT` — port di `127.0.0.1` tempat nginx akan diakses (default 8081 kalau
  dikosongkan). Ganti kalau port itu sudah dipakai aplikasi lain di mini PC yang
  sama (`ss -tlnp | grep 8081` untuk cek).

Baris `COMPOSE_PROFILES` yang mungkin ada di versi lama file ini **sudah tidak
dipakai lagi** — abaikan/hapus kalau nemu.

## 5. Setup `.env` aplikasi Laravel (`src/.env`)

```bash
cp src/.env.example src/.env
```

Edit `src/.env` — ini beda cukup banyak dari default lokal:

```dotenv
APP_NAME="HRIS APIC"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://hris.namadomainanda.com   # domain yang akan dipakai di langkah 8

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

Ini menjalankan `app`, `queue`, `scheduler`, `nginx`, `mysql`, `redis` — tanpa
Mailpit, tanpa MySQL yang diekspos ke host, nginx hanya diekspos ke
`127.0.0.1:${HRIS_PORT}` (bukan `0.0.0.0` — tidak bisa diakses dari LAN/internet
langsung, hanya dari proses lain di mesin yang sama seperti connector Cloudflare
Tunnel). Tidak ada service `cloudflared` baru di sini — lihat langkah 8.

## 7. Install dependency & siapkan aplikasi

Clone baru belum punya `vendor/` — install dulu sebelum artisan command apa pun bisa jalan:

```bash
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan key:generate --force
```

## 8. Tambahkan route HRIS ke tunnel Cloudflare yang sudah ada

Di dashboard Cloudflare Zero Trust → Networks → Tunnels → pilih tunnel yang sudah
aktif (mis. "server-abdi") → tab **Public Hostname** → **Add a hostname**:

- **Subdomain/Domain**: subdomain yang diinginkan (mis. `hris.namadomainanda.com`)
- **Service Type**: `HTTP`
- **URL**:
  - `localhost:8081` (atau `HRIS_PORT` yang dipakai) — kalau hasil cek di langkah 3
    adalah **systemd** atau Docker **network mode host**
  - `host.docker.internal:8081` — kalau connector jalan di network Docker sendiri

Simpan. Tidak perlu token baru, tidak perlu tunnel baru — route ini langsung aktif
begitu disimpan, memakai connector yang sudah berjalan.

## 9. Migrasi & seed (TANPA data demo)

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RoleSeeder --force
docker compose exec app php artisan db:seed --class=LeaveTypeSeeder --force
```

**Jangan jalankan** `php artisan db:seed` tanpa `--class` (itu menjalankan
`DatabaseSeeder`, yang juga memanggil `DemoDataSeeder` — akun-akun demo publik
seperti dijelaskan di bagian atas dokumen ini).

## 10. Buat akun HR/Administrator pertama

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

## 11. Cache production & optimasi

```bash
docker compose exec app php artisan optimize
```

## 12. Verifikasi

- `curl -I http://localhost:8081/admin/login` langsung di mini PC — harus `200`,
  bukan connection refused (kalau gagal, cek `docker compose logs nginx`).
- Buka `https://hris.namadomainanda.com/admin/login` dari browser — harus muncul
  halaman login (bukan error, bukan "502/1033" dari Cloudflare — kalau muncul
  berarti route di langkah 8 belum tersambung dengan benar ke port HRIS).
- Login dengan akun dari langkah 10 → harus diarahkan ke setup 2FA wajib (lihat
  `README.md` bagian Keamanan) → selesaikan enrollment dengan aplikasi authenticator
  sungguhan (Google Authenticator/Authy), bukan cara manual seperti saat pengujian
  lokal.
- `docker compose ps` — pastikan semua container `running`.
- `curl -sI https://hris.namadomainanda.com/admin/login | grep -i strict-transport`
  — pastikan header HSTS muncul.
- Jalankan `./scripts/security-check.sh` — pastikan `composer audit` bersih.

## 13. Backup terjadwal

`scripts/backup-database.sh` sudah teruji (lihat README), tapi belum otomatis
terjadwal — tambahkan ke crontab mini PC:

```bash
crontab -e
```

```cron
0 2 * * * cd /path/ke/apic && ./scripts/backup-database.sh >> /var/log/hris-backup.log 2>&1
```

## 14. Update aplikasi di kemudian hari

```bash
git pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize
docker compose restart app queue scheduler
```

**Baris terakhir (`restart`) wajib, jangan dilewati.** `php.prod.ini` mengaktifkan
`opcache.validate_timestamps=0` (dibahas di README bagian Keamanan) — PHP tidak
otomatis mendeteksi file `.php` yang berubah setelah `git pull`, tetap menjalankan
versi lama yang sudah di-cache sampai proses PHP-FPM-nya benar-benar direstart.
`up -d --build` **tidak selalu me-restart** container yang sudah jalan (Compose
cuma recreate kalau ada konfigurasi yang berubah — kode di `src/` adalah bind
mount, bukan bagian dari image, jadi berubah tanpa Compose "menyadarinya"), dan
`php artisan optimize` sama sekali beda layer (cache Laravel, bukan cache PHP) —
keduanya **tidak cukup** sendirian untuk membuat kode baru benar-benar aktif.
Tandanya kalau langkah ini terlewat: kode sudah ter-`git pull` (`git log` menunjukkan
commit terbaru) tapi perilaku aplikasi masih seperti versi sebelumnya.

## Checklist ringkas

- [ ] Cara connector Cloudflare Tunnel yang sudah ada dicek (systemd / Docker + network mode)
- [ ] `.env` root: `UID`/`GID` asli, password kuat, `HRIS_PORT` dipilih (cek tidak bentrok)
- [ ] `src/.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` https, `SESSION_SECURE_COOKIE=true`, SMTP nyata
- [ ] `composer install --no-dev`, `key:generate`
- [ ] Route baru ditambahkan ke tunnel Cloudflare yang sudah ada (bukan tunnel baru)
- [ ] Migrasi + seed **RoleSeeder & LeaveTypeSeeder saja** (bukan `DemoDataSeeder`)
- [ ] Akun HR/Administrator pertama dibuat manual, 2FA aktif
- [ ] `security-check.sh` bersih
- [ ] Backup terjadwal lewat cron
