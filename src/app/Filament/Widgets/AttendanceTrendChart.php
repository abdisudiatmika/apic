<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

/**
 * PRD 5.1 listed "Grafik tren kehadiran" as a Dashboard HR output, but Fase 1 only
 * ever built stat cards (AttendanceOverview) — no chart. This closes that gap as
 * part of PRD 12's "Analitik". Deliberately not synced to the Reports page's own
 * department/branch/date filters — Filament's page-filter-sharing (HasFiltersForm)
 * is specific to the Dashboard page class, not a generic Page — so this widget has
 * its own simple period selector instead, independent and self-contained.
 */
class AttendanceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Kehadiran';

    public ?string $filter = '14';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari terakhir',
            '14' => '14 hari terakhir',
            '30' => '30 hari terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?? 14);
        $start = Carbon::today()->subDays($days - 1);
        $today = Carbon::today();

        $logsByDate = AttendanceLog::query()
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->get()
            ->groupBy(fn (AttendanceLog $log) => $log->date->toDateString());

        $labels = [];
        $hadir = [];
        $terlambat = [];
        $tidakHadir = [];

        $cursor = $start->copy();

        while ($cursor->lte($today)) {
            $dayLogs = $logsByDate->get($cursor->toDateString(), collect());

            $labels[] = $cursor->translatedFormat('d M');
            $hadir[] = $dayLogs->whereIn('status', ['hadir', 'terlambat'])->count();
            $terlambat[] = $dayLogs->where('status', 'terlambat')->count();
            $tidakHadir[] = $dayLogs->where('status', 'tidak_hadir')->count();

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $hadir,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => '#22c55e33',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $terlambat,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b33',
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => $tidakHadir,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef444433',
                ],
            ],
        ];
    }
}
