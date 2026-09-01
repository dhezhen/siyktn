<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syahadah - {{ $peserta->nama }}</title>
    
    <!-- Google Fonts untuk Bahasa Arab -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Noto+Naskh+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite('resources/css/app.css')

    <style>
        :root {
            --primary-color: #065f46; /* emerald-800 */
            --gold-color: #d97706; /* amber-600 */
        }

        body {
            background-color: #f1f5f9; /* slate-100 */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Amiri', 'Times New Roman', serif;
        }

        /* Ukuran kertas A4 Landscape */
        .certificate-container {
            width: 297mm;
            height: 210mm;
            background-color: #ffffff;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            box-sizing: border-box;
            padding: 20mm;
        }

        /* Print Settings */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                background-color: transparent;
                min-height: auto;
                display: block;
            }
            .certificate-container {
                width: 297mm;
                height: 210mm;
                box-shadow: none;
                margin: 0;
                padding: 20mm;
                page-break-after: avoid;
            }
            .no-print {
                display: none !important;
            }
        }

        /* Frame/Border Sementara */
        .certificate-border {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px double var(--gold-color);
            padding: 2mm;
            pointer-events: none;
            z-index: 10;
        }
        
        .certificate-border-inner {
            width: 100%;
            height: 100%;
            border: 1px solid var(--primary-color);
        }

        .content {
            position: relative;
            z-index: 20;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .header h1 {
            font-family: 'Noto Naskh Arabic', sans-serif;
            font-size: 36pt;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
            line-height: 1.2;
        }

        .header h2 {
            font-family: 'Amiri', serif;
            font-size: 24pt;
            color: var(--gold-color);
            margin: 10px 0 0 0;
            font-weight: 700;
        }

        .body-text {
            font-size: 20pt;
            line-height: 2;
            color: #1e293b;
        }

        .student-name {
            font-family: 'Noto Naskh Arabic', sans-serif;
            font-size: 28pt;
            font-weight: 700;
            color: var(--primary-color);
            margin: 15px 0;
            border-bottom: 2px dashed var(--gold-color);
            display: inline-block;
            min-width: 50%;
            padding: 0 20px 5px;
        }

        .achievement-details {
            font-size: 18pt;
            margin-top: 15px;
            font-weight: 700;
        }
        
        .predikat {
            font-family: 'Noto Naskh Arabic', sans-serif;
            font-size: 24pt;
            color: var(--gold-color);
            display: inline-block;
            margin: 0 10px;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding: 0 30px;
        }

        .signature-box {
            text-align: center;
            width: 250px;
        }

        .signature-title {
            font-size: 16pt;
            font-weight: 700;
            color: #334155;
            margin-bottom: 60px; /* Space for signature */
        }

        .signature-name {
            font-size: 16pt;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
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
            direction: ltr; /* Reset to LTR for UI */
        }

        .btn-print {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-family: sans-serif;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }

        .btn-print:hover {
            background-color: #047857; /* emerald-700 */
        }

        .btn-back {
            background-color: #e2e8f0;
            color: #334155;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-family: sans-serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .ni-text {
            position: absolute;
            top: 0;
            left: 0;
            font-family: 'Times New Roman', sans-serif;
            font-size: 10pt;
            direction: ltr;
            color: #64748b;
        }

    </style>
</head>
<body>

    <div class="print-toolbar no-print">
        <a href="{{ route('halaqah.laporan', $halaqah) }}" class="btn-back">
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
            Cetak Syahadah
        </button>
    </div>

    <div class="certificate-container">
        <!-- Bingkai Sementara -->
        <div class="certificate-border">
            <div class="certificate-border-inner"></div>
        </div>

        <div class="content">
            <div class="ni-text">
                NI: {{ $anggota->pendaftaran?->nomor_induk ?: '-' }}<br>
            </div>

            <div class="header">
                <h1>شَهَادَةُ حِفْظِ الْقُرْآنِ الْكَرِيمِ</h1>
                <h2>Syahadah Tahfizh Al-Qur'an</h2>
            </div>

            <div class="body-text">
                <p>
                    يَشْهَدُ مَرْكَزُ الْحِفْظِ بِأَنَّ :
                    <br>
                    <span class="student-name">
                        {{ $peserta->nama_arab ?: $peserta->nama }}
                    </span>
                    <br>
                    قَدْ أَتَمَّ / أَتَمَّتْ حِفْظَ : 
                    <span style="font-weight:bold; color: var(--primary-color);">
                        {{ rtrim(rtrim(number_format((float)($anggota->ziyadah_halaman ?? 0), 1, ',', '.'), '0'), ',') }}
                    </span> صَفْحَةٍ 
                    (بِمِقْدَارِ {{ \App\Models\Setoran::setaraJuz((float)($anggota->ziyadah_halaman ?? 0)) }})
                </p>
                
                <p class="achievement-details">
                    بِتَقْدِيرِ : <span class="predikat">{{ $anggota->predikat_arab }}</span> ({{ $anggota->predikat }})
                </p>
            </div>

            <div class="signatures">
                <!-- Kanan (RTL) -->
                <div class="signature-box">
                    <div class="signature-title">الْمُحَفِّظُ<br>Muhaffizh/Penguji</div>
                    <div class="signature-name">{{ $halaqah->muhaffizh?->nama ?? '..........................' }}</div>
                </div>

                <!-- Kiri (RTL) -->
                <div class="signature-box">
                    <div class="signature-title">الْمُدِيرُ<br>Direktur Karantina</div>
                    <div class="signature-name">..........................</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
