<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PRD 12 — "Laporan & Analitik HR": satu tempat untuk keempat ringkasan yang
 * diminta (kehadiran+keterlambatan, cuti, bon cuti, perjalanan dinas), dipakai baik
 * oleh App\Filament\Pages\Reports (tampilan layar) maupun ReportExportController
 * (Excel/PDF) supaya angka yang diunduh selalu cocok dengan yang tampil di layar
 * untuk filter yang sama. Pola sama seperti LeaveBalanceService yang sudah ada:
 * Eloquent biasa dengan eager-load berconstraint tanggal, bukan raw SQL aggregate.
 */
class ReportService
{
    /**
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object>
     */
    public function attendanceSummary(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);

        return $this->scopedEmployees($filters)
            ->with(['attendanceLogs' => fn ($q) => $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])])
            ->get()
            ->map(function (Employee $employee) {
                $logs = $employee->attendanceLogs;
                $late = $logs->where('status', 'terlambat');

                return (object) [
                    'employee' => $employee,
                    'hadir' => $logs->whereIn('status', ['hadir', 'terlambat'])->count(),
                    'terlambat' => $late->count(),
                    'tidak_hadir' => $logs->where('status', 'tidak_hadir')->count(),
                    'dinas' => $logs->where('status', 'dinas')->count(),
                    'total_late_minutes' => (int) $late->sum('late_minutes'),
                    'avg_late_minutes' => $late->isNotEmpty() ? round($late->avg('late_minutes'), 1) : 0.0,
                ];
            })
            ->sortBy(fn ($row) => $row->employee->name)
            ->values();
    }

    /**
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object>
     */
    public function leaveSummary(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);

        return $this->scopedEmployees($filters)
            ->with(['leaveRequests' => fn ($q) => $q->where('start_date', '<=', $end)->where('end_date', '>=', $start)])
            ->get()
            ->map(function (Employee $employee) {
                $requests = $employee->leaveRequests;

                return (object) [
                    'employee' => $employee,
                    'total_pengajuan' => $requests->count(),
                    'hari_disetujui' => (float) $requests->where('status', 'disetujui')->sum('days'),
                    'hari_pending' => (float) $requests->whereIn('status', ['menunggu_atasan', 'menunggu_hr'])->sum('days'),
                    'ditolak' => $requests->where('status', 'ditolak')->count(),
                ];
            })
            ->sortBy(fn ($row) => $row->employee->name)
            ->values();
    }

    /**
     * Bon Cuti tidak punya rentang tanggal sendiri (bukan seperti Cuti/Surat
     * Tugas) — pengajuan disaring berdasarkan tanggal diajukan (created_at).
     *
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object>
     */
    public function leaveAdvanceSummary(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);

        return $this->scopedEmployees($filters)
            ->with(['leaveAdvances' => fn ($q) => $q->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()])])
            ->get()
            ->map(function (Employee $employee) {
                $advances = $employee->leaveAdvances;
                $approved = $advances->where('status', 'disetujui');

                return (object) [
                    'employee' => $employee,
                    'total_pengajuan' => $advances->count(),
                    'total_hari' => (float) $approved->sum('days'),
                    'outstanding' => (float) $approved->sum('outstanding_days'),
                    'lunas' => $approved->whereNotNull('settled_at')->count(),
                ];
            })
            ->sortBy(fn ($row) => $row->employee->name)
            ->values();
    }

    /**
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object>
     */
    public function travelSummary(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);

        return $this->scopedEmployees($filters)
            ->with(['travelAssignments' => fn ($q) => $q->where('status', 'disetujui')
                ->where('start_date', '<=', $end)
                ->where('end_date', '>=', $start)])
            ->get()
            ->map(function (Employee $employee) {
                $assignments = $employee->travelAssignments;

                return (object) [
                    'employee' => $employee,
                    'surat_tugas' => $assignments->where('type', 'surat_tugas')->count(),
                    'perjalanan_dinas' => $assignments->where('type', 'perjalanan_dinas')->count(),
                    'surat_jalan' => $assignments->where('type', 'surat_jalan')->count(),
                    'total_hari' => (int) $assignments->sum(fn ($a) => $a->start_date->diffInDays($a->end_date) + 1),
                ];
            })
            ->sortBy(fn ($row) => $row->employee->name)
            ->values();
    }

    /**
     * @param  array{department_id: ?int, branch_id: ?int}  $filters
     */
    private function scopedEmployees(array $filters): Builder
    {
        return Employee::query()
            ->where('is_active', true)
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->when($filters['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id));
    }

    /**
     * @param  array{start_date: string, end_date: string}  $filters
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(array $filters): array
    {
        return [Carbon::parse($filters['start_date']), Carbon::parse($filters['end_date'])];
    }
}
