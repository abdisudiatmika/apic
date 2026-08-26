<?php

namespace App\Filament\Portal\Resources\LeaveAdvances\Tables;

use App\Models\LeaveAdvance;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
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
                    ->visible(fn () => auth()->user()->hasRole('atasan')),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti'),
                TextColumn::make('days')
                    ->label('Hari Diajukan')
                    ->numeric(),
                TextColumn::make('outstanding_days')
                    ->label('Outstanding')
                    ->numeric(),
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
                        'menunggu_atasan', 'menunggu_hr' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    }),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsAtasan')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveAdvance $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (LeaveAdvance $record, array $data) {
                        $record->approveByAtasan(auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Disetujui, diteruskan ke HR')->success()->send();
                    }),

                Action::make('rejectAsAtasan')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (LeaveAdvance $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (LeaveAdvance $record, array $data) {
                        $record->rejectByAtasan(auth()->user(), $data['note']);

                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    }),
            ]);
    }
}
