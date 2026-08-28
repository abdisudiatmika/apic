<?php

namespace App\Filament\Portal\Resources\TravelAssignments\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TravelAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Jenis Pengajuan')
                    ->options([
                        'surat_tugas' => 'Surat Tugas',
                        'perjalanan_dinas' => 'Perjalanan Dinas',
                        'surat_jalan' => 'Surat Jalan',
                    ])
                    ->default('surat_tugas')
                    ->required(),
                Select::make('employee_ids')
                    ->label('Pegawai yang Ditugaskan')
                    ->helperText('Bisa lebih dari satu, termasuk diri sendiri.')
                    ->options(fn () => Employee::where('is_active', true)->pluck('name', 'id'))
                    ->multiple()
                    ->searchable()
                    ->required(),
                TextInput::make('destination')
                    ->label('Tujuan/Lokasi')
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Tanggal Berangkat')
                    ->native(false)
                    ->required()
                    ->live(),
                DatePicker::make('end_date')
                    ->label('Tanggal Pulang')
                    ->native(false)
                    ->required()
                    ->minDate(fn ($get) => $get('start_date')),
                Textarea::make('purpose')
                    ->label('Keperluan/Agenda')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('transportation')
                    ->label('Kendaraan/Transportasi (opsional)'),
                TextInput::make('estimated_cost')
                    ->label('Estimasi Biaya (opsional)')
                    ->numeric()
                    ->prefix('Rp'),
                FileUpload::make('attachment_path')
                    ->label('Lampiran Pendukung (opsional)')
                    ->disk('local')
                    ->directory('travel-assignment-attachments')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(5120)
                    ->columnSpanFull(),
            ]);
    }
}
