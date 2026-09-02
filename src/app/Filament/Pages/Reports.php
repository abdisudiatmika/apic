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
 * PRD 12 — "Laporan & Analitik HR". Fase 7: satu tabel gabungan per pegawai
 * (bukan lagi 4 bagian terpisah), dengan kartu ringkasan & grafik keterlambatan
 * per departemen di atasnya. Masih pola computed-rows-dari-Blade seperti
 * TeamLeaveCalendar, bukan Filament Table framework.
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

    public int $page = 1;

    protected int $perPage = 10;

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
     * Filter/pencarian berubah → kembali ke halaman 1, supaya paginasi tidak
     * "nyangkut" di halaman kosong ketika hasil yang cocok jadi lebih sedikit.
     */
    public function updated(string $property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->cachedFilters = null;
            $this->page = 1;
        }
    }

    /**
     * @return array{start_date: string, end_date: string, department_id: ?int, branch_id: ?int}
     */
    public function getFilters(): array
    {
        return $this->cachedFilters ??= $this->form->getState();
    }

    public function getSummaryStats(): object
    {
        return app(ReportService::class)->summaryStats($this->getFilters());
    }

    public function getDepartmentLateness(): Collection
    {
        return app(ReportService::class)->departmentLateness($this->getFilters());
    }

    private function allEmployeeRows(): Collection
    {
        return $this->applySearch(app(ReportService::class)->employeePerformance($this->getFilters()));
    }

    public function getEmployeeRowsTotal(): int
    {
        return $this->allEmployeeRows()->count();
    }

    public function getEmployeeRows(): Collection
    {
        return $this->allEmployeeRows()->forPage($this->page, $this->perPage)->values();
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function nextPage(): void
    {
        $lastPage = max(1, (int) ceil($this->getEmployeeRowsTotal() / $this->perPage));
        $this->page = min($lastPage, $this->page + 1);
    }

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
