<?php

namespace App\Filament\Resources\TravelAssignments\Pages;

use App\Filament\Resources\TravelAssignments\TravelAssignmentResource;
use App\Models\TravelAssignment;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTravelAssignment extends ViewRecord
{
    protected static string $resource = TravelAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Unduh PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (TravelAssignment $record) => auth()->user()->can('downloadPdf', $record))
                ->url(fn (TravelAssignment $record) => route('travel-assignments.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }
}
