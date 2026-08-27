<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections\Tables;

use App\Models\AttendanceCorrection;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttendanceCorrectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->visible(fn () => auth()->user()->hasRole('atasan')),
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date(),
                TextColumn::make('requested_check_in')
                    ->label('Jam Masuk Diajukan')
                    ->placeholder('-'),
                TextColumn::make('requested_check_out')
                    ->label('Jam Keluar Diajukan')
                    ->placeholder('-'),
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
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsAtasan')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (AttendanceCorrection $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('note')->label('Catatan (opsional)')])
                    ->action(function (AttendanceCorrection $record, array $data) {
                        $record->approveByAtasan(auth()->user(), $data['note'] ?? null);
                        Notification::make()->title('Disetujui, diteruskan ke HR')->success()->send();
                    }),

                Action::make('rejectAsAtasan')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (AttendanceCorrection $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('note')->label('Alasan Penolakan')->required()])
                    ->action(function (AttendanceCorrection $record, array $data) {
                        $record->rejectByAtasan(auth()->user(), $data['note']);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    }),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->visible(fn (AttendanceCorrection $record) => auth()->user()->can('cancel', $record))
                    ->requiresConfirmation()
                    ->action(function (AttendanceCorrection $record) {
                        $record->cancel(auth()->user());
                        Notification::make()->title('Pengajuan dibatalkan')->success()->send();
                    }),
            ]);
    }
}
