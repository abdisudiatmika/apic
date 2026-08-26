<?php

namespace App\Filament\Portal\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->visible(fn () => auth()->user()->hasRole('atasan'))
                    ->searchable(),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti'),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date(),
                TextColumn::make('days')
                    ->label('Hari')
                    ->numeric(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'menunggu_atasan', 'menunggu_hr' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        'dibatalkan' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'menunggu_atasan', 'menunggu_hr' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dibatalkan' => 'Dibatalkan',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'menunggu_atasan' => 'Menunggu (Atasan)',
                        'menunggu_hr' => 'Menunggu (HR)',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dibatalkan' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsAtasan')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->approveByAtasan(auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Disetujui, diteruskan ke HR')->success()->send();
                    }),

                Action::make('rejectAsAtasan')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->rejectByAtasan(auth()->user(), $data['note']);

                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    }),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->can('cancel', $record))
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record) {
                        $record->cancel(auth()->user());

                        Notification::make()->title('Pengajuan dibatalkan')->success()->send();
                    }),
            ]);
    }
}
