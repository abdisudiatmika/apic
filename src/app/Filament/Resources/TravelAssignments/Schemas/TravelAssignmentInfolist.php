<?php

namespace App\Filament\Resources\TravelAssignments\Schemas;

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
                        TextEntry::make('transportation')->label('Transportasi')->placeholder('-'),
                        TextEntry::make('start_date')->label('Mulai')->date(),
                        TextEntry::make('end_date')->label('Selesai')->date(),
                        TextEntry::make('estimated_cost')->label('Estimasi Biaya')->money('IDR')->placeholder('-'),
                        TextEntry::make('purpose')->label('Keperluan')->columnSpanFull(),
                    ]),

                Section::make('Pegawai yang Ditugaskan')
                    ->schema([
                        RepeatableEntry::make('employees')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')->label('Nama'),
                                TextEntry::make('nip')->label('NIP'),
                                TextEntry::make('department.name')->label('Departemen'),
                            ])
                            ->columns(3),
                    ]),

                Section::make('Approval Atasan')
                    ->columns(3)
                    ->visible(fn (TravelAssignment $record) => $record->atasan_id !== null)
                    ->schema([
                        TextEntry::make('atasan.name')->label('Atasan'),
                        TextEntry::make('atasan_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('atasan_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),

                Section::make('Approval & Penandatangan HR')
                    ->columns(3)
                    ->visible(fn (TravelAssignment $record) => $record->hr_id !== null)
                    ->schema([
                        TextEntry::make('hr.name')->label('HR'),
                        TextEntry::make('hr_at')->label('Tanggal')->dateTime(),
                        TextEntry::make('signatory_name')->label('Penandatangan')->placeholder('-'),
                        TextEntry::make('hr_note')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}
