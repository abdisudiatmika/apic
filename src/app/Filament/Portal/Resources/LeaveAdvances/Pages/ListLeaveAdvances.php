<?php

namespace App\Filament\Portal\Resources\LeaveAdvances\Pages;

use App\Filament\Portal\Resources\LeaveAdvances\LeaveAdvanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveAdvances extends ListRecords
{
    protected static string $resource = LeaveAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
