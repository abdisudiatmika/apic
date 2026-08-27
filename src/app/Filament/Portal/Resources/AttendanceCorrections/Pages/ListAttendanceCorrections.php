<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections\Pages;

use App\Filament\Portal\Resources\AttendanceCorrections\AttendanceCorrectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceCorrections extends ListRecords
{
    protected static string $resource = AttendanceCorrectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
