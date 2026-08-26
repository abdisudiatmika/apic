<?php

namespace App\Filament\Portal\Widgets;

use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveBalanceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return [];
        }

        $service = app(LeaveBalanceService::class);
        $year = now()->year;

        return LeaveType::where('is_active', true)
            ->get()
            ->map(function (LeaveType $leaveType) use ($service, $employee, $year) {
                $summary = $service->summary($employee, $leaveType, $year);

                return Stat::make($leaveType->name, number_format($summary->available, 1).' hari')
                    ->description(sprintf(
                        'Hak %s - terpakai %s - pending %s%s',
                        number_format($summary->entitled, 1),
                        number_format($summary->used, 1),
                        number_format($summary->pending, 1),
                        $summary->bonOutstanding > 0 ? ' - bon '.number_format($summary->bonOutstanding, 1) : ''
                    ))
                    ->color($summary->available < 0 ? 'danger' : 'success');
            })
            ->all();
    }
}
