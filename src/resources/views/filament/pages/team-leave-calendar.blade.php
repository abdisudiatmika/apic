<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left dark:border-white/10">
                            <th class="py-2 pr-4 font-medium">Tanggal</th>
                            <th class="py-2 pr-4 font-medium">Jumlah Cuti</th>
                            <th class="py-2 pr-4 font-medium">Nama Pegawai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getDailyRows() as $row)
                            <tr class="border-b border-gray-100 dark:border-white/5 {{ $row['over_threshold'] ? 'bg-danger-50 dark:bg-danger-500/10' : '' }}">
                                <td class="py-2 pr-4">{{ $row['date']->translatedFormat('l, d F Y') }}</td>
                                <td class="py-2 pr-4">
                                    <span class="{{ $row['over_threshold'] ? 'font-semibold text-danger-600 dark:text-danger-400' : '' }}">
                                        {{ $row['count'] }}
                                    </span>
                                    @if ($row['over_threshold'])
                                        <span class="ml-1 text-xs text-danger-600 dark:text-danger-400">&#9888; melebihi ambang batas</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $row['names'] ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-gray-500">Tidak ada data pada rentang tanggal ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
