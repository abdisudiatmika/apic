<?php

namespace App\Filament\Resources\AttendanceCorrections\Schemas;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceLog;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceCorrectionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengajuan Koreksi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')->label('Pegawai'),
                        TextEntry::make('date')->label('Tanggal')->date(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('reason')->label('Alasan')->columnSpanFull(),
                    ]),

                Section::make('Data Absensi Saat Ini')
                    ->description('Data yang tersimpan sebelum koreksi ini diterapkan.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('current_check_in')
                            ->label('Jam Masuk Saat Ini')
                            ->state(fn (AttendanceCorrection $record) => AttendanceLog::where('employee_id', $record->employee_id)
                                ->where('date', $record->date->toDateString())->value('check_in'))
                            ->placeholder('Tidak ada data'),
                        TextEntry::make('current_check_out')
                            ->label('Jam Keluar Saat Ini')
                            ->state(fn (AttendanceCorrection $record) => AttendanceLog::where('employee_id', $record->employee_id)
                                ->where('date', $record->date->toDateString())->value('check_out'))
                            ->placeholder('Tidak ada data'),
                    ]),

                Section::make('Diajukan Menjadi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('requested_check_in')->label('Jam Masuk')->placeholder('-'),
                        TextEntry::make('requested_check_out')->label('Jam Keluar')->placeholder('-'),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn (AttendanceCorrection $record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval HR')
                    ->columns(3)
                    ->visible(fn (AttendanceCorrection $record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
