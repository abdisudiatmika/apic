<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Branch;
use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->searchable(),
                TextColumn::make('position.name')
                    ->label('Jabatan')
                    ->searchable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable(),
                TextColumn::make('supervisor.name')
                    ->label('Atasan')
                    ->searchable(),
                TextColumn::make('employment_status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('attendance_machine_id')
                    ->label('ID Mesin Absensi')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::pluck('name', 'id')),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->options(fn () => Branch::pluck('name', 'id')),
                SelectFilter::make('employment_status')
                    ->label('Status Pegawai')
                    ->options(['tetap' => 'Tetap', 'kontrak' => 'Kontrak', 'probation' => 'Probation']),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
