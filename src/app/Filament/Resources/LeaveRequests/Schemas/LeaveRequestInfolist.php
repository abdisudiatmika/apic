<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use App\Models\LeaveRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pengajuan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')->label('Pegawai'),
                        TextEntry::make('leaveType.name')->label('Jenis Cuti'),
                        TextEntry::make('start_date')->label('Mulai')->date(),
                        TextEntry::make('end_date')->label('Selesai')->date(),
                        TextEntry::make('days')->label('Jumlah Hari')->numeric(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('replacementEmployee.name')->label('Pegawai Pengganti')->placeholder('-'),
                        TextEntry::make('reason')->label('Alasan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn (LeaveRequest $record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval HR')
                    ->columns(3)
                    ->visible(fn (LeaveRequest $record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
