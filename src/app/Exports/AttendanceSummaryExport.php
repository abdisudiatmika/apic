<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceSummaryExport implements Export, FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Pegawai', 'Hadir', 'Terlambat', 'Tidak Hadir', 'Dinas', 'Total Menit Terlambat', 'Rata-rata Menit Terlambat'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->hadir,
            $row->terlambat,
            $row->tidak_hadir,
            $row->dinas,
            $row->total_late_minutes,
            $row->avg_late_minutes,
        ];
    }
}
