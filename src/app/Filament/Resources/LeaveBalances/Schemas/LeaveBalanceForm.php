<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name')
                    ->required(),
                TextInput::make('year')
                    ->label('Tahun')
                    ->required()
                    ->numeric()
                    ->default(now()->year),
                TextInput::make('entitled_days')
                    ->label('Hak Cuti (hari)')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('carry_forward_days')
                    ->label('Carry Forward (hari)')
                    ->helperText('Sisa cuti tahun sebelumnya yang dibawa ke tahun ini, jika ada.')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
