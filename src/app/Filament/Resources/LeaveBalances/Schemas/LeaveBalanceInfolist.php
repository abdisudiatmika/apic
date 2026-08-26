<?php

namespace App\Filament\Resources\LeaveBalances\Schemas;

use App\Models\LeaveBalance;
use App\Services\LeaveBalanceService;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveBalanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')->label('Pegawai'),
                        TextEntry::make('leaveType.name')->label('Jenis Cuti'),
                        TextEntry::make('year')->label('Tahun'),
                        TextEntry::make('entitled_days')->label('Hak Cuti'),
                        TextEntry::make('carry_forward_days')->label('Carry Forward'),
                        TextEntry::make('available')
                            ->label('Sisa Tersedia')
                            ->state(fn (LeaveBalance $record) => app(LeaveBalanceService::class)
                                ->summary($record->employee, $record->leaveType, $record->year)->available)
                            ->badge(),
                    ]),

                Section::make('Riwayat Penambahan/Pengurangan Saldo')
                    ->description('Termasuk potongan otomatis dari Bon Cuti (PRD 5.7).')
                    ->visible(fn (LeaveBalance $record) => $record->adjustments()->exists())
                    ->schema([
                        RepeatableEntry::make('adjustments')
                            ->label('')
                            ->schema([
                                TextEntry::make('amount')->label('Jumlah'),
                                TextEntry::make('reason')->label('Alasan')->columnSpan(2),
                                TextEntry::make('created_at')->label('Tanggal')->dateTime(),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }
}
