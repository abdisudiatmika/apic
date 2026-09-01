# HRIS APIC — Fase 1–6 (Lokal, Docker)

Implementasi Fase 1 (fondasi: autentikasi, RBAC, data pegawai, master data, absensi +
5.3.1 Import Excel), Fase 2 (Cuti & Bon Cuti: 5.5–5.8), Fase 3 (Koreksi Absensi 5.4,
Surat Tugas/Perjalanan Dinas 5.10, Notifikasi 11), Fase 4 (hardening keamanan), Fase 5
(Laporan & Analitik HR — fitur 12), dan Fase 6 (Kelola User) dari PRD "Sistem HR &
Kepegawaian" v2.1 — **seluruh modul inti PRD sudah selesai**, ditambah satu fitur di
luar PRD yang ternyata dibutuhkan nyata saat deploy produksi (lihat Fase 6 di bawah).
Lihat
`/Users/abdisudiatmika/.claude/plans/melodic-cuddling-goose.md` untuk rencana lengkap
tiap fase, atau dokumen PRD untuk gambaran seluruh sistem.

Dokumen ini untuk **menjalankan secara lokal**. Untuk deploy ke server/mini PC
sungguhan, lihat [`DEPLOY.md`](DEPLOY.md) — beda cukup banyak dari setup lokal
(tanpa data demo, kredensial produksi, Cloudflare Tunnel, dll).

## Menjalankan secara lokal

Prasyarat: Docker Desktop menyala.

```bash
cp .env.example .env
# edit .env: isi DB_PASSWORD & DB_ROOT_PASSWORD dengan password acak yang kuat
# (jangan pakai nilai contoh apa adanya)

docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d --build
docker compose exec app php artisan migrate --seed
```

`docker-compose.dev.yml` menambahkan Mailpit dan port host (8080 untuk nginx, 3307
untuk MySQL) di atas `docker-compose.yml` — dipisah jadi file sendiri (bukan
`docker-compose.prod.yml` yang dipakai untuk mini PC) supaya keduanya tidak
bergantung pada fitur merge Compose yang butuh versi terbaru (lihat komentar di
`docker-compose.prod.yml`). Semua perintah `docker compose ...` di bawah asumsikan
dua file ini — ganti `docker compose` dengan
`docker compose -f docker-compose.yml -f docker-compose.dev.yml` bila perlu.

Akses:
- Admin panel (HR/Administrator/Direksi): http://localhost:8080/admin
- Portal (Pegawai/Atasan): http://localhost:8080/portal
- Mailpit (tangkap email lokal): http://localhost:8025

## Akun demo (DEV ONLY — data & password ini hanya untuk lokal)

| Role | Email | Password |
|---|---|---|
| HR | hr@demo.test | DemoHR#2026 |
| Administrator | admin@demo.test | DemoAdmin#2026 |
| Direksi | direksi@demo.test | DemoDireksi#2026 |
| Atasan | atasan@demo.test | DemoAtasan#2026 |
| Pegawai | pegawai@demo.test | DemoPegawai#2026 |

Seluruh data pegawai di seeder (`database/seeders/DemoDataSeeder.php`) adalah data
sintetis (Faker) — **bukan** data pegawai APIC yang sesungguhnya. File export mesin
absensi asli yang dipakai sebagai referensi struktur kolom untuk fitur import tidak
pernah disalin ke dalam repo atau database ini.

## Yang sudah berfungsi di Fase 1

- Login + 2 panel Filament dengan hak akses per role (`App\Models\User::canAccessPanel`)
- Data Pegawai (CRUD penuh di admin; read-only milik sendiri/tim di portal)
- Master data: Cabang, Departemen, Jabatan, Shift
- Data Absensi (list + filter tanggal/departemen/cabang/status)
- **Import Absensi via Excel** (menu "Import Absensi") — upload file, background job
  mem-parsing sheet "Exception Stat.", preview hasil via "Riwayat Import" (baris
  berhasil/gagal + alasan gagal per baris)
