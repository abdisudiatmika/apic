<?php

namespace App\Filament\Portal\Resources\TravelAssignments;

use App\Filament\Portal\Resources\TravelAssignments\Pages\CreateTravelAssignment;
use App\Filament\Portal\Resources\TravelAssignments\Pages\ListTravelAssignments;
use App\Filament\Portal\Resources\TravelAssignments\Pages\ViewTravelAssignment;
use App\Filament\Portal\Resources\TravelAssignments\Schemas\TravelAssignmentForm;
use App\Filament\Portal\Resources\TravelAssignments\Schemas\TravelAssignmentInfolist;
use App\Filament\Portal\Resources\TravelAssignments\Tables\TravelAssignmentsTable;
use App\Models\TravelAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TravelAssignmentResource extends Resource
{
    protected static ?string $model = TravelAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static ?string $navigationLabel = 'Surat Tugas';

    /**
     * Pegawai: sees requests they submitted OR where they're one of the assigned
     * employees. Atasan: additionally sees requests submitted by their team.
     */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $employee = $user->employee;

        $query = parent::getEloquentQuery();

        return $query->where(function (Builder $q) use ($user, $employee) {
            $q->whereHas('requester', fn (Builder $r) => $r->where('user_id', $user->id))
                ->orWhereHas('employees', fn (Builder $e) => $e->where('user_id', $user->id));

            if ($user->hasRole('atasan') && $employee) {
                $q->orWhereHas('requester', fn (Builder $r) => $r->where('supervisor_id', $employee->id));
            }
        });
    }

    public static function form(Schema $schema): Schema
    {
        return TravelAssignmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TravelAssignmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TravelAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTravelAssignments::route('/'),
            'create' => CreateTravelAssignment::route('/create'),
            'view' => ViewTravelAssignment::route('/{record}'),
        ];
    }
}
