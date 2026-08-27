<?php

namespace App\Filament\Portal\Resources\TravelAssignments\Pages;

use App\Filament\Portal\Resources\TravelAssignments\TravelAssignmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTravelAssignment extends CreateRecord
{
    protected static string $resource = TravelAssignmentResource::class;

    /** @var array<int> */
    private array $employeeIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // employee_ids drives the travel_assignment_employees pivot, not a column
        // on travel_assignments itself — pulled out here, synced in afterCreate().
        $this->employeeIds = $data['employee_ids'] ?? [];
        unset($data['employee_ids']);

        $data['requested_by'] = auth()->user()->employee->id;
        $data['status'] = 'menunggu_atasan';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->employees()->sync($this->employeeIds);
        $this->record->submit();
    }
}
