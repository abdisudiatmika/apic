<?php

namespace App\Filament\Resources\AttendanceLogs\Tables;

use App\Models\Branch;
use App\Models\Department;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('employee.department.name')
                    ->label('Departemen'),
                TextColumn::make('employee.branch.name')
                    ->label('Cabang'),
                TextColumn::make('check_in')
                    ->label('Jam Masuk')
                    ->placeholder('-'),
                TextColumn::make('check_out')
                    ->label('Jam Keluar')
                    ->placeholder('-'),
                TextColumn::make('late_minutes')
                    ->label('Telat (menit)')
                    ->numeric(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'tidak_hadir' => 'danger',
                        'dinas' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'tidak_hadir' => 'Tidak Hadir',
                        'dinas' => 'Dinas',
                        default => $state,
                    }),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'device' => 'Mesin',
                        'excel_import' => 'Import Excel',
                        'manual_correction' => 'Koreksi Manual',
                        'travel_assignment' => 'Surat Tugas',
                        default => $state,
                    })
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('employee.department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $value) => $q->whereRelation('employee', 'department_id', $value)
                    )),
                SelectFilter::make('employee.branch_id')
                    ->label('Cabang')
                    ->options(fn () => Branch::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $value) => $q->whereRelation('employee', 'branch_id', $value)
                    )),
                SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'tidak_hadir' => 'Tidak Hadir',
                        'dinas' => 'Dinas',
                    ]),
                Filter::make('date')
                    ->schema([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('date', '>=', $date))
                        ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('date', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
