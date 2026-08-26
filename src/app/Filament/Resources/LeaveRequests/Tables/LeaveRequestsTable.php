<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('employee.department.name')
                    ->label('Departemen'),
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
                SelectFilter::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->options(fn () => LeaveType::pluck('name', 'id')),
                SelectFilter::make('employee.department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q, $value) => $q->whereRelation('employee', 'department_id', $value)
                    )),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('approveAsHr')
                    ->label('Setujui (HR)')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Catatan (opsional)'),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->approveByHr(auth()->user(), $data['note'] ?? null);

                        Notification::make()->title('Cuti disetujui')->success()->send();
                    }),

                Action::make('rejectAsHr')
                    ->label('Tolak (HR)')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (LeaveRequest $record) => auth()->user()->can('approveAsHr', $record))
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('note')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->rejectByHr(auth()->user(), $data['note']);

                        Notification::make()->title('Cuti ditolak')->danger()->send();
                    }),
            ]);
    }
}
