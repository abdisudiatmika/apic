<?php

namespace App\Imports;

use App\Models\AttendanceImport;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Entry point for parsing an uploaded attendance export. Keyed by sheet title
 * (not index) so it targets the "Exception Stat." sheet specifically — the export
 * also contains "Jadwal Info", "Stat. Absen", and "Lap. Log Absen" sheets that are
 * not usable as an import source (see PRD 5.3.1 for why "Exception Stat." was
 * chosen: one row per employee per date, rather than one column per date).
 */
class AttendanceImportFile implements Import, WithMultipleSheets
{
    public function __construct(
        private readonly AttendanceImport $attendanceImport,
        private readonly int $branchId,
    ) {}

    public function sheets(): array
    {
        return [
            'Exception Stat.' => new AttendanceExceptionStatSheetImport($this->attendanceImport, $this->branchId),
        ];
    }
}
