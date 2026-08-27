<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

/**
 * Only what a pegawai fills in — employee_id/status/approval columns are injected
 * in Pages\CreateAttendanceCorrection, same pattern as the Cuti forms in Fase 2.
 */
class AttendanceCorrectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal yang Dikoreksi')
                    ->native(false)
                    ->maxDate(now())
                    ->required(),
                TimePicker::make('requested_check_in')
                    ->label('Jam Masuk Seharusnya')
                    ->seconds(false),
                TimePicker::make('requested_check_out')
                    ->label('Jam Keluar Seharusnya')
                    ->seconds(false),
                Textarea::make('reason')
                    ->label('Alasan')
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('attachment_path')
                    ->label('Lampiran (opsional)')
                    ->disk('local')
                    ->directory('attendance-correction-attachments')
                    ->visibility('private')
                    ->maxSize(5120),
            ]);
    }
}
