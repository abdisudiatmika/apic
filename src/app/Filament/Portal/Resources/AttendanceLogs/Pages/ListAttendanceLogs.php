<?php

namespace App\Filament\Portal\Resources\AttendanceLogs\Pages;

use App\Filament\Portal\Resources\AttendanceLogs\AttendanceLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceLogs extends ListRecords
{
    protected static string $resource = AttendanceLogResource::class;
}
