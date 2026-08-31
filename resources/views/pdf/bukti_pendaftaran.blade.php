<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bukti Pendaftaran</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; color: #333; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; color: #064e3b; }
        .header p { margin: 5px 0 0; color: #64748b; }
        .section { margin-bottom: 20px; }
        .section-title { font-weight: bold; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { padding: 8px; text-align: left; border: 1px solid #e2e8f0; }
        th { background-color: #f8fafc; font-weight: 600; width: 35%; }
        .qr-section { text-align: center; margin-top: 30px; padding: 20px; border: 1px dashed #cbd5e1; background-color: #f8fafc; border-radius: 8px; }
        .qr-section p { margin-top: 10px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" alt="Logo Karantina Tahfizh" style="max-height: 70px; margin-bottom: 10px;">
        <h1>Bukti Pendaftaran Peserta</h1>
        <p>Tanda Terima Pendaftaran Program Karantina Tahfizh</p>
    </div>

    <div class="section">
        <div class="section-title">Data Pendaftar</div>
        <table>
            <tr>
                <th>Nomor Induk / NIK</th>
                <td>{{ $pendaftaran->peserta->nik ?? '-' }}</td>
            </tr>
            <tr>
                <th>Nama Lengkap</th>
                <td>{{ $pendaftaran->peserta->nama }}</td>
            </tr>
            <tr>
                <th>Jenis Kelamin</th>
                <td>{{ $pendaftaran->peserta->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            </tr>
            <tr>
                <th>Tempat, Tanggal Lahir</th>
                <td>{{ $pendaftaran->peserta->tempat_lahir }}, {{ $pendaftaran->peserta->tanggal_lahir?->format('d F Y') ?? '-' }}</td>
            </tr>
            <tr>
                <th>Nomor HP (WhatsApp)</th>
                <td>{{ $pendaftaran->peserta->no_hp ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Data Program</div>
        <table>
            <tr>
                <th>Kode Pendaftaran</th>
                <td><strong>{{ $pendaftaran->kode_pendaftaran }}</strong></td>
            </tr>
            <tr>
                <th>Program Pilihan</th>
                <td>{{ $pendaftaran->paket_program_label }}</td>
            </tr>
            <tr>
                <th>Angkatan</th>
                <td>{{ $pendaftaran->angkatan->nama ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tanggal Mendaftar</th>
                <td>{{ $pendaftaran->didaftarkan_pada->format('d F Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="qr-section">
        <!-- Render SVG QR Code to base64 to ensure dompdf handles it correctly -->
        <img src="data:image/svg+xml;base64,{{ base64_encode(SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(150)->generate($pendaftaran->kode_pendaftaran)) }}" alt="QR Code">
        <p>Pindai QR Code ini atau sebutkan Kode Pendaftaran <strong>{{ $pendaftaran->kode_pendaftaran }}</strong> pada saat registrasi ulang di lokasi.</p>
    </div>
    
    <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 30px;">
        Dokumen ini merupakan bukti pendaftaran yang sah. Harap disimpan dan ditunjukkan kepada panitia.
    </p>
</body>
</html>
