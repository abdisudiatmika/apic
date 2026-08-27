<?php

namespace App\Filament\Resources\AttendanceCorrections\Tables;

use App\Models\AttendanceCorrection;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->searchable(),
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
                        'menunggu_atasan' => 'Menunggu (Atasan)',
                        'menunggu_hr' => 'Menunggu (HR)',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        'dibatalkan' => 'Dibatalkan',
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
                        'dibatalkan' => 'Dibatalkan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsHr')
                    ->label('Setujui (HR)')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (AttendanceCorrection $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (AttendanceCorrection $record, array $data) {
                        $record->approveByHr(auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Koreksi absensi disetujui')->success()->send();
                    }),

                Action::make('rejectAsHr')
                    ->label('Tolak (HR)')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (AttendanceCorrection $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (AttendanceCorrection $record, array $data) {
                        $record->rejectByHr(auth()->user(), $data['note']);

                        Notification::make()->title('Koreksi absensi ditolak')->danger()->send();
                    }),
            ]);
    }
}
