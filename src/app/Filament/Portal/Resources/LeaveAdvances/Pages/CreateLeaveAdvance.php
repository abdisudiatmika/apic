<?php

namespace App\Filament\Portal\Resources\LeaveAdvances\Pages;

use App\Filament\Portal\Resources\LeaveAdvances\LeaveAdvanceResource;
use App\Models\LeaveType;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreateLeaveAdvance extends CreateRecord
{
    protected static string $resource = LeaveAdvanceResource::class;

    /**
     * PRD 5.7: "Batas maksimal bon cuti ditentukan perusahaan" — enforced here as
     * the leave type's default_days_per_year, rather than against current balance
     * (bon cuti exists precisely for when balance is insufficient/zero).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employee = auth()->user()->employee;
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);

        if ((float) $data['days'] > $leaveType->default_days_per_year) {
            Notification::make()
                ->title('Melebihi batas maksimal Bon Cuti')
                ->body("Batas maksimal Bon Cuti untuk {$leaveType->name} adalah {$leaveType->default_days_per_year} hari.")
                ->danger()
                ->send();

            throw new Halt;
        }

        $data['employee_id'] = $employee->id;
        $data['status'] = 'menunggu_atasan';

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->submit();
    }
}
