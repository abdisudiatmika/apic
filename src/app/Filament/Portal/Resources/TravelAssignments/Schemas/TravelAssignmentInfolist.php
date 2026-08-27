<?php

namespace App\Filament\Portal\Resources\TravelAssignments\Schemas;

use App\Models\TravelAssignment;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TravelAssignmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Surat Tugas / Perjalanan Dinas')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('letter_number')->label('No. Surat')->placeholder('Belum diterbitkan'),
                        TextEntry::make('requester.name')->label('Pemohon'),
                        TextEntry::make('type')->label('Jenis')->formatStateUsing(fn (TravelAssignment $record) => $record->typeLabel()),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('destination')->label('Tujuan'),
                        TextEntry::make('start_date')->label('Mulai')->date(),
                        TextEntry::make('end_date')->label('Selesai')->date(),
                        TextEntry::make('purpose')->label('Keperluan')->columnSpanFull(),
                    ]),

                Section::make('Pegawai yang Ditugaskan')
                    ->schema([
                        RepeatableEntry::make('employees')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label('Nama'),
                                TextEntry::make('nip')->label('NIP'),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn (TravelAssignment $record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval HR')
                    ->columns(3)
                    ->visible(fn (TravelAssignment $record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
