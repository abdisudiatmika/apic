<?php

namespace App\Filament\Portal\Resources\LeaveAdvances\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name', fn ($query) => $query->where('is_active', true))
                    ->live()
                    ->required(),
                TextInput::make('days')
                    ->label('Jumlah Hari yang Diajukan')
                    ->numeric()
                    ->minValue(0.5)
                    ->step(0.5)
                    ->required(),
                Textarea::make('reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
            ]);
    }
}
