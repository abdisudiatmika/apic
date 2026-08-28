<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveSummaryExport implements Export, FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Pegawai', 'Total Pengajuan', 'Hari Disetujui', 'Hari Pending', 'Ditolak'];
    }

    public function map($row): array
    {
        return [
            $row->employee->name,
            $row->total_pengajuan,
            $row->hari_disetujui,
            $row->hari_pending,
            $row->ditolak,
        ];
    }
}
