<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PRD 12 — "Laporan & Analitik HR": satu tabel gabungan per pegawai (kehadiran,
 * keterlambatan, jam kerja, cuti) plus ringkasan & grafik untuk halaman
 * App\Filament\Pages\Reports dan ReportExportController, supaya angka yang
 * diunduh selalu cocok dengan yang tampil di layar untuk filter yang sama.
 * Fase 7 menggantikan 4 method terpisah (attendance/leave/leaveAdvance/travel)
 * dengan satu method employeePerformance() sesuai permintaan pengguna untuk
 * menggabungkan laporan jadi satu tabel.
 */
class ReportService
{
    /**
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object>
     */
    public function employeePerformance(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);
        $year = now()->year;
        $leaveTypes = LeaveType::where('is_active', true)->get();
        $balanceService = app(LeaveBalanceService::class);

        return $this->scopedEmployees($filters)
            ->with([
                'department',
                'attendanceLogs' => fn ($q) => $q->whereBetween('date', [$start->toDateString(), $end->toDateString()]),
                'leaveRequests' => fn ($q) => $q->where('status', 'disetujui')->whereYear('start_date', $year),
            ])
            ->get()
            ->map(function (Employee $employee) use ($leaveTypes, $balanceService, $year) {
                $logs = $employee->attendanceLogs;
                $withHours = $logs->filter(fn ($log) => $log->check_in && $log->check_out);

                // Sisa Cuti dihitung untuk tahun berjalan lintas semua jenis cuti aktif,
                // bukan mengikuti filter rentang tanggal halaman — "sisa saldo" adalah
                // status berjalan tahunan, bukan sesuatu yang bisa dipotong per rentang
                // tanggal sembarang. Bisa negatif kalau Bon Cuti melebihi hak (LeaveBalanceService).
                $sisaCuti = $leaveTypes->sum(
                    fn (LeaveType $type) => $balanceService->summary($employee, $type, $year)->available
                );

                return (object) [
                    'employee' => $employee,
                    'hadir' => $logs->whereIn('status', ['hadir', 'terlambat'])->count(),
                    'terlambat' => $logs->where('status', 'terlambat')->count(),
                    'tidak_hadir' => $logs->where('status', 'tidak_hadir')->count(),
                    'avg_work_hours' => $withHours->isNotEmpty()
                        ? round($withHours->avg(
                            fn ($log) => Carbon::parse($log->check_out)->diffInMinutes(Carbon::parse($log->check_in), true) / 60
                        ), 1)
                        : 0.0,
                    'total_cuti_days' => (float) $employee->leaveRequests->sum('days'),
                    'sisa_cuti' => $sisaCuti,
                ];
            })
            ->sortBy(fn ($row) => $row->employee->name)
            ->values();
    }

    /**
     * Kartu ringkasan bento di puncak halaman, dengan delta terhadap periode
     * sebelumnya (rentang sepanjang periode aktif, langsung sebelum start_date) —
     * pendekatan approksimasi untuk kesan tren, bukan perbandingan kalender presisi.
     *
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     */
    public function summaryStats(array $filters): object
    {
        [$start, $end] = $this->range($filters);
        $current = $this->computeStats($filters, $start, $end);

        $periodDays = $start->diffInDays($end) + 1;
        $previousEnd = (clone $start)->subDay();
        $previousStart = (clone $previousEnd)->subDays($periodDays - 1);
        $previous = $this->computeStats($filters, $previousStart, $previousEnd);

        return (object) [
            'total_late_hours' => $current->total_late_hours,
            'total_late_hours_delta' => $current->total_late_hours - $previous->total_late_hours,
            'avg_attendance_pct' => $current->avg_attendance_pct,
            'avg_attendance_pct_delta' => $current->avg_attendance_pct - $previous->avg_attendance_pct,
            'perfect_attendance_count' => $current->perfect_attendance_count,
            'perfect_attendance_count_delta' => $current->perfect_attendance_count - $previous->perfect_attendance_count,
            'avg_work_hours' => $current->avg_work_hours,
            'avg_work_hours_delta' => $current->avg_work_hours - $previous->avg_work_hours,
        ];
    }

    /**
     * @param  array{department_id: ?int, branch_id: ?int}  $filters
     */
    private function computeStats(array $filters, Carbon $start, Carbon $end): object
    {
        $employees = $this->scopedEmployees($filters)
            ->with(['attendanceLogs' => fn ($q) => $q->whereBetween('date', [$start->toDateString(), $end->toDateString()])])
            ->get();

        $totalLateMinutes = 0;
        $totalHadirTerlambat = 0;
        $totalTidakHadir = 0;
        $perfectCount = 0;
        $workHourSamples = collect();

        foreach ($employees as $employee) {
            $logs = $employee->attendanceLogs;
            $hadir = $logs->whereIn('status', ['hadir', 'terlambat'])->count();
            $terlambat = $logs->where('status', 'terlambat')->count();
            $tidakHadir = $logs->where('status', 'tidak_hadir')->count();

            $totalLateMinutes += (int) $logs->where('status', 'terlambat')->sum('late_minutes');
            $totalHadirTerlambat += $hadir;
            $totalTidakHadir += $tidakHadir;

            if ($hadir > 0 && $terlambat === 0 && $tidakHadir === 0) {
                $perfectCount++;
            }

            foreach ($logs->filter(fn ($log) => $log->check_in && $log->check_out) as $log) {
                $workHourSamples->push(
                    Carbon::parse($log->check_out)->diffInMinutes(Carbon::parse($log->check_in), true) / 60
                );
            }
        }

        $attendanceDenominator = $totalHadirTerlambat + $totalTidakHadir;

        return (object) [
            'total_late_hours' => round($totalLateMinutes / 60, 1),
            'avg_attendance_pct' => $attendanceDenominator > 0
                ? round($totalHadirTerlambat / $attendanceDenominator * 100, 1)
                : 0.0,
            'perfect_attendance_count' => $perfectCount,
            'avg_work_hours' => $workHourSamples->isNotEmpty() ? round($workHourSamples->avg(), 1) : 0.0,
        ];
    }

    /**
     * Total jam keterlambatan per departemen, untuk bar chart CSS "Analisis
     * Keterlambatan per Departemen" — dirender sebagai <div> proporsional di
     * Blade, tanpa Chart.js/widget tambahan.
     *
     * @param  array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}  $filters
     * @return Collection<int, object{department: string, hours: float}>
     */
    public function departmentLateness(array $filters): Collection
    {
        [$start, $end] = $this->range($filters);

        return $this->scopedEmployees($filters)
            ->with([
                'department',
                'attendanceLogs' => fn ($q) => $q->where('status', 'terlambat')
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()]),
            ])
            ->get()
            ->groupBy(fn (Employee $employee) => $employee->department?->name ?? 'Tanpa Departemen')
            ->map(fn (Collection $employees, string $department) => (object) [
                'department' => $department,
                'hours' => round($employees->sum(fn (Employee $e) => $e->attendanceLogs->sum('late_minutes')) / 60, 1),
            ])
            ->values()
            ->sortByDesc('hours')
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
