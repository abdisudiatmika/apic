<?php

namespace App\Filament\Resources\LeaveAdvances;

use App\Filament\Resources\LeaveAdvances\Pages\ListLeaveAdvances;
use App\Filament\Resources\LeaveAdvances\Pages\ViewLeaveAdvance;
use App\Filament\Resources\LeaveAdvances\Schemas\LeaveAdvanceInfolist;
use App\Filament\Resources\LeaveAdvances\Tables\LeaveAdvancesTable;
use App\Models\LeaveAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveAdvanceResource extends Resource
{
    protected static ?string $model = LeaveAdvance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Bon Cuti';

    protected static string|\UnitEnum|null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return LeaveAdvanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveAdvancesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveAdvances::route('/'),
            'view' => ViewLeaveAdvance::route('/{record}'),
        ];
    }
}
