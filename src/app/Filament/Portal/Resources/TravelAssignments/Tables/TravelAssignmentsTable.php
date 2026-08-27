<?php

namespace App\Filament\Portal\Resources\TravelAssignments\Tables;

use App\Models\TravelAssignment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TravelAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('letter_number')
                    ->label('No. Surat')
                    ->placeholder('-'),
                TextColumn::make('requester.name')
                    ->label('Pemohon'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (TravelAssignment $record) => $record->typeLabel()),
                TextColumn::make('destination')
                    ->label('Tujuan'),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date(),
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
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('note')->label('Catatan (opsional)')])
                    ->action(function (TravelAssignment $record, array $data) {
                        $record->approveByAtasan(auth()->user(), $data['note'] ?? null);
                        Notification::make()->title('Disetujui, diteruskan ke HR')->success()->send();
                    }),

                Action::make('rejectAsAtasan')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('approveAsAtasan', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('note')->label('Alasan Penolakan')->required()])
                    ->action(function (TravelAssignment $record, array $data) {
                        $record->rejectByAtasan(auth()->user(), $data['note']);
                        Notification::make()->title('Pengajuan ditolak')->danger()->send();
                    }),

                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-trash')
                    ->color('gray')
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('cancel', $record))
                    ->requiresConfirmation()
                    ->action(function (TravelAssignment $record) {
                        $record->cancel(auth()->user());
                        Notification::make()->title('Pengajuan dibatalkan')->success()->send();
                    }),

                Action::make('downloadPdf')
                    ->label('Unduh PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('downloadPdf', $record))
                    ->url(fn (TravelAssignment $record) => route('travel-assignments.pdf', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
