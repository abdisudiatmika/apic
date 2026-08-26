<?php

namespace App\Filament\Resources\LeaveBlackoutDates\Pages;

use App\Filament\Resources\LeaveBlackoutDates\LeaveBlackoutDateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLeaveBlackoutDate extends EditRecord
{
    protected static string $resource = LeaveBlackoutDateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
