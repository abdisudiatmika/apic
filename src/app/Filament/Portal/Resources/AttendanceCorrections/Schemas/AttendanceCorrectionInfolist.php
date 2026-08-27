<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections\Schemas;

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
                        TextEntry::make('requested_check_in')->label('Jam Masuk Diajukan')->placeholder('-'),
                        TextEntry::make('requested_check_out')->label('Jam Keluar Diajukan')->placeholder('-'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('reason')->label('Alasan')->columnSpanFull(),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn ($record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval HR')
                    ->columns(3)
                    ->visible(fn ($record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
