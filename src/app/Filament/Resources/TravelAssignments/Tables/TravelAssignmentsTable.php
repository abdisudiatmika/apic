<?php

namespace App\Filament\Resources\TravelAssignments\Tables;

use App\Models\TravelAssignment;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('requester.name')
                    ->label('Pemohon')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (TravelAssignment $record) => $record->typeLabel()),
                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date(),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date(),
                TextColumn::make('employees.name')
                    ->label('Pegawai Ditugaskan')
                    ->listWithLineBreaks()
                    ->limitList(3),
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
                SelectFilter::make('type')
                    ->options([
                        'surat_tugas' => 'Surat Tugas',
                        'perjalanan_dinas' => 'Perjalanan Dinas',
                        'surat_jalan' => 'Surat Jalan',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsHr')
                    ->label('Setujui & Terbitkan (HR)')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        TextInput::make('signatory_name')->label('Nama Penandatangan')->required(),
                        TextInput::make('signatory_position')->label('Jabatan Penandatangan')->required(),
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (TravelAssignment $record, array $data) {
                        $record->approveByHr(auth()->user(), $data['note'] ?? null, $data['signatory_name'], $data['signatory_position']);

                        Notification::make()->title('Surat tugas disetujui & nomor diterbitkan')->success()->send();
                    }),

                Action::make('rejectAsHr')
                    ->label('Tolak (HR)')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (TravelAssignment $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('note')->label('Alasan Penolakan')->required()])
                    ->action(function (TravelAssignment $record, array $data) {
                        $record->rejectByHr(auth()->user(), $data['note']);

                        Notification::make()->title('Surat tugas ditolak')->danger()->send();
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
