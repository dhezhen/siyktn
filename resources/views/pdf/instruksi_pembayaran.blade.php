<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Instruksi Pembayaran</title>
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
        .alert { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 10px 15px; margin-bottom: 20px; }
        .bank-info { background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .bank-info h3 { margin-top: 0; margin-bottom: 10px; color: #166534; }
        .total { font-size: 18px; font-weight: bold; color: #0f172a; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('logo.png') }}" alt="Logo Karantina Tahfizh" style="max-height: 70px; margin-bottom: 10px;">
        <h1>Instruksi Pembayaran Pendaftaran</h1>
        <p>Kode Pendaftaran: <strong>{{ $pendaftaran->kode_pendaftaran }}</strong></p>
    </div>

    <div class="alert">
        <strong>Penting:</strong> Anda dapat membayar Biaya Pendaftaran saja terlebih dahulu (Rp 100.000) sebagai tanda jadi/komitmen. Biaya Program dapat dilunasi setelah Anda dinyatakan lulus verifikasi, atau Anda juga dapat membayar total keseluruhan secara langsung.
    </div>

    <div class="section">
        <div class="section-title">Data Pendaftar</div>
        <table>
            <tr>
                <th>Nama Lengkap</th>
                <td>{{ $pendaftaran->peserta->nama }}</td>
            </tr>
            <tr>
                <th>Program/Angkatan</th>
                <td>{{ $pendaftaran->paket_program_label }} - {{ $pendaftaran->angkatan->nama ?? '' }}</td>
            </tr>
            <tr>
                <th>Tanggal Mendaftar</th>
                <td>{{ $pendaftaran->didaftarkan_pada->format('d F Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Rincian Biaya</div>
        <table>
            <tr>
                <th>Biaya Pendaftaran</th>
                <td>{{ $pendaftaran->formatted_biaya_pendaftaran }}</td>
            </tr>
            <tr>
                <th>Biaya Program</th>
                <td>{{ $pendaftaran->formatted_biaya_program }}</td>
            </tr>
            <tr>
                <th>Total Keseluruhan</th>
                <td class="total">Rp {{ number_format((float) $pendaftaran->biaya_pendaftaran + (float) $pendaftaran->biaya_program, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="bank-info">
        <h3>Informasi Rekening Pembayaran</h3>
        <p>Silakan transfer pembayaran ke salah satu rekening berikut:</p>
        <p>
            <strong>Bank Syariah Indonesia (BSI)</strong><br>
            No. Rekening: 79999 54547<br>
            A.n: Yayasan Karantina Tahfizh Nasional
        </p>
        <p><em>*Mohon sertakan Kode Pendaftaran ({{ $pendaftaran->kode_pendaftaran }}) pada berita transfer.</em></p>
    </div>
    
    <p style="font-size: 12px; color: #64748b; text-align: center; margin-top: 30px;">
        Harap simpan bukti pembayaran dan konfirmasikan kepada panitia atau unggah melalui sistem setelah Anda login.<br>
        <strong>Butuh bantuan? Hubungi Customer Service (WhatsApp/Telp): 081312700100</strong>
    </p>
</body>
</html>
