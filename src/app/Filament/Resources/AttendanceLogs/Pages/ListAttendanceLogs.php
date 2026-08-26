<?php

namespace App\Filament\Resources\AttendanceLogs\Pages;

use App\Filament\Resources\AttendanceLogs\AttendanceLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceLogs extends ListRecords
{
    protected static string $resource = AttendanceLogResource::class;
}
