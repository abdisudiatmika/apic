<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Laravel's schema builder has no portable "modify enum" helper, so this alters
 * the column directly. Adds the 'dinas' status (a pegawai on an approved Surat
 * Tugas/Perjalanan Dinas must not read as "tidak_hadir" — PRD 5.10's "Integrasi
 * status dengan absensi") and the 'travel_assignment' source that produces it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE attendance_logs MODIFY status ENUM('hadir', 'terlambat', 'tidak_hadir', 'dinas') NOT NULL DEFAULT 'hadir'");
        DB::statement("ALTER TABLE attendance_logs MODIFY source ENUM('device', 'excel_import', 'manual_correction', 'travel_assignment') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE attendance_logs MODIFY status ENUM('hadir', 'terlambat', 'tidak_hadir') NOT NULL DEFAULT 'hadir'");
        DB::statement("ALTER TABLE attendance_logs MODIFY source ENUM('device', 'excel_import', 'manual_correction') NOT NULL");
    }
};
