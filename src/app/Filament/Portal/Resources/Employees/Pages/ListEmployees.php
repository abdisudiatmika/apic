<?php

namespace App\Filament\Portal\Resources\Employees\Pages;

use App\Filament\Portal\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
}
