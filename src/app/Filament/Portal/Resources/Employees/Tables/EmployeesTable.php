<?php

namespace App\Filament\Portal\Resources\Employees\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nip')->label('NIP'),
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('department.name')->label('Departemen'),
                TextColumn::make('position.name')->label('Jabatan'),
                TextColumn::make('branch.name')->label('Cabang'),
                TextColumn::make('employment_status')->label('Status')->badge(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
