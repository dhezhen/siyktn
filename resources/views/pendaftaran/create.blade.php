<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Peserta — {{ setting('app_name', config('app.name')) }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen antialiased text-slate-900">

<!-- Page Container -->
<div class="flex flex-col lg:flex-row min-h-screen w-full">

    <!-- Bagian Kiri (40%): Logo & Judul -->
    <div class="w-full lg:w-2/5 lg:sticky lg:top-0 lg:h-screen flex flex-col justify-center items-center text-center px-6 py-12 lg:p-12 bg-gradient-to-br from-emerald-800 via-emerald-900 to-slate-950 text-white relative overflow-hidden shadow-2xl z-10">
        <!-- Aksen Dekoratif -->
        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,0.8) 0%, transparent 50%);"></div>
        
        <img src="{{ asset('logo.png') }}" alt="Logo" class="mb-8 h-28 lg:h-36 w-auto object-contain drop-shadow-xl relative z-10">
        
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight mb-5 relative z-10 drop-shadow-md">
            Sistem Informasi Pendaftaran <br>
            <span class="text-emerald-400">Karantina Tahfizh Al-Qur'an Nasional</span>
        </h1>
        
        <p class="text-emerald-50/90 text-sm sm:text-base font-medium leading-relaxed max-w-sm relative z-10">
            Silakan pilih mode pendaftar baru atau alumni, lalu lengkapi formulir pendaftaran di samping untuk bergabung bersama kami.
        </p>
    </div>

    <!-- Bagian Kanan (60%): Formulir Pendaftaran -->
    <div class="w-full lg:w-3/5 px-4 py-10 sm:px-10 lg:p-16 bg-gradient-to-br from-slate-50 via-white to-emerald-50/50">
        <div class="max-w-3xl mx-auto w-full">
            <x-alert />

    @if ($angkatan->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-10 shadow-sm text-center">
            <x-empty-state title="Pendaftaran sedang ditutup"
                           message="Saat ini belum ada angkatan yang menerima pendaftaran, atau kuotanya sudah penuh. Silakan periksa kembali beberapa waktu lagi." />
        </div>
    @else
        <!-- Mode Switcher Berbeda Warna (Pendaftar Baru = Emerald | Alumni = Amber Gold) -->
        <div class="mb-8 mx-auto max-w-md p-1.5 rounded-2xl bg-slate-200/60 grid grid-cols-2 gap-2 text-sm font-semibold shadow-inner">
            {{-- Tombol Pendaftar Baru (Emerald Gradient) --}}
            <button type="button" id="tab-baru" onclick="switchMode('baru')"
                    class="rounded-xl py-3 px-4 text-center transition-all duration-200 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-600/25 inline-flex items-center justify-center gap-2 font-extrabold cursor-pointer">
                <x-icon name="document-text" class="size-4 text-white" />
                <span>Pendaftar Baru</span>
            </button>

            {{-- Tombol Alumni (Amber Gold Gradient) --}}
            <button type="button" id="tab-alumni" onclick="switchMode('alumni')"
                    class="rounded-xl py-3 px-4 text-center transition-all duration-200 text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center gap-2 font-bold cursor-pointer">
                <x-icon name="bolt" class="size-4 text-amber-500" />
                <span>Alumni (Cukup NIK)</span>
            </button>
        </div>

        <!-- Main Card Form Container -->
        <div class="rounded-[2rem] border border-slate-200/60 bg-white p-6 sm:p-12 shadow-2xl shadow-slate-200/40">
            <form id="form-pendaftaran" method="POST" action="{{ route('pendaftaran.store') }}" enctype="multipart/form-data" class="space-y-10">
                @csrf
                <input type="hidden" name="tipe_pendaftar" id="tipe_pendaftar" value="{{ old('tipe_pendaftar', 'baru') }}">

                <!-- SEKSI 1: Angkatan & Paket Program -->
                <div class="space-y-6">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-3.5">
                        <span class="grid size-7 place-items-center rounded-xl bg-emerald-600 text-xs font-black text-white shadow-sm">1</span>
                        <h2 class="font-extrabold text-slate-900 text-sm sm:text-base tracking-wide">PILIHAN ANGKATAN & PAKET PROGRAM</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <x-select name="angkatan_id" id="angkatan_id" label="Pilih Angkatan Karantina" required>
                                @foreach ($angkatan as $item)
                                    <option value="{{ $item->id }}" @selected(old('angkatan_id') == $item->id)>
                                        {{ $item->nama }} (Tahun {{ $item->tahun }})
                                        @php
                                            $infoQuota = [];
                                            if ($item->sisa_kuota !== null) $infoQuota[] = "Total sisa {$item->sisa_kuota}";
                                            if ($item->sisa_kuota_putra !== null) $infoQuota[] = "Putra sisa {$item->sisa_kuota_putra}";
                                            if ($item->sisa_kuota_putri !== null) $infoQuota[] = "Putri sisa {$item->sisa_kuota_putri}";
                                        @endphp
                                        @if (!empty($infoQuota))
                                            — ({{ implode(', ', $infoQuota) }})
                                        @endif
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <!-- Banner Status Kuota Real-Time -->
                        <div id="quota-banner" class="hidden rounded-2xl px-4 py-3.5 text-xs font-semibold leading-relaxed transition-all shadow-sm"></div>

                        <div>
                            <x-select name="paket_program" id="paket_program" label="Pilih Paket Program Tahfizh" required onchange="updateInvoiceSummary()">
                                <option value="">-- Pilih Paket Program --</option>
                                @if (isset($programs) && $programs->count())
                                    @foreach ($programs as $prog)
                                        <option value="{{ $prog->kode }}" data-biaya-program="{{ $prog->biaya_program }}" data-biaya-reg="{{ $prog->biaya_pendaftaran }}" @selected(old('paket_program') == $prog->kode)>
                                            {{ $prog->nama }} — {{ $prog->formatted_biaya_program }}
                                        </option>
                                    @endforeach
                                @else
                                    @foreach (\App\Models\Pendaftaran::PAKET_PROGRAM as $key => $paket)
                                        <option value="{{ $key }}" data-biaya-program="{{ $paket['biaya'] }}" data-biaya-reg="100000" @selected(old('paket_program') == $key)>
                                            {{ $paket['nama'] }} — Rp {{ number_format($paket['biaya'], 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                @endif
                            </x-select>
                        </div>

                        <!-- Card Ringkasan Estimasi Biaya -->
                        <div id="invoice-summary" class="rounded-2xl border border-emerald-200/80 bg-emerald-50 p-6 text-slate-900 text-sm space-y-3 shadow-sm">
                            <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-emerald-900 border-b border-emerald-200/70 pb-2.5">
                                <x-icon name="document-text" class="size-4 text-emerald-700" />
                                <span>Rincian Ringkasan Biaya</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-700">
                                <span>Biaya Registrasi Pendaftaran:</span>
                                <span id="summary-biaya-reg" class="font-bold text-slate-900">Rp 100.000</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-700">
                                <span>Biaya Program Karantina:</span>
                                <span id="summary-biaya-program" class="font-bold text-slate-900">Rp 0</span>
                            </div>
                            <div class="border-t border-emerald-200/80 pt-2.5 flex justify-between items-center font-extrabold text-sm text-slate-950">
                                <span>Total Estimasi Biaya:</span>
                                <span id="summary-total-biaya" class="text-emerald-700 font-extrabold text-base">Rp 100.000</span>
                            </div>
                            <p class="mt-1.5 text-[11px] text-emerald-900 leading-snug font-medium">
                                💡 Pembayaran biaya registrasi dan biaya program dapat ditransfer atau dibayar langsung secara tunai saat kedatangan di lokasi (Presensi On-Site).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- MODE ALUMNI (Warna Tema Amber Gold) -->
                <div id="section-alumni" class="hidden space-y-6 pt-6 border-t border-slate-100">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-3.5">
                        <span class="grid size-7 place-items-center rounded-xl bg-amber-500 text-xs font-black text-white shadow-sm">⚡</span>
                        <h2 class="font-extrabold text-amber-950 text-sm sm:text-base tracking-wide">VERIFIKASI DATA ALUMNI</h2>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-input name="nik_alumni" id="nik_alumni" label="NIK (Nomor KTP/KK) (16 digit)"
                                 inputmode="numeric" maxlength="16" placeholder="3208xxxxxxxxxxxx"
                                 :value="old('nik_alumni')" />

                        <x-input name="tanggal_lahir_alumni" id="tanggal_lahir_alumni" type="date" label="Tanggal Lahir"
                                 :value="old('tanggal_lahir_alumni')"
                                 hint="Verifikasi privasi alumni." />

                        <div class="sm:col-span-2 rounded-2xl bg-amber-50 p-4 text-xs leading-relaxed text-amber-950 border border-amber-200/80 flex items-start gap-2.5">
                            <x-icon name="light-bulb" class="size-5 shrink-0 text-amber-600 mt-0.5" />
                            <span>Jika NIK & Tanggal Lahir Anda cocok dengan catatan sistem kami, seluruh data diri & KTP/KK Anda akan secara otomatis digunakan kembali.</span>
                        </div>
                    </div>
                </div>

                <!-- MODE PENDAFTAR BARU -->
                <div id="section-baru" class="space-y-10 pt-6 border-t border-slate-100">
                    <!-- SEKSI 2: Data Diri & Identitas -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3.5">
                            <span class="grid size-7 place-items-center rounded-xl bg-emerald-600 text-xs font-black text-white shadow-sm">2</span>
                            <h2 class="font-extrabold text-slate-900 text-sm sm:text-base tracking-wide">DATA DIRI & IDENTITAS</h2>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-input name="nama" id="input_nama" label="Nama Lengkap" :value="old('nama')" autofocus placeholder="Nama sesuai KTP/KK" />

                            <x-input name="nik" id="input_nik" label="NIK (Nomor KTP/KK) (16 digit)" :value="old('nik')"
                                     inputmode="numeric" maxlength="16" placeholder="3208xxxxxxxxxxxx" />

                            <x-select name="jenis_kelamin" id="jenis_kelamin" label="Jenis Kelamin">
                                <option value="L" @selected(old('jenis_kelamin', 'L') === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                            </x-select>

                            <x-select name="kewarganegaraan" id="kewarganegaraan" label="Kewarganegaraan">
                                <option value="WNI" @selected(old('kewarganegaraan', 'WNI') === 'WNI')>WNI (Indonesia)</option>
                                <option value="WNA" @selected(old('kewarganegaraan') === 'WNA')>WNA / Luar Negeri</option>
                            </x-select>

                            <x-input name="tempat_lahir" id="input_tempat_lahir" label="Tempat Lahir" :value="old('tempat_lahir')" placeholder="mis. Kuningan" />

                            <x-input name="tanggal_lahir" id="input_tanggal_lahir" type="date" label="Tanggal Lahir"
                                     :value="old('tanggal_lahir')" />

                            <!-- Container Wilayah WNI -->
                            <div id="container-wni" class="contents sm:col-span-2">
                                <x-select name="provinsi" id="provinsi" label="Provinsi">
                                    <option value="">— Pilih Provinsi —</option>
                                    @php
                                        $daftarProvinsi = [
                                            'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi',
                                            'Sumatera Selatan', 'Bangka Belitung', 'Bengkulu', 'Lampung', 'DKI Jakarta',
                                            'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Banten', 'Bali',
                                            'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
                                            'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
                                            'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara',
                                            'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat', 'Papua Selatan', 'Papua Tengah',
                                            'Papua Pegunungan', 'Papua Barat Daya'
                                        ];
                                    @endphp
                                    @foreach ($daftarProvinsi as $prov)
                                        <option value="{{ $prov }}" @selected(old('provinsi') === $prov)>{{ $prov }}</option>
                                    @endforeach
                                </x-select>

                                <x-input name="kabupaten_kota_wni" id="kabupaten_kota_wni" label="Kabupaten / Kota"
                                         :value="old('kabupaten_kota')" placeholder="mis. Kab. Kuningan" />
                            </div>

                            <!-- Container Wilayah WNA -->
                            <div id="container-wna" class="hidden contents sm:col-span-2">
                                <x-input name="negara_wna" id="negara_wna" label="Nama Negara Asal"
                                         :value="old('negara')" placeholder="mis. Malaysia" />

                                <x-input name="kabupaten_kota_wna" id="kabupaten_kota_wna" label="Kota Asal"
                                         :value="old('kabupaten_kota')" placeholder="mis. Kuala Lumpur" />
                            </div>

                            <input type="hidden" name="negara" id="input_negara" value="{{ old('negara', 'Indonesia') }}">
                            <input type="hidden" name="kabupaten_kota" id="input_kabupaten_kota" value="{{ old('kabupaten_kota') }}">

                            <x-input name="no_hp" id="input_no_hp" label="Nomor WhatsApp" :value="old('no_hp')"
                                     placeholder="08xxxxxxxxxx" />

                            <x-input name="email" id="input_email" type="email" label="Email Aktif" :value="old('email')"
                                     placeholder="nama@contoh.id" />

                            <div class="sm:col-span-2">
                                <label for="alamat" class="mb-1.5 block text-sm font-semibold text-slate-800">
                                    Alamat Lengkap Domisili <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="alamat" id="alamat" rows="2.5"
                                          class="block w-full rounded-xl border-0 px-4 py-3 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600 shadow-sm placeholder:text-slate-400 text-slate-900 transition-all duration-200"
                                          placeholder="Nama jalan, RT/RW, Desa/Kelurahan, Kecamatan…">{{ old('alamat') }}</textarea>
                                @error('alamat')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 3: Data Wali & Dokumen -->
                    <div class="space-y-6 pt-6 border-t border-slate-100">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-3.5">
                            <span class="grid size-7 place-items-center rounded-xl bg-emerald-600 text-xs font-black text-white shadow-sm">3</span>
                            <h2 class="font-extrabold text-slate-900 text-sm sm:text-base tracking-wide">DATA ORANG TUA / WALI</h2>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-input name="nama_wali" id="input_nama_wali" label="Nama Wali" :value="old('nama_wali')" placeholder="Nama lengkap wali" />
                            <x-input name="no_hp_wali" id="input_no_hp_wali" label="Nomor HP Wali" :value="old('no_hp_wali')" placeholder="08xxxxxxxxxx" />

                        </div>
                    </div>
                </div>

                <!-- Konfirmasi & Submit -->
                <div class="pt-8 border-t border-slate-100 space-y-6">
                    <label class="flex items-start gap-3 text-xs sm:text-sm text-slate-800 cursor-pointer font-medium">
                        <input type="checkbox" name="persetujuan" value="1" required @checked(old('persetujuan'))
                               class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="leading-relaxed">
                            Saya menyatakan bahwa seluruh data yang diisi adalah benar dan bersedia mematuhi tata tertib karantina tahfizh.
                        </span>
                    </label>
                    @error('persetujuan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror

                    <button type="submit" id="btn-submit"
                            class="w-full py-4 px-6 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-extrabold text-base shadow-lg shadow-emerald-600/30 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 cursor-pointer">
                        <x-icon name="check-badge" class="size-5 text-emerald-200" />
                        <span>Kirim Formulir Pendaftaran</span>
                    </button>
                </div>
            </form>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const quotaData = @json($quotaData ?? []);
                const angkatanSelect = document.getElementById('angkatan_id');
                const genderSelect = document.getElementById('jenis_kelamin');
                const banner = document.getElementById('quota-banner');
                const btnSubmit = document.getElementById('btn-submit');

                function updateQuotaCheck() {
                    if (!angkatanSelect || !genderSelect || !banner) return;

                    const angkatanId = angkatanSelect.value;
                    const gender = genderSelect.value;
                    const data = quotaData[angkatanId];

                    if (!data) {
                        banner.classList.add('hidden');
                        return;
                    }

                    const labelGender = gender === 'L' ? 'Laki-laki' : 'Perempuan';
                    const isFullGender = (gender === 'L' && data.is_full_putra) || (gender === 'P' && data.is_full_putri);
                    const sisaGender = gender === 'L' ? data.sisa_putra : data.sisa_putri;

                    banner.classList.remove('hidden', 'bg-rose-50', 'text-rose-900', 'border', 'border-rose-200', 'bg-emerald-50', 'text-emerald-900', 'border-emerald-200');

                    if (isFullGender) {
                        banner.classList.add('bg-rose-50', 'text-rose-900', 'border', 'border-rose-200');
                        banner.innerHTML = `⚠️ <strong>Kuota Penuh!</strong> Kuota pendaftaran untuk <strong>${labelGender}</strong> pada <strong>${data.nama}</strong> sudah penuh. Silakan pilih angkatan lain.`;
                        if (btnSubmit) {
                            btnSubmit.disabled = true;
                            btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } else {
                        banner.classList.add('bg-emerald-50', 'text-emerald-900', 'border', 'border-emerald-200');
                        let infoText = `✓ Kuota pendaftaran untuk <strong>${labelGender}</strong> pada <strong>${data.nama}</strong> masih tersedia`;
                        if (sisaGender !== null) {
                            infoText += ` (sisa <strong>${sisaGender}</strong> kursi)`;
                        }
                        infoText += '.';
                        banner.innerHTML = infoText;

                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                }

                if (angkatanSelect && genderSelect) {
                    angkatanSelect.addEventListener('change', updateQuotaCheck);
                    genderSelect.addEventListener('change', updateQuotaCheck);
                    updateQuotaCheck();
                }

                // Wilayah WNI vs WNA Logic
                const kwgSelect = document.getElementById('kewarganegaraan');
                const containerWni = document.getElementById('container-wni');
                const containerWna = document.getElementById('container-wna');
                const inputNegara = document.getElementById('input_negara');
                const inputKabKota = document.getElementById('input_kabupaten_kota');

                const kabWni = document.getElementById('kabupaten_kota_wni');
                const negaraWna = document.getElementById('negara_wna');
                const kabWna = document.getElementById('kabupaten_kota_wna');

                function updateTerritoryFields() {
                    if (!kwgSelect) return;

                    const isWni = kwgSelect.value === 'WNI';

                    if (isWni) {
                        if (containerWni) containerWni.classList.remove('hidden');
                        if (containerWna) containerWna.classList.add('hidden');
                        if (inputNegara) inputNegara.value = 'Indonesia';
                        if (inputKabKota && kabWni) inputKabKota.value = kabWni.value;
                    } else {
                        if (containerWni) containerWni.classList.add('hidden');
                        if (containerWna) containerWna.classList.remove('hidden');
                        if (inputNegara && negaraWna) inputNegara.value = negaraWna.value || 'Luar Negeri';
                        if (inputKabKota && kabWna) inputKabKota.value = kabWna.value;
                    }
                }

                if (kwgSelect) {
                    kwgSelect.addEventListener('change', updateTerritoryFields);
                    if (kabWni) kabWni.addEventListener('input', updateTerritoryFields);
                    if (negaraWna) negaraWna.addEventListener('input', updateTerritoryFields);
                    if (kabWna) kabWna.addEventListener('input', updateTerritoryFields);
                    updateTerritoryFields();
                }
            });

            window.switchMode = function (mode) {
                const tipeInput = document.getElementById('tipe_pendaftar');
                const tabBaru = document.getElementById('tab-baru');
                const tabAlumni = document.getElementById('tab-alumni');
                const secBaru = document.getElementById('section-baru');
                const secAlumni = document.getElementById('section-alumni');

                const nikAlumni = document.getElementById('nik_alumni');
                const tglAlumni = document.getElementById('tanggal_lahir_alumni');

                const inputNama = document.getElementById('input_nama');
                const inputNik = document.getElementById('input_nik');
                const inputTgl = document.getElementById('input_tanggal_lahir');
                const inputHp = document.getElementById('input_no_hp');
                const inputEmail = document.getElementById('input_email');
                const inputAlamat = document.getElementById('alamat');

                if (mode === 'alumni') {
                    if (tipeInput) tipeInput.value = 'alumni';

                    if (tabAlumni) tabAlumni.className = 'rounded-xl py-3 px-4 text-center transition-all duration-200 bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-md shadow-amber-500/25 inline-flex items-center justify-center gap-2 font-extrabold cursor-pointer';
                    if (tabBaru) tabBaru.className = 'rounded-xl py-3 px-4 text-center transition-all duration-200 text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center gap-2 font-bold cursor-pointer';

                    if (secAlumni) secAlumni.classList.remove('hidden');
                    if (secBaru) secBaru.classList.add('hidden');

                    if (nikAlumni) nikAlumni.required = true;
                    if (tglAlumni) tglAlumni.required = true;

                    if (inputNama) inputNama.required = false;
                    if (inputNik) inputNik.required = false;
                    if (inputTgl) inputTgl.required = false;
                    if (inputHp) inputHp.required = false;
                    if (inputEmail) inputEmail.required = false;
                    if (inputAlamat) inputAlamat.required = false;
                } else {
                    if (tipeInput) tipeInput.value = 'baru';

                    if (tabBaru) tabBaru.className = 'rounded-xl py-3 px-4 text-center transition-all duration-200 bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-600/25 inline-flex items-center justify-center gap-2 font-extrabold cursor-pointer';
                    if (tabAlumni) tabAlumni.className = 'rounded-xl py-3 px-4 text-center transition-all duration-200 text-slate-700 hover:bg-slate-100 inline-flex items-center justify-center gap-2 font-bold cursor-pointer';

                    if (secBaru) secBaru.classList.remove('hidden');
                    if (secAlumni) secAlumni.classList.add('hidden');

                    if (nikAlumni) nikAlumni.required = false;
                    if (tglAlumni) tglAlumni.required = false;

                    if (inputNama) inputNama.required = true;
                    if (inputNik) inputNik.required = true;
                    if (inputTgl) inputTgl.required = true;
                    if (inputHp) inputHp.required = true;
                    if (inputEmail) inputEmail.required = true;
                    if (inputAlamat) inputAlamat.required = true;
                }
            };

            if (document.getElementById('tipe_pendaftar') && document.getElementById('tipe_pendaftar').value === 'alumni') {
                window.switchMode('alumni');
            }

            window.updateInvoiceSummary = function() {
                const select = document.getElementById('paket_program');
                if (!select) return;
                const selectedOpt = select.options[select.selectedIndex];
                const biayaProgram = selectedOpt ? (parseInt(selectedOpt.getAttribute('data-biaya-program')) || 0) : 0;
                const biayaReg = selectedOpt ? (parseInt(selectedOpt.getAttribute('data-biaya-reg')) || 100000) : 100000;
                const total = biayaReg + biayaProgram;

                const elBiayaReg = document.getElementById('summary-biaya-reg');
                const elBiayaProg = document.getElementById('summary-biaya-program');
                const elTotal = document.getElementById('summary-total-biaya');

                if (elBiayaReg) elBiayaReg.innerText = 'Rp ' + biayaReg.toLocaleString('id-ID');
                if (elBiayaProg) elBiayaProg.innerText = 'Rp ' + biayaProgram.toLocaleString('id-ID');
                if (elTotal) elTotal.innerText = 'Rp ' + total.toLocaleString('id-ID');
            };

            updateInvoiceSummary();
        </script>
    @endif

    <p class="mt-6 text-center text-xs text-slate-500 font-medium">
        Sudah punya akun petugas?
        <a href="{{ route('login') }}" class="font-bold text-emerald-700 hover:underline">Masuk di sini</a>
    </p>
    </div> <!-- End max-w-3xl wrapper -->
    </div> <!-- End Right Column (60%) -->
</div> <!-- End Page Container -->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-pendaftaran');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Biarkan form tersubmit secara native ke server, 
                // tapi tampilkan SweetAlert loading screen sementara memproses
                Swal.fire({
                    title: 'Memproses Pendaftaran...',
                    html: 'Mohon tunggu sebentar, data sedang dikirim.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            });
        }
    });
</script>

</body>
</html>
