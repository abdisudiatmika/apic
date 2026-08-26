<?php

namespace App\Observers;

use App\Models\LeaveBalance;
use App\Services\LeaveBalanceService;

/**
 * Wires PRD 5.7's auto-deduction rule into the moment a leave balance actually
 * changes, so HR never has to remember to trigger it manually.
 */
class LeaveBalanceObserver
{
    public function __construct(private readonly LeaveBalanceService $leaveBalanceService) {}

    public function created(LeaveBalance $leaveBalance): void
    {
        $capacity = (float) $leaveBalance->entitled_days + (float) $leaveBalance->carry_forward_days;

        $this->leaveBalanceService->deductOutstandingAdvances($leaveBalance, $capacity);
    }

    public function updated(LeaveBalance $leaveBalance): void
    {
        $before = (float) $leaveBalance->getOriginal('entitled_days') + (float) $leaveBalance->getOriginal('carry_forward_days');
        $after = (float) $leaveBalance->entitled_days + (float) $leaveBalance->carry_forward_days;

        $increase = $after - $before;

        if ($increase > 0) {
            $this->leaveBalanceService->deductOutstandingAdvances($leaveBalance, $increase);
        }
    }
}
