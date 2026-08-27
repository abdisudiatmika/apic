<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('contract-reminders:send')]
#[Description('Notify HR about employee contracts ending within 30 days (PRD 5.1 / 11)')]
class SendContractReminders extends Command
{
    public function handle(): int
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now()->toDateString(), Carbon::now()->addDays(30)->toDateString()])
            ->get();

        if ($employees->isEmpty()) {
            $this->info('No contracts ending within 30 days.');

            return self::SUCCESS;
        }

        $hrUsers = User::role(['hr', 'administrator'])->get();

        if ($hrUsers->isEmpty()) {
            $this->warn('No HR/Administrator users to notify.');

            return self::SUCCESS;
        }

        foreach ($employees as $employee) {
            FilamentNotification::make()
                ->title('Kontrak segera berakhir')
                ->body("Kontrak {$employee->name} ({$employee->nip}) berakhir {$employee->contract_end_date->translatedFormat('d F Y')}.")
                ->warning()
                ->sendToDatabase($hrUsers);
        }

        $this->info("Sent reminders for {$employees->count()} employee(s).");

        return self::SUCCESS;
    }
}
