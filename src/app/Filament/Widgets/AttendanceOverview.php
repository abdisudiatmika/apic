<?php

namespace App\Filament\Widgets;

use App\Models\AttendanceLog;
use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class AttendanceOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();

        $activeEmployees = Employee::where('is_active', true)->count();

        $todayLogs = AttendanceLog::whereDate('date', $today)->get();

        $hadir = $todayLogs->whereIn('status', ['hadir', 'terlambat'])->count();
        $terlambat = $todayLogs->where('status', 'terlambat')->count();
        $tidakHadir = $todayLogs->where('status', 'tidak_hadir')->count();
        $belumAdaData = $activeEmployees - $todayLogs->count();

        return [
            Stat::make('Pegawai Aktif', $activeEmployees)
                ->description('Total pegawai berstatus aktif')
                ->color('gray'),

            Stat::make('Hadir Hari Ini', $hadir)
                ->description($today->translatedFormat('d F Y'))
                ->color('success'),

            Stat::make('Terlambat Hari Ini', $terlambat)
                ->color($terlambat > 0 ? 'warning' : 'gray'),

            Stat::make('Tidak Hadir Hari Ini', $tidakHadir)
                ->description($belumAdaData > 0 ? "{$belumAdaData} belum ada data absensi" : null)
                ->color($tidakHadir > 0 ? 'danger' : 'gray'),
        ];
    }
}
