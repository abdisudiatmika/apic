<?php

namespace App\Filament\Portal\Resources\TravelAssignments\Pages;

use App\Filament\Portal\Resources\TravelAssignments\TravelAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTravelAssignments extends ListRecords
{
    protected static string $resource = TravelAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
