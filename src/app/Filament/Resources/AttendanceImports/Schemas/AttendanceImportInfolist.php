<?php

namespace App\Filament\Resources\AttendanceImports\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceImportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('file_name')->label('File'),
                        TextEntry::make('uploadedBy.name')->label('Diupload oleh')->placeholder('-'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('created_at')->label('Waktu Upload')->dateTime(),
                        TextEntry::make('row_success')->label('Baris Berhasil')->numeric(),
                        TextEntry::make('row_failed')->label('Baris Gagal')->numeric(),
                    ]),

                Section::make('Baris yang Gagal Dicocokkan')
                    ->description('Pegawai dengan ID mesin absensi ini belum terdaftar di cabang yang dipilih, atau tanggalnya tidak terbaca — tindak lanjuti secara manual.')
                    ->visible(fn ($record) => $record->errors()->exists())
                    ->schema([
                        RepeatableEntry::make('errors')
                            ->label('')
                            ->schema([
                                TextEntry::make('row_number')->label('Baris ke-'),
                                TextEntry::make('reason')->label('Alasan')->columnSpan(2),
                            ])
                            ->columns(3),
                    ]),
            ]);
    }
}
