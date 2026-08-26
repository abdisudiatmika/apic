<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveAdvance;
use App\Models\LeaveBalance;
use App\Models\LeaveBlackoutDate;
use App\Models\LeaveType;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Single source of truth for leave-balance math (PRD 5.6) and the Bon Cuti
 * auto-deduction rule (PRD 5.7: "Saat hak cuti muncul, sistem otomatis melakukan
 * pemotongan"). Used by both the submission validation and the Sisa Cuti displays,
 * so the number a pegawai sees before submitting always matches what gets enforced.
 */
class LeaveBalanceService
{
    public function summary(Employee $employee, LeaveType $leaveType, int $year): LeaveBalanceSummary
    {
        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        $entitledBase = $balance
            ? (float) $balance->entitled_days + (float) $balance->carry_forward_days
            : 0.0;

        $adjustments = $balance
            ? (float) $balance->adjustments()->sum('amount')
            : 0.0;

        $entitled = $entitledBase + $adjustments;

        $used = (float) $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'disetujui')
            ->whereYear('start_date', $year)
            ->sum('days');

        $pending = (float) $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->whereIn('status', ['menunggu_atasan', 'menunggu_hr'])
            ->whereYear('start_date', $year)
            ->sum('days');

        $bonOutstanding = (float) $employee->leaveAdvances()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'disetujui')
            ->sum('outstanding_days');

        $available = $entitled - $used - $pending - $bonOutstanding;

        return new LeaveBalanceSummary(
            entitled: $entitled,
            used: $used,
            pending: $pending,
            bonOutstanding: $bonOutstanding,
            available: $available,
        );
    }

    /**
     * Called from LeaveBalanceObserver whenever a balance's entitled/carry-forward
     * capacity goes up. Settles the oldest outstanding Bon Cuti first, writing one
     * leave_balance_adjustments row per advance it touches so HR can see exactly
     * when and how much was deducted — not just a silently-lower live number.
     */
    public function deductOutstandingAdvances(LeaveBalance $balance, float $newCapacity): void
    {
        if ($newCapacity <= 0) {
            return;
        }

        $remainingCapacity = $newCapacity;

        $advances = LeaveAdvance::query()
            ->where('employee_id', $balance->employee_id)
            ->where('leave_type_id', $balance->leave_type_id)
            ->where('status', 'disetujui')
            ->where('outstanding_days', '>', 0)
            ->orderBy('hr_at')
            ->get();

        foreach ($advances as $advance) {
            if ($remainingCapacity <= 0) {
                break;
            }

            $deduction = min($advance->outstanding_days, $remainingCapacity);

            $balance->adjustments()->create([
                'amount' => -$deduction,
                'reason' => "Potongan otomatis Bon Cuti #{$advance->id}",
            ]);

            $advance->update([
                'outstanding_days' => $advance->outstanding_days - $deduction,
                'settled_at' => ($advance->outstanding_days - $deduction) <= 0 ? now() : null,
            ]);

            $remainingCapacity -= $deduction;
        }
    }

    /**
     * Hari kerja (Senin-Jumat) inklusif antara $start dan $end. Hari libur
     * nasional/perusahaan belum dikecualikan — kalender hari libur (bagian dari
     * PRD 5.9) belum dibangun, jadi rentang yang melewati tanggal merah akan
     * sedikit overcount untuk saat ini.
     */
    public function workingDaysBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        $days = 0;
        $cursor = Carbon::instance($start)->startOfDay();
        $endDay = Carbon::instance($end)->startOfDay();

        while ($cursor->lte($endDay)) {
            if (! $cursor->isWeekend()) {
                $days++;
            }

            $cursor->addDay();
        }

        return (float) $days;
    }

    public function hasOverlap(Employee $employee, CarbonInterface $start, CarbonInterface $end, ?int $excludeId = null): bool
    {
        return $employee->leaveRequests()
            ->whereNotIn('status', ['ditolak', 'dibatalkan'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }

    public function blackoutFor(Employee $employee, CarbonInterface $start, CarbonInterface $end): ?LeaveBlackoutDate
    {
        return LeaveBlackoutDate::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->whereNull('department_id')->orWhere('department_id', $employee->department_id))
            ->orderBy('date')
            ->first();
    }
}
