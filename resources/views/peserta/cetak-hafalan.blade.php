<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Setoran Hafalan - {{ $peserta->nama }}</title>
    @vite('resources/css/app.css')
    <style>
        :root {
            --primary-color: #065f46;
        }
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
            font-family: sans-serif;
            color: #334155;
        }
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20mm;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .print-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 100;
            display: flex;
            gap: 10px;
        }
        .btn-print {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print:hover {
            background-color: #047857;
        }
        .btn-back {
            background-color: #e2e8f0;
            color: #334155;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background-color: #cbd5e1;
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                background-color: transparent;
                padding: 0;
            }
            .print-container {
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: 100%;
                padding: 15mm;
            }
            .no-print {
                display: none !important;
            }
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 10pt;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f8fafc;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <a href="{{ route('peserta.show', $peserta) }}" class="btn-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Kembali
        </a>
        <button onclick="window.print()" class="btn-print">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
            </svg>
            Cetak Riwayat
        </button>
    </div>

    <div class="print-container">
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid var(--primary-color); padding-bottom: 15px;">
            <img src="{{ asset('logo.png') }}" alt="Logo" style="height: 70px; margin-bottom: 15px; object-fit: contain;">
            <h1 style="margin: 0; font-size: 18pt; color: var(--primary-color); font-weight: bold; text-transform: uppercase;">RIWAYAT SETORAN HAFALAN</h1>
        </div>

        <table style="width: 100%; margin-bottom: 20px; font-size: 11pt;">
            <tr>
                <td style="width: 140px; font-weight: bold; padding: 4px 0;">Nama Lengkap</td>
                <td style="width: 10px;">:</td>
                <td>{{ $peserta->nama }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 4px 0;">NIK</td>
                <td>:</td>
                <td>{{ $peserta->nik ?: '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; padding: 4px 0;">Waktu Cetak</td>
                <td>:</td>
                <td>{{ now()->translatedFormat('d F Y H:i') }}</td>
            </tr>
        </table>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">No</th>
                    <th style="width: 90px;">Tanggal</th>
                    <th>Angkatan</th>
                    <th>Muhaffizh / Pencatat</th>
                    <th style="width: 70px;">Jenis</th>
                    <th>Capaian Hafalan</th>
                    <th style="width: 60px; text-align: center;">Halaman</th>
                    <th>Kualitas</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($setorans as $index => $setoran)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>{{ $setoran->tanggal->translatedFormat('d M Y') }}</td>
                        <td>{{ $setoran->angkatan_nama }}</td>
                        <td>{{ $setoran->muhaffizh?->nama ?? $setoran->pencatat?->name ?? '-' }}</td>
                        <td>
                            <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9pt; font-weight: bold; background-color: {{ $setoran->jenis === 'ziyadah' ? '#ecfdf5' : '#f0f9ff' }}; color: {{ $setoran->jenis === 'ziyadah' ? '#047857' : '#0369a1' }}; border: 1px solid {{ $setoran->jenis === 'ziyadah' ? '#a7f3d0' : '#bae6fd' }};">
                                {{ ucfirst($setoran->jenis) }}
                            </span>
                        </td>
                        <td>{{ $setoran->bacaan ?: '-' }}</td>
                        <td style="text-align: center; font-weight: bold;">{{ rtrim(rtrim(number_format($setoran->jumlah_halaman, 1, ',', '.'), '0'), ',') }}</td>
                        <td>{{ $setoran->kualitas_label }}</td>
                        <td>{{ $setoran->catatan ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px; color: #64748b;">Belum ada riwayat setoran hafalan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
            <table style="width: 300px; font-size: 11pt; border-collapse: collapse; border: 1px solid #cbd5e1;">
                <tr>
                    <th colspan="2" style="background-color: #f8fafc; padding: 8px; border-bottom: 1px solid #cbd5e1; text-align: center;">TOTAL CAPAIAN</th>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold; border-right: 1px solid #cbd5e1;">Total Ziyadah</td>
                    <td style="padding: 8px; text-align: right; font-weight: bold; color: #047857;">
                        {{ rtrim(rtrim(number_format($setorans->where('jenis', 'ziyadah')->sum('jumlah_halaman'), 1, ',', '.'), '0'), ',') }} Hlm
                        <div style="font-size: 9pt; color: #64748b; font-weight: normal; margin-top: 2px;">
                            (Setara dengan {{ \App\Models\Setoran::setaraJuz($setorans->where('jenis', 'ziyadah')->sum('jumlah_halaman')) }})
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; font-weight: bold; border-right: 1px solid #cbd5e1;">Total Murajaah</td>
                    <td style="padding: 8px; text-align: right; font-weight: bold; color: #0369a1;">
                        {{ rtrim(rtrim(number_format($setorans->where('jenis', 'murajaah')->sum('jumlah_halaman'), 1, ',', '.'), '0'), ',') }} Hlm
                        <div style="font-size: 9pt; color: #64748b; font-weight: normal; margin-top: 2px;">
                            (Setara dengan {{ \App\Models\Setoran::setaraJuz($setorans->where('jenis', 'murajaah')->sum('jumlah_halaman')) }})
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
