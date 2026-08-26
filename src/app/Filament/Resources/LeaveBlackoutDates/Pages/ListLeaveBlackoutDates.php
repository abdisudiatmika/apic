<?php

namespace App\Filament\Resources\LeaveBlackoutDates\Pages;

use App\Filament\Resources\LeaveBlackoutDates\LeaveBlackoutDateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLeaveBlackoutDates extends ListRecords
{
    protected static string $resource = LeaveBlackoutDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
