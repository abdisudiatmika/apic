<?php

namespace App\Filament\Resources\AttendanceLogs;

use App\Filament\Resources\AttendanceLogs\Pages\ListAttendanceLogs;
use App\Filament\Resources\AttendanceLogs\Pages\ViewAttendanceLog;
use App\Filament\Resources\AttendanceLogs\Schemas\AttendanceLogInfolist;
use App\Filament\Resources\AttendanceLogs\Tables\AttendanceLogsTable;
use App\Models\AttendanceLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * List + view only for Phase 1. Manual creation/editing belongs to the "Koreksi
 * Absensi" workflow (PRD 5.4, a later phase) which needs its own approval trail —
 * letting this resource edit records directly would bypass that.
 */
class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Data Absensi';

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceLogsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceLogs::route('/'),
            'view' => ViewAttendanceLog::route('/{record}'),
        ];
    }
}
