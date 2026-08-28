<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran & Keterlambatan</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { text-align: center; border-bottom: 3px double #1a1a1a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 11px; color: #555; }
        .title { text-align: center; margin-bottom: 12px; }
        .title h2 { text-decoration: underline; margin: 0; font-size: 14px; }
        .title p { margin: 2px 0; font-size: 11px; color: #555; }
        table.data { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.data th, table.data td { border: 1px solid #999; padding: 6px 8px; font-size: 11px; text-align: left; }
        table.data th { background: #f0f0f0; }
        table.data td.num { text-align: right; }
        .footer-note { margin-top: 30px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>APIC</h1>
        <p>Alamat perusahaan &middot; Kota &middot; Indonesia</p>
    </div>

    <div class="title">
        <h2>LAPORAN KEHADIRAN &amp; KETERLAMBATAN</h2>
        <p>Periode: {{ \Illuminate\Support\Carbon::parse($filters['start_date'])->translatedFormat('d F Y') }}
            &ndash; {{ \Illuminate\Support\Carbon::parse($filters['end_date'])->translatedFormat('d F Y') }}</p>
        @if ($departmentName || $branchName)
            <p>{{ $departmentName ? "Departemen: {$departmentName}" : '' }}{{ $departmentName && $branchName ? ' | ' : '' }}{{ $branchName ? "Cabang: {$branchName}" : '' }}</p>
        @endif
    </div>

    <table class="data">
        <thead>
            <tr>
                <th>Pegawai</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Tidak Hadir</th>
                <th>Dinas</th>
                <th>Total Menit Terlambat</th>
                <th>Rata-rata Menit Terlambat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee->name }}</td>
                    <td class="num">{{ $row->hadir }}</td>
                    <td class="num">{{ $row->terlambat }}</td>
                    <td class="num">{{ $row->tidak_hadir }}</td>
                    <td class="num">{{ $row->dinas }}</td>
                    <td class="num">{{ $row->total_late_minutes }}</td>
                    <td class="num">{{ $row->avg_late_minutes }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">
        Dokumen ini dibuat otomatis oleh sistem HRIS APIC pada {{ now()->translatedFormat('d F Y H:i') }}.
    </p>
</body>
</html>
