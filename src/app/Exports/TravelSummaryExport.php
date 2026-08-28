<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TravelSummaryExport implements Export, FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Pegawai', 'Surat Tugas', 'Perjalanan Dinas', 'Surat Jalan', 'Total Hari'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->surat_tugas,
            $row->perjalanan_dinas,
            $row->surat_jalan,
            $row->total_hari,
        ];
    }
}
