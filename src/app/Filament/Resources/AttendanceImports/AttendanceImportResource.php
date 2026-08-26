<?php

namespace App\Filament\Resources\AttendanceImports;

use App\Filament\Resources\AttendanceImports\Pages\ListAttendanceImports;
use App\Filament\Resources\AttendanceImports\Pages\ViewAttendanceImport;
use App\Filament\Resources\AttendanceImports\Schemas\AttendanceImportInfolist;
use App\Filament\Resources\AttendanceImports\Tables\AttendanceImportsTable;
use App\Models\AttendanceImport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only by design: a record is only ever created by UploadAttendanceImport's
 * submit() + the ProcessAttendanceImport job, never through a manual form here.
 */
class AttendanceImportResource extends Resource
{
    protected static ?string $model = AttendanceImport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Riwayat Import';

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceImportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceImportsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceImports::route('/'),
            'view' => ViewAttendanceImport::route('/{record}'),
        ];
    }
}
