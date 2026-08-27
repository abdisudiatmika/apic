<?php

namespace App\Filament\Resources\TravelAssignments;

use App\Filament\Resources\TravelAssignments\Pages\ListTravelAssignments;
use App\Filament\Resources\TravelAssignments\Pages\ViewTravelAssignment;
use App\Filament\Resources\TravelAssignments\Schemas\TravelAssignmentInfolist;
use App\Filament\Resources\TravelAssignments\Tables\TravelAssignmentsTable;
use App\Models\TravelAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TravelAssignmentResource extends Resource
{
    protected static ?string $model = TravelAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Surat Tugas';

    protected static string|UnitEnum|null $navigationGroup = 'Surat Tugas';

    public static function infolist(Schema $schema): Schema
    {
        return TravelAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelAssignmentsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelAssignments::route('/'),
            'view' => ViewTravelAssignment::route('/{record}'),
        ];
    }
}
