<?php

namespace App\Filament\Portal\Resources\Employees;

use App\Filament\Portal\Resources\Employees\Pages\ListEmployees;
use App\Filament\Portal\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Portal\Resources\Employees\Schemas\EmployeeInfolist;
use App\Filament\Portal\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Read-only self-service: a pegawai sees only their own record, an atasan sees
 * their own plus their direct reports'. Editing employee data stays an HR/Admin
 * job in the admin panel (EmployeeResource there).
 */
class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $navigationLabel = 'Profil Pegawai';

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = parent::getEloquentQuery();

        if ($user->hasRole('atasan') && $employee) {
            return $query->where(fn (Builder $q) => $q->where('supervisor_id', $employee->id)->orWhere('id', $employee->id));
        }

        return $query->where('user_id', $user->id);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'view' => ViewEmployee::route('/{record}'),
        ];
    }
}
