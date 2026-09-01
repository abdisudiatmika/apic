<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceTrendChart;
use App\Models\Branch;
use App\Models\Department;
use App\Services\ReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            'search' => null,
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
                TextInput::make('search')
                    ->label('Cari Pegawai')
                    ->placeholder('Ketik nama...')
                    ->prefixIcon('heroicon-o-magnifying-glass')
                    ->live(debounce: 300)
                    ->columnSpanFull(),
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
        return $this->applySearch(app(ReportService::class)->attendanceSummary($this->getFilters()));
    }

    public function getLeaveRows(): Collection
    {
        return $this->applySearch(app(ReportService::class)->leaveSummary($this->getFilters()));
    }

    public function getLeaveAdvanceRows(): Collection
    {
        return $this->applySearch(app(ReportService::class)->leaveAdvanceSummary($this->getFilters()));
    }

    public function getTravelRows(): Collection
    {
        return $this->applySearch(app(ReportService::class)->travelSummary($this->getFilters()));
    }

    /**
     * "Cari Pegawai" applies to all four tables at once — a report page is scanned
     * for one person's numbers across every section, not searched section by
     * section. Filtered here in PHP (not pushed into ReportService) since it's a
     * display-only narrowing of an already-computed summary, not a data query.
     */
    private function applySearch(Collection $rows): Collection
    {
        $search = trim((string) ($this->getFilters()['search'] ?? ''));

        if ($search === '') {
            return $rows;
        }

        return $rows->filter(
            fn ($row) => str_contains(mb_strtolower($row->employee->name), mb_strtolower($search))
        )->values();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AttendanceTrendChart::class,
        ];
    }
}
