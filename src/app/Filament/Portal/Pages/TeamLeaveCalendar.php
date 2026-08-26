<?php

namespace App\Filament\Portal\Pages;

use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Scoped to the logged-in atasan's own team — see PRD 5.8 "Pengguna: Manager dan
 * HR". A pegawai account never sees this page (canAccess()).
 */
class TeamLeaveCalendar extends Page
{
    protected string $view = 'filament.portal.pages.team-leave-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Kalender Tim';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('atasan') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from' => now()->startOfWeek()->toDateString(),
            'until' => now()->endOfWeek()->toDateString(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('from')->label('Dari Tanggal')->native(false)->required()->live(),
                DatePicker::make('until')->label('Sampai Tanggal')->native(false)->required()->live(),
            ])
            ->statePath('data');
    }

    /**
     * @return Collection<int, array{date: Carbon, count: int, names: string}>
     */
    public function getDailyRows(): Collection
    {
        $state = $this->form->getState();

        if (empty($state['from']) || empty($state['until'])) {
            return collect();
        }

        $employee = auth()->user()->employee;
        $from = Carbon::parse($state['from']);
        $until = Carbon::parse($state['until']);

        $requests = LeaveRequest::query()
            ->with('employee')
            ->whereIn('status', ['menunggu_atasan', 'menunggu_hr', 'disetujui'])
            ->where('start_date', '<=', $until)
            ->where('end_date', '>=', $from)
            ->whereHas('employee', fn ($q) => $q->where('supervisor_id', $employee?->id)->orWhere('id', $employee?->id))
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
            ]);

            $cursor->addDay();
        }

        return $rows;
    }
}
