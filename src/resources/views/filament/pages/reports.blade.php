<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        {{-- Deprecated in favor of schema-based widget rendering, but this project's
             custom pages (see team-leave-calendar.blade.php) all use the plain
             custom-$view style rather than Page::content() schemas — kept consistent
             with that, rather than mixing rendering paradigms for one page. --}}
        <x-filament-widgets::widgets
            :widgets="$this->getVisibleHeaderWidgets()"
            :columns="1"
        />

        @php
            $filters = $this->getFilters();
        @endphp

        @php($rows = $this->getAttendanceRows())
        <x-filament::section>
            <x-slot name="heading">Ringkasan Kehadiran &amp; Keterlambatan</x-slot>
            <x-slot name="description">{{ $rows->count() }} pegawai</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.attendance.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.attendance.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="max-h-[26rem] overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="px-3 py-2.5 font-semibold">Pegawai</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Hadir</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Terlambat</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Tidak Hadir</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Dinas</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Menit</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Rata-rata Menit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($rows as $row)
                            <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-primary-50 dark:odd:bg-transparent dark:even:bg-white/[0.03] dark:hover:bg-white/10">
                                <td class="px-3 py-2 font-medium">{{ $row->employee->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->hadir }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->terlambat }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->tidak_hadir }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->dinas }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->total_late_minutes }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->avg_late_minutes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada rentang &amp; filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @php($rows = $this->getLeaveRows())
        <x-filament::section>
            <x-slot name="heading">Ringkasan Cuti</x-slot>
            <x-slot name="description">{{ $rows->count() }} pegawai</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.leave.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.leave.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="max-h-[26rem] overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="px-3 py-2.5 font-semibold">Pegawai</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Pengajuan</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Hari Disetujui</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Hari Pending</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Ditolak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($rows as $row)
                            <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-primary-50 dark:odd:bg-transparent dark:even:bg-white/[0.03] dark:hover:bg-white/10">
                                <td class="px-3 py-2 font-medium">{{ $row->employee->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->total_pengajuan }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->hari_disetujui }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->hari_pending }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->ditolak }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada rentang &amp; filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @php($rows = $this->getLeaveAdvanceRows())
        <x-filament::section>
            <x-slot name="heading">Ringkasan Bon Cuti</x-slot>
            <x-slot name="description">{{ $rows->count() }} pegawai</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.leave-advance.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.leave-advance.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="max-h-[26rem] overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="px-3 py-2.5 font-semibold">Pegawai</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Pengajuan</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Hari</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Outstanding</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Lunas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($rows as $row)
                            <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-primary-50 dark:odd:bg-transparent dark:even:bg-white/[0.03] dark:hover:bg-white/10">
                                <td class="px-3 py-2 font-medium">{{ $row->employee->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->total_pengajuan }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->total_hari }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->outstanding }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->lunas }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada rentang &amp; filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        @php($rows = $this->getTravelRows())
        <x-filament::section>
            <x-slot name="heading">Ringkasan Perjalanan Dinas</x-slot>
            <x-slot name="description">{{ $rows->count() }} pegawai</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.travel.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.travel.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="max-h-[26rem] overflow-y-auto overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800">
                        <tr class="text-left">
                            <th class="px-3 py-2.5 font-semibold">Pegawai</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Surat Tugas</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Perjalanan Dinas</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Surat Jalan</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Total Hari</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($rows as $row)
                            <tr class="odd:bg-white even:bg-gray-50/70 hover:bg-primary-50 dark:odd:bg-transparent dark:even:bg-white/[0.03] dark:hover:bg-white/10">
                                <td class="px-3 py-2 font-medium">{{ $row->employee->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->surat_tugas }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->perjalanan_dinas }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->surat_jalan }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row->total_hari }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500">Tidak ada data pada rentang &amp; filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
