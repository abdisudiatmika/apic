<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php
            $filters = $this->getFilters();
            $stats = $this->getSummaryStats();
        @endphp

        {{-- Kartu ringkasan bento --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $cards = [
                    [
                        'label' => 'Total Keterlambatan',
                        'value' => number_format($stats->total_late_hours, 1) . ' jam',
                        'delta' => $stats->total_late_hours_delta,
                        'goodWhenUp' => false,
                        'icon' => 'heroicon-o-clock',
                    ],
                    [
                        'label' => 'Kehadiran Rata-rata',
                        'value' => number_format($stats->avg_attendance_pct, 1) . '%',
                        'delta' => $stats->avg_attendance_pct_delta,
                        'goodWhenUp' => true,
                        'icon' => 'heroicon-o-chart-bar',
                    ],
                    [
                        'label' => 'Kehadiran Sempurna',
                        'value' => $stats->perfect_attendance_count . ' pegawai',
                        'delta' => $stats->perfect_attendance_count_delta,
                        'goodWhenUp' => true,
                        'icon' => 'heroicon-o-check-badge',
                    ],
                    [
                        'label' => 'Rata-rata Jam Kerja',
                        'value' => number_format($stats->avg_work_hours, 1) . ' jam',
                        'delta' => $stats->avg_work_hours_delta,
                        'goodWhenUp' => true,
                        'icon' => 'heroicon-o-briefcase',
                    ],
                ];
            @endphp
            @foreach ($cards as $card)
                @php
                    $isUp = $card['delta'] > 0;
                    $isDown = $card['delta'] < 0;
                    $isGood = $isUp === $card['goodWhenUp'] ? $isUp || $isDown : false;
                    $deltaColor = $card['delta'] == 0
                        ? 'text-gray-400 dark:text-gray-500'
                        : (($isUp && $card['goodWhenUp']) || ($isDown && ! $card['goodWhenUp'])
                            ? 'text-success-600 dark:text-success-400'
                            : 'text-danger-600 dark:text-danger-400');
                @endphp
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span>
                        <x-filament::icon :icon="$card['icon']" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    </div>
                    <div class="mt-2 text-2xl font-semibold tabular-nums text-gray-950 dark:text-white">{{ $card['value'] }}</div>
                    <div class="mt-1 flex items-center gap-1 text-xs {{ $deltaColor }}">
                        @if ($card['delta'] != 0)
                            <x-filament::icon :icon="$isUp ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'" class="h-3.5 w-3.5" />
                        @endif
                        <span>{{ $card['delta'] == 0 ? 'Sama seperti periode lalu' : (($isUp ? '+' : '') . number_format($card['delta'], 1) . ' dari periode lalu') }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Grafik keterlambatan per departemen --}}
        <x-filament::section>
            <x-slot name="heading">Analisis Keterlambatan per Departemen</x-slot>

            @php
                $lateness = $this->getDepartmentLateness();
            @endphp
            @if ($lateness->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada data keterlambatan pada rentang &amp; filter ini.</p>
            @else
                @php
                    $max = max(1, $lateness->max('hours'));
                @endphp
                <div class="space-y-3">
                    @foreach ($lateness as $item)
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $item->department }}</span>
                                <span class="tabular-nums text-gray-500 dark:text-gray-400">{{ number_format($item->hours, 1) }} jam</span>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                <div class="h-full rounded-full bg-primary-500" style="width: {{ max(2, round($item->hours / $max * 100)) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Tabel gabungan performa pegawai --}}
        @php
            $rows = $this->getEmployeeRows();
            $total = $this->getEmployeeRowsTotal();
            $perPage = $this->getPerPage();
            $from = $total === 0 ? 0 : (($this->page - 1) * $perPage) + 1;
            $to = min($total, $this->page * $perPage);
        @endphp
        <x-filament::section>
            <x-slot name="heading">Laporan Performa Karyawan</x-slot>
            <x-slot name="description">{{ $total }} pegawai</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.employee-performance.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.employee-performance.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="px-3 py-2.5 font-semibold">Pegawai</th>
                            <th class="px-3 py-2.5 font-semibold">Departemen</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Hadir</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Terlambat</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Rata-rata Jam Kerja</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Cuti</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Sisa Cuti</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($rows as $row)
                            @php
                                $initials = collect(explode(' ', trim($row->employee->name)))
                                    ->filter()
                                    ->map(fn ($segment) => mb_substr($segment, 0, 1))
                                    ->take(2)
                                    ->join('');
                                $initials = mb_strtoupper($initials);
                            @endphp
                            <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-primary-50 dark:odd:bg-transparent dark:even:bg-white/[0.03] dark:hover:bg-white/10">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-600 text-xs font-semibold text-white">{{ $initials }}</div>
                                        <span class="font-medium">{{ $row->employee->name }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $row->employee->department?->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->hadir }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($row->terlambat > 0)
                                        <x-filament::badge color="danger">{{ $row->terlambat }}</x-filament::badge>
                                    @else
                                        <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row->avg_work_hours, 1) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ number_format($row->total_cuti_days, 1) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    <x-filament::badge :color="$row->sisa_cuti < 0 ? 'danger' : 'success'">
                                        {{ number_format($row->sisa_cuti, 1) }}
                                    </x-filament::badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada rentang &amp; filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>Menampilkan {{ $from }}-{{ $to }} dari {{ $total }} pegawai</span>
                <div class="flex gap-2">
                    <x-filament::button wire:click="previousPage" color="gray" size="sm" icon="heroicon-o-chevron-left" :disabled="$this->page <= 1">Sebelumnya</x-filament::button>
                    <x-filament::button wire:click="nextPage" color="gray" size="sm" icon-position="after" icon="heroicon-o-chevron-right" :disabled="$to >= $total">Berikutnya</x-filament::button>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
