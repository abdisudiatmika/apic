<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('attendance-reminders:send')]
#[Description('Notify employees scheduled yesterday who have no attendance log yet (PRD 11)')]
class SendAttendanceReminders extends Command
{
    public function handle(): int
    {
        $yesterday = Carbon::yesterday();

        $scheduledEmployeeIds = Employee::query()
            ->whereHas('schedules', fn ($q) => $q->where('date', $yesterday->toDateString()))
            ->pluck('id');

        $loggedEmployeeIds = AttendanceLog::query()
            ->where('date', $yesterday->toDateString())
            ->pluck('employee_id');

        $missing = Employee::query()
            ->whereIn('id', $scheduledEmployeeIds->diff($loggedEmployeeIds))
            ->with('user')
            ->get();

        $notified = 0;

        foreach ($missing as $employee) {
            if (! $employee->user) {
                continue;
            }

            FilamentNotification::make()
                ->title('Absensi belum tercatat')
                ->body("Tidak ada data absensi Anda tanggal {$yesterday->translatedFormat('d F Y')}. Ajukan Koreksi Absensi bila Anda sebenarnya hadir.")
                ->warning()
                ->sendToDatabase($employee->user);

            $notified++;
        }

        $this->info("Notified {$notified} employee(s) with missing attendance for {$yesterday->toDateString()}.");

        return self::SUCCESS;
    }
}
