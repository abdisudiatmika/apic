# HRIS APIC — Fase 1, 2 & 3 (Lokal, Docker)

Implementasi Fase 1 (fondasi: autentikasi, RBAC, data pegawai, master data, absensi +
5.3.1 Import Excel), Fase 2 (Cuti & Bon Cuti: 5.5–5.8), dan Fase 3 (Koreksi Absensi 5.4,
Surat Tugas/Perjalanan Dinas 5.10, Notifikasi 11) dari PRD "Sistem HR & Kepegawaian"
v2.1. Lihat `/Users/abdisudiatmika/.claude/plans/melodic-cuddling-goose.md` untuk
rencana lengkap fase yang sedang berjalan, atau dokumen PRD untuk gambaran seluruh
sistem.

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

Belum dikerjakan (fase berikutnya, sesuai rencana): Laporan & Analitik lanjutan (12),
dan hardening keamanan sebelum produksi (lihat bagian Keamanan di bawah).

**Keterbatasan yang diketahui (Fase 3):** format nomor surat, kop surat, dan pihak
penandatangan pada PDF Surat Tugas bersifat placeholder generik — PRD 5.10 sendiri
menandai ini perlu disesuaikan dengan SOP resmi perusahaan sebelum dipakai nyata.
Notifikasi email/WhatsApp belum ada, baru notifikasi dalam aplikasi (bell icon).

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
    │   │   ├── Portal/              # panel portal (pegawai/atasan): Resources, Pages, Widgets
    │   │   ├── Pages/               # halaman custom admin (Import Absensi, Kalender Cuti)
    │   │   └── Widgets/
    │   ├── Imports/                 # parser Excel (AttendanceExceptionStatSheetImport)
    │   ├── Jobs/                    # ProcessAttendanceImport (queued)
    │   ├── Services/                 # LeaveBalanceService — mesin hitung saldo cuti
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
```