- Dashboard HR dengan angka nyata dari database
- Audit log otomatis (`spatie/laravel-activitylog`) untuk semua model di atas

## Yang sudah berfungsi di Fase 2

- **Pengajuan Cuti** (menu "Cuti" di portal) — pegawai ajukan, sistem validasi saldo,
  tanggal tumpang tindih, dan tanggal terbatas (blackout) sebelum tersimpan; approval
  berjenjang Atasan → HR lewat tombol "Setujui"/"Tolak" langsung di tabel
- **Sisa Cuti** — dihitung live oleh `App\Services\LeaveBalanceService`, tampil sebagai
  widget di dashboard portal dan kolom di Saldo Cuti (admin)
- **Bon Cuti** dengan **potongan otomatis** — saat HR menambah/menaikkan hak cuti
  (Saldo Cuti di admin), bon cuti yang masih outstanding otomatis terpotong
  (`App\Observers\LeaveBalanceObserver`), tercatat di riwayat penyesuaian saldo
- **Kalender Cuti & Ketersediaan Tim** — tabel per tanggal berisi jumlah & nama pegawai
  yang cuti, dengan ambang batas peringatan; versi admin (semua pegawai) dan versi
  portal (tim atasan saja)
- Tanggal Terbatas Cuti (blackout dates) — master data di admin, dicek otomatis saat
  pegawai mengajukan cuti

**Keterbatasan yang diketahui:** jumlah hari cuti dihitung sebagai hari kerja
(Senin–Jumat) tanpa mengecualikan hari libur nasional/perusahaan, karena kalender hari
libur (bagian dari PRD 5.9) belum dibangun — cuti yang melewati tanggal merah akan
sedikit overcount untuk saat ini.

## Yang sudah berfungsi di Fase 3

- **Koreksi Absensi** (menu "Koreksi Absensi") — pegawai ajukan jam masuk/keluar
  seharusnya, approval Atasan → HR; saat disetujui, `AttendanceLog` diperbarui
  (`updateOrCreate`) dan riwayat sebelum/sesudah otomatis tercatat lewat
  `activity_log` yang sudah ada sejak Fase 1 (tidak perlu tabel riwayat terpisah)
- **Surat Tugas / Perjalanan Dinas** (menu "Surat Tugas") — pegawai ajukan untuk
  beberapa pegawai sekaligus (relasi many-to-many), approval Atasan → HR; saat HR
  menyetujui: nomor surat diterbitkan otomatis (`{urut}/ST-APIC/{bulan-romawi}/{tahun}`),
  PDF bisa diunduh (`barryvdh/laravel-dompdf`, lihat `resources/views/pdf/`), dan
  `AttendanceLog` setiap pegawai yang ditugaskan otomatis berstatus **dinas** untuk
  seluruh tanggal perjalanan (tidak tercatat "Tidak Hadir") — tanpa menimpa data
  kehadiran nyata bila ternyata mereka tetap check-in
- **Notifikasi persisten** (ikon lonceng, bukan cuma toast) — `App\Concerns\NotifiesApprovers`
  dipasang di keempat alur approval (Cuti, Bon Cuti, Koreksi Absensi, Surat Tugas):
  atasan dinotifikasi saat ada pengajuan baru, HR dinotifikasi saat atasan sudah
  menyetujui, pemohon dinotifikasi saat keputusan akhir keluar
- **Pengingat terjadwal** — `contract-reminders:send` (kontrak berakhir dalam 30 hari)
  dan `attendance-reminders:send` (pegawai berjadwal tanpa data absensi), didaftarkan
  di `routes/console.php`, jalan otomatis lewat container `scheduler`
- Dashboard HR menambah stat "Kontrak Segera Berakhir"

Hardening keamanan (Fase 4) sudah selesai — lihat bagian Keamanan di bawah.

**Keterbatasan yang diketahui (Fase 3):** format nomor surat, kop surat, dan pihak
penandatangan pada PDF Surat Tugas bersifat placeholder generik — PRD 5.10 sendiri
menandai ini perlu disesuaikan dengan SOP resmi perusahaan sebelum dipakai nyata.
Notifikasi email/WhatsApp belum ada, baru notifikasi dalam aplikasi (bell icon).

