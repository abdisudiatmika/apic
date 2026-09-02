<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeePerformanceExport implements Export, FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Pegawai', 'Departemen', 'Hadir', 'Terlambat', 'Rata-rata Jam Kerja', 'Total Cuti', 'Sisa Cuti'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->employee->department?->name ?? '-',
            $row->hadir,
            $row->terlambat,
            $row->avg_work_hours,
            $row->total_cuti_days,
            $row->sisa_cuti,
        ];
    }
}
