<?php

namespace App\Filament\Portal\Resources\LeaveRequests\Pages;

use App\Filament\Portal\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Carbon;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    /**
     * Computes derived fields (employee_id, days, status) and enforces PRD 5.5's
     * "Sistem cek saldo & jadwal" here — before any database write — rather than
     * relying on the form alone, so a request can never be created past validation.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $employee = auth()->user()->employee;
        $leaveType = LeaveType::findOrFail($data['leave_type_id']);
        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $service = app(LeaveBalanceService::class);

        if ($blackout = $service->blackoutFor($employee, $start, $end)) {
            Notification::make()
                ->title('Tanggal tidak dapat diajukan')
                ->body("Tanggal {$blackout->date->translatedFormat('d F Y')} dibatasi untuk cuti: {$blackout->reason}")
                ->danger()
                ->send();

            throw new Halt;
        }

        if ($service->hasOverlap($employee, $start, $end)) {
            Notification::make()
                ->title('Tanggal bertabrakan')
                ->body('Anda sudah punya pengajuan cuti lain yang tumpang tindih dengan rentang tanggal ini.')
                ->danger()
                ->send();

            throw new Halt;
        }

        $days = $service->workingDaysBetween($start, $end);
        $summary = $service->summary($employee, $leaveType, $start->year);

        if ($days > $summary->available) {
            Notification::make()
                ->title('Saldo cuti tidak cukup')
                ->body(sprintf('Sisa cuti tersedia: %s hari, pengajuan ini butuh %s hari.', $summary->available, $days))
                ->danger()
                ->send();

            throw new Halt;
        }

        $data['employee_id'] = $employee->id;
        $data['days'] = $days;
        $data['status'] = 'menunggu_atasan';

        return $data;
    }
}
