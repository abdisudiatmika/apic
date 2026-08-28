<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceTrendChart;
use App\Models\Branch;
use App\Models\Department;
use App\Services\ReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * PRD 12 — "Laporan & Analitik HR". One page bundling all four requested summaries
 * behind one shared filter bar, matching TeamLeaveCalendar's pattern (plain
 * computed rows rendered straight from Blade, no Filament Table framework) rather
 * than four separate CRUD-style resources.
 */
class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Laporan & Analitik';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 1;

    /** @var array<string, mixed> */
    public ?array $data = [];

    private ?array $cachedFilters = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['administrator', 'hr', 'direksi']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
            'department_id' => null,
            'branch_id' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('start_date')->label('Dari Tanggal')->native(false)->required()->live(),
                DatePicker::make('end_date')->label('Sampai Tanggal')->native(false)->required()->live(),
                Select::make('department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::pluck('name', 'id'))
                    ->live(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(fn () => Branch::pluck('name', 'id'))
                    ->live(),
            ])
            ->statePath('data');
    }

    /**
     * @return array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}
     */
    public function getFilters(): array
    {
        return $this->cachedFilters ??= $this->form->getState();
    }

    public function getAttendanceRows(): Collection
    {
        return app(ReportService::class)->attendanceSummary($this->getFilters());
    }

    public function getLeaveRows(): Collection
    {
        return app(ReportService::class)->leaveSummary($this->getFilters());
    }

    public function getLeaveAdvanceRows(): Collection
    {
        return app(ReportService::class)->leaveAdvanceSummary($this->getFilters());
    }

    public function getTravelRows(): Collection
    {
        return app(ReportService::class)->travelSummary($this->getFilters());
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceTrendChart::class,
        ];
    }
}
