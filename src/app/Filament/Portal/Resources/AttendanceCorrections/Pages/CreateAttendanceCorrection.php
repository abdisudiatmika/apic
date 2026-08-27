<?php

namespace App\Filament\Portal\Resources\AttendanceCorrections\Pages;

use App\Filament\Portal\Resources\AttendanceCorrections\AttendanceCorrectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendanceCorrection extends CreateRecord
{
    protected static string $resource = AttendanceCorrectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_id'] = auth()->user()->employee->id;
        $data['status'] = 'menunggu_atasan';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->submit();
    }
}
