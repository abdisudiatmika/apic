<?php

namespace App\Filament\Resources\LeaveAdvances\Schemas;

use App\Models\LeaveAdvance;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveAdvanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bon Cuti')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')->label('Pegawai'),
                        TextEntry::make('leaveType.name')->label('Jenis Cuti'),
                        TextEntry::make('days')->label('Hari Diajukan')->numeric(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('outstanding_days')->label('Sisa Outstanding')->numeric(),
                        TextEntry::make('settled_at')->label('Lunas Pada')->dateTime()->placeholder('Belum lunas'),
                        TextEntry::make('reason')->label('Alasan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn (LeaveAdvance $record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval HR')
                    ->columns(3)
                    ->visible(fn (LeaveAdvance $record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
