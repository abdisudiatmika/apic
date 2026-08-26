<?php

namespace App\Filament\Portal\Resources\LeaveAdvances;

use App\Filament\Portal\Resources\LeaveAdvances\Pages\CreateLeaveAdvance;
use App\Filament\Portal\Resources\LeaveAdvances\Pages\ListLeaveAdvances;
use App\Filament\Portal\Resources\LeaveAdvances\Pages\ViewLeaveAdvance;
use App\Filament\Portal\Resources\LeaveAdvances\Schemas\LeaveAdvanceForm;
use App\Filament\Portal\Resources\LeaveAdvances\Schemas\LeaveAdvanceInfolist;
use App\Filament\Portal\Resources\LeaveAdvances\Tables\LeaveAdvancesTable;
use App\Models\LeaveAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeaveAdvanceResource extends Resource
{
    protected static ?string $model = LeaveAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Bon Cuti';

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
        return LeaveAdvanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveAdvanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveAdvancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveAdvances::route('/'),
            'create' => CreateLeaveAdvance::route('/create'),
            'view' => ViewLeaveAdvance::route('/{record}'),
        ];
    }
}
