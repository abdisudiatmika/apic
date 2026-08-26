<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Department;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * PRD 5.8. Deliberately a plain computed table, not a visual month-grid widget —
 * avoids pulling in a calendar JS package for what HR/Manager actually asked for:
 * "siapa yang tidak tersedia" per tanggal, with a headcount warning.
 */
class TeamLeaveCalendar extends Page
{
    protected string $view = 'filament.pages.team-leave-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Cuti';

    protected static string|UnitEnum|null $navigationGroup = 'Cuti';

    protected static ?int $navigationSort = 4;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['administrator', 'hr', 'direksi']) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfWeek()->toDateString(),
            'until' => now()->endOfWeek()->toDateString(),
            'department_id' => null,
            'branch_id' => null,
            'threshold' => 3,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('from')->label('Dari Tanggal')->native(false)->required()->live(),
                DatePicker::make('until')->label('Sampai Tanggal')->native(false)->required()->live(),
                Select::make('department_id')
                    ->label('Departemen')
                    ->options(fn () => Department::pluck('name', 'id'))
                    ->live(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(fn () => Branch::pluck('name', 'id'))
                    ->live(),
                TextInput::make('threshold')
                    ->label('Ambang Batas Peringatan')
                    ->helperText('Tandai tanggal jika jumlah pegawai cuti bersamaan mencapai angka ini.')
                    ->numeric()
                    ->default(3)
                    ->live(),
            ])
            ->statePath('data');
    }

    /**
     * @return Collection<int, array{date: Carbon, count: int, names: string, over_threshold: bool}>
     */
    public function getDailyRows(): Collection
    {
        $state = $this->form->getState();

        if (empty($state['from']) || empty($state['until'])) {
            return collect();
        }

        $from = Carbon::parse($state['from']);
        $until = Carbon::parse($state['until']);
        $threshold = (int) ($state['threshold'] ?? 3);

        $requests = LeaveRequest::query()
            ->with('employee')
            ->whereIn('status', ['menunggu_atasan', 'menunggu_hr', 'disetujui'])
            ->where('start_date', '<=', $until)
            ->where('end_date', '>=', $from)
            ->when($state['department_id'] ?? null, fn ($q, $deptId) => $q->whereRelation('employee', 'department_id', $deptId))
            ->when($state['branch_id'] ?? null, fn ($q, $branchId) => $q->whereRelation('employee', 'branch_id', $branchId))
            ->get();

        $rows = collect();
        $cursor = $from->copy();

        while ($cursor->lte($until)) {
            $onLeave = $requests->filter(
                fn (LeaveRequest $r) => $cursor->betweenIncluded($r->start_date, $r->end_date)
            );

            $rows->push([
                'date' => $cursor->copy(),
                'count' => $onLeave->count(),
                'names' => $onLeave->pluck('employee.name')->implode(', '),
                'over_threshold' => $onLeave->count() >= $threshold,
            ]);

            $cursor->addDay();
        }

        return $rows;
    }
}
