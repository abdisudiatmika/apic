<?php

namespace App\Filament\Portal\Resources\AttendanceLogs\Tables;

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
                    ->searchable()
                    ->visible(fn () => auth()->user()->hasRole('atasan')),
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
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'tidak_hadir' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'tidak_hadir' => 'Tidak Hadir',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'hadir' => 'Hadir',
                        'terlambat' => 'Terlambat',
                        'tidak_hadir' => 'Tidak Hadir',
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
