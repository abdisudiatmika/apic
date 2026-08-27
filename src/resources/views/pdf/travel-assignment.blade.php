<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $travelAssignment->typeLabel() }} {{ $travelAssignment->letter_number }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { text-align: center; border-bottom: 3px double #1a1a1a; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 11px; color: #555; }
        .title { text-align: center; margin-bottom: 20px; }
        .title h2 { text-decoration: underline; margin: 0; font-size: 14px; }
        .title p { margin: 2px 0; }
        table.info { width: 100%; margin-bottom: 16px; }
        table.info td { padding: 3px 0; vertical-align: top; }
        table.info td.label { width: 160px; }
        table.employees { width: 100%; border-collapse: collapse; margin: 12px 0; }
        table.employees th, table.employees td { border: 1px solid #999; padding: 6px 8px; font-size: 11px; text-align: left; }
        table.employees th { background: #f0f0f0; }
        .signature { margin-top: 50px; width: 260px; margin-left: auto; text-align: center; }
        .signature .space { height: 60px; }
        .footer-note { margin-top: 40px; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <h1>APIC</h1>
        <p>Alamat perusahaan &middot; Kota &middot; Indonesia</p>
    </div>

    <div class="title">
        <h2>{{ strtoupper($travelAssignment->typeLabel()) }}</h2>
        <p>Nomor: {{ $travelAssignment->letter_number }}</p>
    </div>

    <p>Yang bertanda tangan di bawah ini menugaskan pegawai berikut untuk melaksanakan
        {{ strtolower($travelAssignment->typeLabel()) }}:</p>

    <table class="employees">
        <thead>
            <tr>
                <th>NIP</th>
                <th>Nama</th>
                <th>Jabatan</th>
                <th>Departemen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($travelAssignment->employees as $employee)
                <tr>
                    <td>{{ $employee->nip }}</td>
                    <td>{{ $employee->name }}</td>
                    <td>{{ $employee->position?->name ?? '-' }}</td>
                    <td>{{ $employee->department?->name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="info">
        <tr>
            <td class="label">Tujuan</td>
            <td>: {{ $travelAssignment->destination }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Pelaksanaan</td>
            <td>: {{ $travelAssignment->start_date->translatedFormat('d F Y') }}
                &ndash; {{ $travelAssignment->end_date->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <td class="label">Keperluan</td>
            <td>: {{ $travelAssignment->purpose }}</td>
        </tr>
        @if ($travelAssignment->transportation)
            <tr>
                <td class="label">Transportasi</td>
                <td>: {{ $travelAssignment->transportation }}</td>
            </tr>
        @endif
    </table>

    <p>Demikian surat ini dibuat untuk dilaksanakan dengan penuh tanggung jawab.</p>

    <div class="signature">
        <p>{{ now()->translatedFormat('d F Y') }}</p>
        <div class="space"></div>
        <p><strong>{{ $travelAssignment->signatory_name ?? '.....................................' }}</strong><br>
            {{ $travelAssignment->signatory_position ?? 'Jabatan Penandatangan' }}</p>
    </div>

    <p class="footer-note">
        Dokumen ini dibuat otomatis oleh sistem HRIS APIC. Format nomor surat, kop, dan pihak
        penandatangan bersifat sementara — perlu disesuaikan dengan SOP resmi perusahaan.
    </p>
</body>
</html>
