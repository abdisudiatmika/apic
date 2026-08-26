<?php

namespace App\Imports;

use App\Models\AttendanceImport;
use App\Models\AttendanceImportError;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parses the "Exception Stat." sheet of the attendance machine's export.
 *
 * Real layout (confirmed against an actual export, see the PRD discussion this was
 * designed from): two title/date-range rows, then a header row ("ID", "Nama",
 * "Departemen", "Tgl", "Timezone I" x2, "Timezone II" x2, "Terlambat (Min)",
 * "Pulang Awal (Min)", "Absen (Min)", "Total (Min)", "Catatan"), then a sub-header
 * row ("Masuk"/"Keluar" under each Timezone), then one data row per employee per
 * date. The header row is located by content (not a hardcoded row number) so a
 * vendor export with an extra blank row doesn't silently misparse.
 */
class AttendanceExceptionStatSheetImport implements ToCollection
{
    public function __construct(
        private readonly AttendanceImport $attendanceImport,
        private readonly int $branchId,
    ) {}

    public function collection(Collection $rows): void
    {
        $headerIndex = $rows->search(
            fn ($row) => $this->cell($row, 0) === 'ID' && $this->cell($row, 1) === 'Nama'
        );

        if ($headerIndex === false) {
            $this->attendanceImport->update([
                'status' => 'failed',
                'row_success' => 0,
                'row_failed' => 0,
            ]);

            AttendanceImportError::create([
                'attendance_import_id' => $this->attendanceImport->id,
                'row_number' => 0,
                'raw_data' => [],
                'reason' => 'Sheet "Exception Stat." tidak dikenali: baris header (kolom "ID"/"Nama") tidak ditemukan. Format file mungkin berbeda dari yang diharapkan.',
            ]);

            return;
        }

        $dataStartIndex = $headerIndex + 2; // header row + the Masuk/Keluar sub-header row

        $employeesByMachineId = Employee::query()
            ->where('branch_id', $this->branchId)
            ->whereNotNull('attendance_machine_id')
            ->get()
            ->keyBy(fn (Employee $employee) => (string) $employee->attendance_machine_id);

        $success = 0;
        $failed = 0;

        for ($i = $dataStartIndex; $i < $rows->count(); $i++) {
            $row = $rows[$i];
            $rowNumber = $i + 1; // 1-indexed, matches what a human sees when opening the file

            $rawId = $this->cell($row, 0);

            if ($rawId === '' || $rawId === null) {
                continue; // blank separator row between employees, not an error
            }

            $employee = $employeesByMachineId->get($rawId);

            if (! $employee) {
                $this->recordError($rowNumber, $row, "ID mesin absensi \"{$rawId}\" tidak ditemukan pada pegawai di cabang yang dipilih.");
                $failed++;

                continue;
            }

            $date = $this->parseDate($row[3] ?? null);

            if (! $date) {
                $this->recordError($rowNumber, $row, 'Kolom "Tgl" kosong atau tidak dapat dibaca sebagai tanggal.');
                $failed++;

                continue;
            }

            $checkIn = $this->parseTime($row[4] ?? null);
            $checkOut = $this->parseTime($row[5] ?? null);
            $lateMinutes = (int) ($row[8] ?? 0);
            $earlyLeaveMinutes = (int) ($row[9] ?? 0);

            $status = match (true) {
                $checkIn === null && $checkOut === null => 'tidak_hadir',
                $lateMinutes > 0 => 'terlambat',
                default => 'hadir',
            };

            AttendanceLog::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $date],
                [
                    'check_in' => $checkIn,
                    'check_out' => $checkOut,
                    'late_minutes' => max($lateMinutes, 0),
                    'early_leave_minutes' => max($earlyLeaveMinutes, 0),
                    'status' => $status,
                    'source' => 'excel_import',
                    'attendance_import_id' => $this->attendanceImport->id,
                ]
            );

            $success++;
        }

        $this->attendanceImport->update([
            'row_success' => $success,
            'row_failed' => $failed,
            'status' => match (true) {
                $failed === 0 && $success > 0 => 'success',
                $success > 0 => 'partial',
                default => 'failed',
            },
        ]);
    }

    private function recordError(int $rowNumber, $row, string $reason): void
    {
        AttendanceImportError::create([
            'attendance_import_id' => $this->attendanceImport->id,
            'row_number' => $rowNumber,
            'raw_data' => $row instanceof Collection ? $row->toArray() : (array) $row,
            'reason' => $reason,
        ]);
    }

    private function cell($row, int $index): ?string
    {
        $value = $row[$index] ?? null;

        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTime(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i:s');
        }

        if (is_numeric($value)) {
            // Excel stores time-of-day as a fraction of a 24h day.
            $seconds = (int) round(((float) $value) * 86400);

            return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
