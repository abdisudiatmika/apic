<?php

namespace App\Filament\Portal\Resources\AttendanceLogs;

use App\Filament\Portal\Resources\AttendanceLogs\Pages\ListAttendanceLogs;
use App\Filament\Portal\Resources\AttendanceLogs\Pages\ViewAttendanceLog;
use App\Filament\Portal\Resources\AttendanceLogs\Schemas\AttendanceLogInfolist;
use App\Filament\Portal\Resources\AttendanceLogs\Tables\AttendanceLogsTable;
use App\Models\AttendanceLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Self-service, read-only. Query is scoped per role in getEloquentQuery() — this is
 * the actual enforcement point (an atasan/pegawai guessing another employee's
 * attendance-log URL still gets a 404, not just a hidden row), the AttendanceLogPolicy
 * underneath is the second, independent layer for the same rule.
 */
class AttendanceLogResource extends Resource
{
    protected static ?string $model = AttendanceLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Absensi';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = parent::getEloquentQuery();

        if ($user->hasRole('atasan') && $employee) {
            return $query->whereHas(
                'employee',
                fn (Builder $q) => $q->where('supervisor_id', $employee->id)->orWhere('id', $employee->id)
            );
        }

        return $query->whereHas('employee', fn (Builder $q) => $q->where('user_id', $user->id));
    }

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