## Yang sudah berfungsi di Fase 5

- **Laporan & Analitik HR** (menu "Laporan & Analitik", panel admin — HR/Administrator/
  Direksi) — PRD 12: satu halaman dengan filter bersama (rentang tanggal, departemen,
  cabang) menampilkan empat ringkasan per pegawai: Kehadiran & Keterlambatan, Cuti,
  Bon Cuti, dan Perjalanan Dinas. Tiap ringkasan bisa diunduh sebagai Excel maupun PDF
  (`app/Services/ReportService.php` — satu sumber angka yang sama dipakai baik oleh
  tampilan layar maupun file yang diunduh, jadi keduanya selalu cocok).
- **Grafik Tren Kehadiran** — melunasi "Grafik tren kehadiran" yang disebut di PRD 5.1
  (Dashboard HR) tapi belum pernah dibangun di Fase 1; sekarang jadi widget chart di
  halaman Laporan, dengan pilihan periode 7/14/30 hari sendiri.
- Tidak ada laporan "Data Pegawai" terpisah — resource Data Pegawai (5.2) yang sudah
  ada di Fase 1 sudah bisa difilter/dicari HR tanpa modul laporan baru; baris fitur 12
  di PRD spesifik hanya menyebut kehadiran/keterlambatan/cuti/bon cuti/perjalanan dinas.

Dengan Fase 5 selesai, **seluruh modul inti PRD v2.1 (fitur 1–13) sudah berfungsi.**

## Yang sudah berfungsi di Fase 6

- **Kelola User** (menu "Kelola User", grup "Pengaturan", panel admin — khusus
  **Administrator**) — ditemukan sebagai gap nyata saat deploy produksi: 39 data
  pegawai yang dibuat di server tidak satu pun bisa login, karena satu-satunya cara
  membuat akun (`User`) selama ini adalah lewat `php artisan tinker`. Sekarang
  Administrator bisa buat/edit akun (nama, email, kata sandi, satu peran dari 5 yang
  ada) langsung lewat web. Bukan bagian dari PRD asli — PRD bagian 2 menyebut
  "Administrator: Mengatur user, hak akses" sebagai tanggung jawabnya, tapi tidak
  pernah dirinci jadi fitur 5.x tersendiri seperti modul lain.
- Menautkan akun ke Data Pegawai **tetap** lewat field "Akun Login" yang sudah ada
  di form Data Pegawai (Fase 1) — Kelola User tidak menduplikasi mekanisme itu.
- `UserPolicy` lebih ketat dari Policy lain di sistem ini: hanya role
  `administrator` (bukan `hasAnyRole([...])` seperti Data Pegawai yang HR juga
  boleh kelola) — mengelola akun login & peran dianggap operasi paling sensitif.
  Ada proteksi diri: administrator tidak bisa menghapus akunnya sendiri.

## Keamanan — status & item yang masih perlu dikerjakan

Sudah dikerjakan sejak awal (lihat plan untuk detail lengkap):
- Container non-root, image minimal (`php:8.4-fpm-alpine`)
- Kredensial hanya lewat `.env` (tidak pernah di-commit)
- RBAC di level Policy (`app/Policies/*`), bukan cuma sembunyikan menu —
  sudah diuji manual: akun HR mendapat 403 saat mencoba akses `/portal`, akun
  Pegawai/Atasan hanya melihat data yang menjadi haknya di query level
  (`getEloquentQuery()` di tiap Resource portal), bukan hanya disaring di UI
- Import Excel: validasi tipe file, batas ukuran, diproses lewat queue (Redis) —
  bukan sinkron di request, supaya upload besar/berulang tidak membebani server
- Audit log aktif dari awal untuk semua model utama

