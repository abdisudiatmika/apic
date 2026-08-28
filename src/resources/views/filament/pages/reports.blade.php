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

        <x-filament::section>
            <x-slot name="heading">Ringkasan Kehadiran &amp; Keterlambatan</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.attendance.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.attendance.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Pegawai</th>
                            <th class="py-2 pr-4 font-medium">Hadir</th>
                            <th class="py-2 pr-4 font-medium">Terlambat</th>
                            <th class="py-2 pr-4 font-medium">Tidak Hadir</th>
                            <th class="py-2 pr-4 font-medium">Dinas</th>
                            <th class="py-2 pr-4 font-medium">Total Menit Terlambat</th>
                            <th class="py-2 pr-4 font-medium">Rata-rata Menit Terlambat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getAttendanceRows() as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row->employee->name }}</td>
                                <td class="py-2 pr-4">{{ $row->hadir }}</td>
                                <td class="py-2 pr-4">{{ $row->terlambat }}</td>
                                <td class="py-2 pr-4">{{ $row->tidak_hadir }}</td>
                                <td class="py-2 pr-4">{{ $row->dinas }}</td>
                                <td class="py-2 pr-4">{{ $row->total_late_minutes }}</td>
                                <td class="py-2 pr-4">{{ $row->avg_late_minutes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">Tidak ada data pada rentang & filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ringkasan Cuti</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.leave.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.leave.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Pegawai</th>
                            <th class="py-2 pr-4 font-medium">Total Pengajuan</th>
                            <th class="py-2 pr-4 font-medium">Hari Disetujui</th>
                            <th class="py-2 pr-4 font-medium">Hari Pending</th>
                            <th class="py-2 pr-4 font-medium">Ditolak</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getLeaveRows() as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row->employee->name }}</td>
                                <td class="py-2 pr-4">{{ $row->total_pengajuan }}</td>
                                <td class="py-2 pr-4">{{ $row->hari_disetujui }}</td>
                                <td class="py-2 pr-4">{{ $row->hari_pending }}</td>
                                <td class="py-2 pr-4">{{ $row->ditolak }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada data pada rentang & filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ringkasan Bon Cuti</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.leave-advance.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.leave-advance.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Pegawai</th>
                            <th class="py-2 pr-4 font-medium">Total Pengajuan</th>
                            <th class="py-2 pr-4 font-medium">Total Hari</th>
                            <th class="py-2 pr-4 font-medium">Outstanding</th>
                            <th class="py-2 pr-4 font-medium">Lunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getLeaveAdvanceRows() as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row->employee->name }}</td>
                                <td class="py-2 pr-4">{{ $row->total_pengajuan }}</td>
                                <td class="py-2 pr-4">{{ $row->total_hari }}</td>
                                <td class="py-2 pr-4">{{ $row->outstanding }}</td>
                                <td class="py-2 pr-4">{{ $row->lunas }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada data pada rentang & filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Ringkasan Perjalanan Dinas</x-slot>
            <x-slot name="headerEnd">
                <div class="flex gap-2">
                    <x-filament::button href="{{ route('reports.travel.excel', $filters) }}" color="gray" size="sm" icon="heroicon-o-table-cells">Excel</x-filament::button>
                    <x-filament::button href="{{ route('reports.travel.pdf', $filters) }}" color="gray" size="sm" icon="heroicon-o-document-text">PDF</x-filament::button>
                </div>
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Pegawai</th>
                            <th class="py-2 pr-4 font-medium">Surat Tugas</th>
                            <th class="py-2 pr-4 font-medium">Perjalanan Dinas</th>
                            <th class="py-2 pr-4 font-medium">Surat Jalan</th>
                            <th class="py-2 pr-4 font-medium">Total Hari</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getTravelRows() as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5">
                                <td class="py-2 pr-4">{{ $row->employee->name }}</td>
                                <td class="py-2 pr-4">{{ $row->surat_tugas }}</td>
                                <td class="py-2 pr-4">{{ $row->perjalanan_dinas }}</td>
                                <td class="py-2 pr-4">{{ $row->surat_jalan }}</td>
                                <td class="py-2 pr-4">{{ $row->total_hari }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada data pada rentang & filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
