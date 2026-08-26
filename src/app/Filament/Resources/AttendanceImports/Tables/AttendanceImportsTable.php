<?php

namespace App\Filament\Resources\AttendanceImports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('uploadedBy.name')
                    ->label('Diupload oleh')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'partial' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'processing' => 'Diproses',
                        'success' => 'Berhasil',
                        'partial' => 'Sebagian',
                        'failed' => 'Gagal',
                        default => $state,
                    }),
                TextColumn::make('row_success')
                    ->label('Baris Berhasil')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('row_failed')
                    ->label('Baris Gagal')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : null),
                TextColumn::make('created_at')
                    ->label('Waktu Upload')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'processing' => 'Diproses',
                        'success' => 'Berhasil',
                        'partial' => 'Sebagian',
                        'failed' => 'Gagal',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
