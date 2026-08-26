<?php

namespace App\Filament\Resources\LeaveBlackoutDates;

use App\Filament\Resources\LeaveBlackoutDates\Pages\CreateLeaveBlackoutDate;
use App\Filament\Resources\LeaveBlackoutDates\Pages\EditLeaveBlackoutDate;
use App\Filament\Resources\LeaveBlackoutDates\Pages\ListLeaveBlackoutDates;
use App\Filament\Resources\LeaveBlackoutDates\Schemas\LeaveBlackoutDateForm;
use App\Filament\Resources\LeaveBlackoutDates\Tables\LeaveBlackoutDatesTable;
use App\Models\LeaveBlackoutDate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveBlackoutDateResource extends Resource
{
    protected static ?string $model = LeaveBlackoutDate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?string $navigationLabel = 'Tanggal Terbatas Cuti';

    protected static string|\UnitEnum|null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return LeaveBlackoutDateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveBlackoutDatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveBlackoutDates::route('/'),
            'create' => CreateLeaveBlackoutDate::route('/create'),
            'edit' => EditLeaveBlackoutDate::route('/{record}/edit'),
        ];
    }
}