**Selesai di Fase 4 (hardening) — diuji end-to-end lewat browser, bukan cuma ditulis:**
- 2FA (TOTP) wajib untuk panel admin (HR/Administrator/Direksi) — `AppAuthentication`
  bawaan Filament, secret & recovery codes tersimpan terenkripsi di kolom
  `users.app_authentication_secret`/`app_authentication_recovery_codes`
  (cast `encrypted`/`encrypted:array`, bukan plaintext). Diuji: enrollment penuh
  (scan secret → hitung OTP → aktifkan), lalu re-login memang meminta kode.
- Security headers lengkap: X-Frame-Options, X-Content-Type-Options,
  Referrer-Policy, Permissions-Policy, dan **Content-Security-Policy** di
  `docker/nginx/default.conf`. `script-src` butuh `'unsafe-inline'` **dan**
  `'unsafe-eval'` — Alpine.js (dasar semua komponen Livewire/Filament) meng-eval
  ekspresi `x-data`/`x-bind` lewat `new Function()`, jadi `'unsafe-eval'` bukan
  opsional (ditemukan lewat pengujian browser nyata: tanpa ini seluruh panel diam
  tanpa reaksi apa pun, tanpa error yang terlihat di UI). Avatar bawaan Filament
  (`UiAvatarsProvider`) diganti `App\Support\LocalInitialsAvatarProvider` —
  provider asli mengirim nama pengguna ke `ui-avatars.com` (kebocoran data
  pegawai ke pihak ketiga) dan sekalian diblokir oleh CSP ini; versi lokal
  merender avatar inisial sebagai SVG data URI, tanpa request keluar.
- Rate limiting (`throttle:30,1`) di route non-Filament (unduh PDF Surat Tugas) —
  diuji: request ke-31 dalam window yang sama mengembalikan 429.
- Kebijakan kata sandi minimum (`Password::defaults()` di `AppServiceProvider`).
- Validasi tipe file pada 3 field Lampiran (Koreksi Absensi, Cuti, Surat Tugas) —
  diuji: `.php` ditolak validasi, `.pdf`/`.jpg` diterima.
- `scripts/security-check.sh` — `composer audit` + `composer outdated` +
  pengecekan `.env` tidak ter-commit, dijalankan manual sebelum tiap deploy
  (belum ada CI di project ini).
- `scripts/backup-database.sh` — dump terkompresi + retensi otomatis, **diuji
  dengan restore sungguhan** ke database sementara (bukan cuma cek file ada).

**Disiapkan di Fase 4, tinggal dijalankan saat sudah di mini PC (butuh route
Cloudflare Tunnel aktif, tidak bisa diuji penuh di lokal):**
- `docker-compose.prod.yml` — overlay produksi: tanpa Mailpit, MySQL tidak
  diekspos ke host sama sekali, nginx hanya diekspos ke `127.0.0.1` (bukan
  `0.0.0.0`) di port `HRIS_PORT` (default 8081), `restart: always`,
  `docker/php/php.prod.ini` (opcache tanpa validate_timestamps — konsekuensinya:
  `git pull` di produksi butuh `docker compose restart app queue scheduler`
  sesudahnya, PHP tidak otomatis membaca ulang file yang berubah; lihat langkah
  14 di `DEPLOY.md`). **Tidak ada
  service `cloudflared` di file ini** — mini PC sudah punya connector Cloudflare
  Tunnel aktif untuk aplikasi lain (nama contoh: "server-abdi"), jadi HRIS cukup
  ditambahkan sebagai satu route baru ke tunnel yang sudah ada (Zero Trust
  dashboard → tunnel yang sudah ada → Public Hostname → arahkan ke
  `localhost:8081`), bukan bikin tunnel/token baru — langkah lengkap & catatan
  soal cara koneksi tunnel yang sudah ada (systemd vs container Docker) ada
  sebagai komentar di `docker-compose.prod.yml`. Sudah divalidasi sintaksnya
  lewat `docker compose -f docker-compose.yml -f docker-compose.prod.yml
  config`, belum diaktifkan nyata karena route belum ditambahkan di mini PC.
