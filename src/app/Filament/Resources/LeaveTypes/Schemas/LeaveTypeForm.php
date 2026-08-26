<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Jenis Cuti')
                    ->required(),
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('default_days_per_year')
                    ->label('Jatah Hari per Tahun')
                    ->helperText('Juga dipakai sebagai batas maksimal pengajuan Bon Cuti untuk jenis ini.')
                    ->required()
                    ->numeric()
                    ->default(12),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
