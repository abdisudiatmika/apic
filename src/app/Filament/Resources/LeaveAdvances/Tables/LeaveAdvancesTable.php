<?php

namespace App\Filament\Resources\LeaveAdvances\Tables;

use App\Models\LeaveAdvance;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveAdvancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti'),
                TextColumn::make('days')
                    ->label('Hari Diajukan')
                    ->numeric(),
                TextColumn::make('outstanding_days')
                    ->label('Outstanding')
                    ->numeric()
                    ->color(fn (float $state) => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'menunggu_atasan', 'menunggu_hr' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'menunggu_atasan' => 'Menunggu (Atasan)',
                        'menunggu_hr' => 'Menunggu (HR)',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'menunggu_atasan' => 'Menunggu (Atasan)',
                        'menunggu_hr' => 'Menunggu (HR)',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsHr')
                    ->label('Setujui (HR)')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveAdvance $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (LeaveAdvance $record, array $data) {
                        $record->approveByHr(auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Bon cuti disetujui')->success()->send();
                    }),

                Action::make('rejectAsHr')
                    ->label('Tolak (HR)')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (LeaveAdvance $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (LeaveAdvance $record, array $data) {
                        $record->rejectByHr(auth()->user(), $data['note']);

                        Notification::make()->title('Bon cuti ditolak')->danger()->send();
                    }),
            ]);
    }
}
