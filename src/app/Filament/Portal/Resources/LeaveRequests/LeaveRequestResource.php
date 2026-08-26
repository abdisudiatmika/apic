<?php

namespace App\Filament\Portal\Resources\LeaveRequests;

use App\Filament\Portal\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Portal\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Portal\Resources\LeaveRequests\Pages\ViewLeaveRequest;
use App\Filament\Portal\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Portal\Resources\LeaveRequests\Schemas\LeaveRequestInfolist;
use App\Filament\Portal\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * One resource serves three roles via query-scope + per-action authorization
 * (same pattern as the portal AttendanceLogResource from Fase 1): pegawai submits
 * and sees their own history; atasan additionally sees their team's requests and
 * gets the "Setujui (Atasan)"/"Tolak (Atasan)" table actions.
 */
class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Cuti';

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

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'view' => ViewLeaveRequest::route('/{record}'),
        ];
    }
}
