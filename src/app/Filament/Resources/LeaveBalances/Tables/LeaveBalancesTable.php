<?php

namespace App\Filament\Resources\LeaveBalances\Tables;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->searchable(),
                TextColumn::make('year')
                    ->label('Tahun')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('entitled_days')
                    ->label('Hak Cuti')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('carry_forward_days')
                    ->label('Carry Forward')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sisa')
                    ->label('Sisa Tersedia')
                    ->state(function (LeaveBalance $record) {
                        $summary = app(LeaveBalanceService::class)->summary($record->employee, $record->leaveType, $record->year);

                        return number_format($summary->available, 1);
                    })
                    ->badge()
                    ->color(fn (LeaveBalance $record) => app(LeaveBalanceService::class)
                        ->summary($record->employee, $record->leaveType, $record->year)->available < 0 ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->options(fn () => LeaveType::pluck('name', 'id')),
                SelectFilter::make('year')
                    ->options(fn () => LeaveBalance::query()->distinct()->orderByDesc('year')->pluck('year', 'year')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