- Sengaja **tidak memakai** fitur merge `!reset` Compose (butuh v2.24+) untuk
  memisahkan port dev vs prod — versi Compose di mini PC belum tentu sebaru itu,
  dan `!reset` yang gagal diam-diam (tanpa error) bisa membuat "MySQL tidak
  diekspos ke host" diam-diam jadi salah lagi. Makanya port dev
  (`docker-compose.dev.yml`) dan prod (`docker-compose.prod.yml`) dipisah jadi
  file sendiri-sendiri di atas `docker-compose.yml` yang tanpa port sama sekali —
  bekerja sama di versi Compose mana pun.
- `bootstrap/app.php` menambahkan `trustProxies(at: '*')` — wajib supaya Laravel
  tahu request yang masuk lewat tunnel Cloudflare→nginx sebenarnya HTTPS (cookie
  secure, generate URL `https://`), aman dipakai karena nginx sendiri tidak
  pernah diakses langsung dari luar baik di lokal maupun produksi (hanya lewat
  `127.0.0.1`).
- HSTS (`Strict-Transport-Security`) — sudah ada di
  `docker/nginx/default.prod.conf`, sengaja dipisah dari `default.conf` karena
  header ini di atas HTTP polos hanya diabaikan browser.
- Cron backup asli (`scripts/backup-database.sh` via crontab mini PC, bukan
  dijalankan manual seperti saat pengujian di sini).

## Struktur project

```
hris-apic/
├── docker-compose.yml         # 6 service inti: app, nginx, mysql, redis, queue,
│                               # scheduler — tanpa port host, tanpa mailpit
├── docker-compose.dev.yml     # overlay lokal: +port host (8080/3307/8025), +mailpit
├── docker-compose.prod.yml    # overlay produksi: +port nginx 127.0.0.1 only, restart:always
├── Dockerfile                  # image app (php-fpm), non-root
├── docker/
│   ├── nginx/default.conf      # dev: security headers + CSP
│   ├── nginx/default.prod.conf # prod: + HSTS
│   ├── php/php.ini             # dev
│   └── php/php.prod.ini        # prod: opcache.validate_timestamps=0, cookie secure
├── scripts/
│   ├── security-check.sh       # composer audit + outdated + cek .env tak ter-commit
│   └── backup-database.sh      # dump + kompres + retensi (RETENTION_DAYS)
├── .env                       # kredensial compose (root-level, git-ignored)
└── src/                       # aplikasi Laravel
    ├── app/
    │   ├── Filament/
    │   │   ├── Resources/          # panel admin
    │   │   ├── Portal/              # panel portal (pegawai/atasan): Resources, Pages, Widgets
    │   │   ├── Pages/               # halaman custom admin (Import Absensi, Kalender Cuti)
    │   │   └── Widgets/
    │   ├── Imports/                 # parser Excel (AttendanceExceptionStatSheetImport)
    │   ├── Exports/                  # Excel laporan (AttendanceSummaryExport, dll — Fase 5)
    │   ├── Jobs/                    # ProcessAttendanceImport (queued)
    │   ├── Services/                 # LeaveBalanceService, ReportService (Fase 5)
    │   ├── Observers/                # LeaveBalanceObserver — potongan otomatis Bon Cuti
    │   ├── Models/
    │   └── Policies/
    └── database/
        ├── migrations/
        └── seeders/
```

## Perintah yang berguna

```bash
# lihat log aplikasi
docker compose logs -f app

# masuk ke shell container
docker compose exec app bash

# jalankan artisan command apa pun
docker compose exec app php artisan <command>

# reset database + seed ulang (data sintetis)
docker compose exec app php artisan migrate:fresh --seed

# jalankan test
docker compose exec app php artisan test

# cek keamanan (composer audit, dependency outdated, .env tak ter-commit)
./scripts/security-check.sh

# backup database (kompres + retensi 14 hari default)
./scripts/backup-database.sh

# validasi sintaks docker-compose.prod.yml (tidak menjalankan apa pun)
docker compose -f docker-compose.yml -f docker-compose.prod.yml config
```
