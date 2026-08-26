<?php

namespace App\Filament\Resources\LeaveBlackoutDates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveBlackoutDateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->label('Tanggal')
                    ->native(false)
                    ->required(),
                Select::make('department_id')
                    ->label('Departemen')
                    ->helperText('Kosongkan untuk berlaku di semua departemen.')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('reason')
                    ->label('Alasan')
                    ->required(),
            ]);
    }
}
