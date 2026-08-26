# HRIS APIC — Fase 1 (Lokal, Docker)

Implementasi Fase 1 dari PRD "Sistem HR & Kepegawaian" v2.1: fondasi (autentikasi,
RBAC, data pegawai, master data) dan modul absensi (termasuk sub-fitur 5.3.1 Import
Absensi via Excel). Lihat `/Users/abdisudiatmika/.claude/plans/melodic-cuddling-goose.md`
untuk rencana lengkap fase ini, atau dokumen PRD untuk gambaran seluruh sistem.

## Menjalankan secara lokal

Prasyarat: Docker Desktop menyala.

```bash
cp .env.example .env
# edit .env: isi DB_PASSWORD & DB_ROOT_PASSWORD dengan password acak yang kuat
# (jangan pakai nilai contoh apa adanya)

docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

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

Belum dikerjakan (fase berikutnya, sesuai rencana): Koreksi Absensi (5.4), Pengajuan
Cuti/Sisa Cuti/Bon Cuti/Kalender Cuti (5.5–5.8), Surat Tugas/Perjalanan Dinas (5.10),
Notifikasi (11), Laporan lanjutan (12).

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

**Belum dikerjakan — jangan deploy ke produksi sebelum ini selesai:**
- 2FA untuk role HR/Administrator/Direksi
- Security headers lengkap (CSP, HSTS) — baru relevan penuh setelah pakai domain + HTTPS
- `composer audit` rutin sebagai bagian dari proses deploy
- Strategi backup & rotasi database
- `docker-compose.prod.yml` terpisah (compose saat ini untuk dev: Mailpit, port MySQL
  ke host, dll — semuanya perlu ditinjau ulang sebelum dipindah ke mini PC)

## Struktur project

```
hris-apic/
├── docker-compose.yml       # 7 service: app, nginx, mysql, redis, queue, scheduler, mailpit
├── Dockerfile                # image app (php-fpm), non-root
├── docker/
│   ├── nginx/default.conf
│   └── php/php.ini
├── .env                       # kredensial compose (root-level, git-ignored)
└── src/                       # aplikasi Laravel
    ├── app/
    │   ├── Filament/
    │   │   ├── Resources/          # panel admin
    │   │   ├── Portal/Resources/   # panel portal (pegawai/atasan)
    │   │   ├── Pages/               # halaman custom (mis. Import Absensi)
    │   │   └── Widgets/
    │   ├── Imports/                 # parser Excel (AttendanceExceptionStatSheetImport)
    │   ├── Jobs/                    # ProcessAttendanceImport (queued)
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
```
