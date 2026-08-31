<x-layouts::app title="Verifikasi Kehadiran Peserta On-Site">
    @php
        $angkatanList = \App\Models\Angkatan::orderByDesc('tahun')->get();
        $angkatanId = request('angkatan_id') ?: $angkatanList->firstWhere('status', 'berjalan')?->id;

        $query = \App\Models\Pendaftaran::with(['peserta', 'angkatan', 'verifikatorKehadiran']);
        if ($angkatanId) {
            $query->where('angkatan_id', $angkatanId);
        }

        if (request('kehadiran')) {
            $query->where('status_kehadiran', request('kehadiran'));
        }

        if (request('q')) {
            $keyword = '%'.request('q').'%';
            $query->where(function ($q) use ($keyword) {
                $q->where('kode_pendaftaran', 'like', $keyword)
                  ->orWhere('nomor_induk', 'like', $keyword)
                  ->orWhereHas('peserta', fn ($qp) => $qp
                      ->where('nama', 'like', $keyword)
                      ->orWhere('nik', 'like', $keyword)
                      ->orWhere('kabupaten_kota', 'like', $keyword)
                      ->orWhere('provinsi', 'like', $keyword)
                      ->orWhere('negara', 'like', $keyword)
                      ->orWhere('no_hp', 'like', $keyword)
                      ->orWhere('email', 'like', $keyword));
            });
        }

        $listPendaftaran = $query->latest('didaftarkan_pada')->paginate(20)->withQueryString();

        // Stats calculation
        $baseStatsQuery = \App\Models\Pendaftaran::query();
        if ($angkatanId) $baseStatsQuery->where('angkatan_id', $angkatanId);

        $totalPendaftar = (clone $baseStatsQuery)->count();
        $totalHadir = (clone $baseStatsQuery)->where('status_kehadiran', 'hadir')->count();
        $totalBelumHadir = $totalPendaftar - $totalHadir;
        $persenHadir = $totalPendaftar > 0 ? round(($totalHadir / $totalPendaftar) * 100) : 0;
    @endphp

    <x-page-header title="Verifikasi Kehadiran Peserta (Presensi On-Site)"
                   subtitle="Arahkan QR Code ke kamera di bawah ini atau gunakan input manual untuk konfirmasi kedatangan.">
        <x-slot:actions>
            <x-button :href="route('pendaftaran.index')" variant="secondary">
                <x-icon name="arrow-left" class="size-4 inline mr-1" />
                <span>Kembali ke Daftar Pendaftaran</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <x-alert />

    <!-- ==================================================================== -->
    <!-- BAGIAN 1 (PALING ATAS): HERO SCANNER KAMERA & INPUT CEPAT           -->
    <!-- ==================================================================== -->
    <div class="mb-8 grid gap-6 lg:grid-cols-12">
        <!-- Kolom Kamera Scanner (Lg: 7/12) -->
        <div class="lg:col-span-7">
            <div style="background-color: #0f172a; border-color: #1e293b; color: #ffffff;"
                 class="relative overflow-hidden rounded-3xl border p-5 shadow-2xl">

                <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span style="background-color: #059669; color: #ffffff;"
                              class="grid size-9 place-items-center rounded-xl font-bold shadow-md">
                            <x-icon name="qr-code" class="size-5" />
                        </span>
                        <div>
                            <h3 style="color: #ffffff;" class="text-sm font-bold tracking-tight">Pemindai QR Code</h3>
                            <p style="color: #94a3b8;" class="text-[11px]">Kamera Pemindai Presensi</p>
                        </div>
                    </div>

                    <div id="camera-status-badge" style="background-color: #1e293b; color: #94a3b8; border-color: #334155;"
                         class="flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold">
                        <span class="size-2 rounded-full bg-slate-500"></span>
                        <span id="camera-status-text">Kamera Siap</span>
                    </div>
                </div>

                {{-- Pilihan Perangkat Kamera --}}
                <div class="mb-4">
                    <select id="select-camera"
                            style="background-color: #1e293b; color: #f8fafc; border-color: #334155;"
                            class="w-full rounded-xl border px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                        <option value="">Mengidentifikasi Kamera…</option>
                    </select>
                </div>

                {{-- Area Viewport Kamera (Restricted Max Width & Height) --}}
                <div class="mx-auto max-w-md overflow-hidden rounded-2xl border border-slate-700 bg-slate-950 p-2 shadow-inner">
                    <div id="qr-reader" class="w-full overflow-hidden rounded-xl"></div>
                    <div id="qr-reader-placeholder" class="grid h-48 place-items-center text-center text-slate-500">
                        <div>
                            <x-icon name="qr-code" class="mx-auto size-12 opacity-40 mb-2" />
                            <p class="text-xs font-medium">Klik tombol di bawah untuk mengaktifkan pemindai</p>
                        </div>
                    </div>
                </div>

                {{-- Action Controls --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" id="btn-start-camera"
                            style="background-color: #059669; color: #ffffff;"
                            class="flex-1 rounded-xl py-2.5 text-xs font-bold shadow-lg transition-all hover:bg-emerald-600">
                        Aktifkan Kamera Pemindai
                    </button>
                    <button type="button" id="btn-stop-camera"
                            style="background-color: #334155; color: #ffffff;"
                            class="hidden flex-1 rounded-xl py-2.5 text-xs font-bold transition-all hover:bg-slate-700">
                        Matikan Kamera
                    </button>
                </div>

                <div id="camera-error-banner" style="background-color: #451a03; border-color: #78350f; color: #fef3c7;"
                     class="mt-3 hidden rounded-xl border p-3 text-xs">
                    <p id="camera-error-msg" class="font-semibold">Gagal mengakses kamera.</p>
                </div>
            </div>
        </div>

        <!-- Kolom Input Manual Cepat (Lg: 5/12) -->
        <div class="flex flex-col justify-between space-y-4 lg:col-span-5">
            <div style="background-color: #ffffff; border-color: #e2e8f0; color: #0f172a;"
                 class="rounded-3xl border p-6 shadow-xl">
                <div class="mb-4 flex items-center gap-2">
                    <span style="background-color: #1e293b; color: #ffffff;"
                          class="grid size-9 place-items-center rounded-xl font-bold">
                        <x-icon name="pencil" class="size-5" />
                    </span>
                    <div>
                        <h3 style="color: #0f172a;" class="text-sm font-bold">Input Cepat Manual</h3>
                        <p style="color: #64748b;" class="text-[11px]">Ketik Kode Pendaftaran atau NIK</p>
                    </div>
                </div>

                <form id="form-manual-presensi" onsubmit="handleManualSubmit(event)" class="space-y-4">
                    <div>
                        <label style="color: #475569;" class="mb-1.5 block text-xs font-bold uppercase tracking-wider">
                            Kode Pendaftaran / NIK Santri
                        </label>
                        <input type="text" id="input-kode-qr" placeholder="REG-2026-XXXX / 3208XXXXXXXXXXXX" autofocus
                               style="background-color: #f8fafc; color: #0f172a; border-color: #cbd5e1;"
                               class="block w-full rounded-2xl border px-4 py-3.5 font-mono text-base uppercase tracking-wider shadow-inner focus:border-emerald-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-600/30">
                    </div>
                    <button type="submit"
                            style="background-color: #0f172a; color: #ffffff;"
                            class="w-full rounded-2xl py-3.5 text-xs font-extrabold shadow-lg transition-all hover:bg-slate-800 inline-flex items-center justify-center gap-2">
                        <x-icon name="bolt" class="size-4 text-emerald-400" />
                        <span>Proses Presensi Kehadiran</span>
                    </button>
                </form>
            </div>

            <!-- Result Card (Pop-up Sukses Presensi Realtime) -->
            <div id="result-card" style="background-color: #065f46; border-color: #10b981; color: #ffffff;"
                 class="hidden rounded-3xl border-2 p-6 shadow-2xl transition-all">
                <div class="flex items-start gap-4">
                    <span style="background-color: #ffffff; color: #047857;"
                          class="grid size-12 shrink-0 place-items-center rounded-2xl shadow-md">
                        <x-icon name="check-circle" class="size-8" />
                    </span>
                    <div class="flex-1">
                        <span style="background-color: rgba(16, 185, 129, 0.3); color: #ecfdf5; border: 1px solid rgba(52, 211, 153, 0.4);"
                              class="inline-block rounded-full px-3 py-0.5 text-[10px] font-extrabold uppercase tracking-widest">
                            PRESENSI SUCCESSFUL
                        </span>
                        <h4 id="result-nama" style="color: #ffffff;" class="mt-1 text-xl font-black">Nama Santri</h4>
                        <p id="result-detail" style="color: #d1fae5;" class="mt-0.5 text-xs font-semibold">REG-2026-0001 · Laki-laki</p>
                    </div>
                </div>
                <div style="background-color: rgba(2, 44, 34, 0.6); color: #ecfdf5; border: 1px solid rgba(16, 185, 129, 0.3);"
                     class="mt-4 rounded-2xl px-4 py-3 text-xs flex items-center gap-1.5">
                    <x-icon name="check-badge" class="size-4 text-emerald-400" />
                    <span>Dikonfirmasi Hadir: <span id="result-waktu" style="color: #ffffff;" class="font-extrabold">27 Aug 2026, 17:45</span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- BAGIAN 2: RINGKASAN STATISTIK KEHADIRAN                             -->
    <!-- ==================================================================== -->
    <div class="mb-8 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Card 1: Total Terdaftar -->
        <div style="background-color: #0f172a; border-color: #1e293b; color: #ffffff;"
             class="relative overflow-hidden rounded-2xl border p-5 shadow-md">
            <div class="flex items-center justify-between">
                <p style="color: #cbd5e1;" class="text-xs font-bold uppercase tracking-wider">Total Terdaftar</p>
                <span style="background-color: #1e293b; color: #ffffff;" class="rounded-xl p-2">
                    <x-icon name="users" class="size-4" />
                </span>
            </div>
            <p style="color: #ffffff;" class="mt-2 text-3xl font-black tracking-tight">{{ number_format($totalPendaftar) }}</p>
            <p style="color: #94a3b8;" class="mt-1 text-xs font-medium">Peserta angkatan terpilih</p>
        </div>

        <!-- Card 2: Total Hadir On-Site -->
        <div style="background-color: #047857; border-color: #059669; color: #ffffff;"
             class="relative overflow-hidden rounded-2xl border p-5 shadow-md">
            <div class="flex items-center justify-between">
                <p style="color: #d1fae5;" class="text-xs font-bold uppercase tracking-wider">Total Hadir On-Site</p>
                <span style="background-color: #065f46; color: #ffffff;" class="rounded-xl p-2">
                    <x-icon name="check-circle" class="size-4" />
                </span>
            </div>
            <p style="color: #ffffff;" class="mt-2 text-3xl font-black tracking-tight">{{ number_format($totalHadir) }}</p>
            <p style="color: #ecfdf5;" class="mt-1 text-xs font-bold">Capaian: {{ $persenHadir }}% dari total santri</p>
        </div>

        <!-- Card 3: Belum Hadir -->
        <div style="background-color: #d97706; border-color: #f59e0b; color: #ffffff;"
             class="relative overflow-hidden rounded-2xl border p-5 shadow-md">
            <div class="flex items-center justify-between">
                <p style="color: #fef3c7;" class="text-xs font-bold uppercase tracking-wider">Belum Hadir</p>
                <span style="background-color: #b45309; color: #ffffff;" class="rounded-xl p-2">
                    <x-icon name="info" class="size-4" />
                </span>
            </div>
            <p style="color: #ffffff;" class="mt-2 text-3xl font-black tracking-tight">{{ number_format($totalBelumHadir) }}</p>
            <p style="color: #fffbeb;" class="mt-1 text-xs font-bold">Menunggu kedatangan</p>
        </div>

        <!-- Card 4: Status Pemindai -->
        <div style="background-color: #ecfdf5; border-color: #a7f3d0; color: #064e3b;"
             class="relative overflow-hidden rounded-2xl border p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p style="color: #065f46;" class="text-xs font-bold uppercase tracking-wider">Status Pemindai</p>
                <span style="background-color: #a7f3d0; color: #064e3b;" class="rounded-xl p-2">
                    <x-icon name="camera" class="size-4" />
                </span>
            </div>
            <p id="scanner-status" style="color: #022c22;" class="mt-2 text-base font-extrabold">Siap Digunakan</p>
            <p style="color: #047857;" class="mt-1 text-xs font-semibold">Kamera & Barcode Aktif</p>
        </div>
    </div>

    <!-- ==================================================================== -->
    <!-- BAGIAN 3: TABEL DAFTAR PESERTA & FILTER                              -->
    <!-- ==================================================================== -->
    <div style="background-color: #ffffff; border-color: #e2e8f0; color: #0f172a;"
         class="overflow-hidden rounded-3xl border shadow-sm">
        <!-- Filter Bar -->
        <form method="GET" style="background-color: #f8fafc; border-color: #e2e8f0;"
              class="flex flex-wrap items-center justify-between gap-4 border-b p-5">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label style="color: #0f172a;" class="text-xs font-bold">Angkatan:</label>
                    <select name="angkatan_id" onchange="this.form.submit()"
                            style="background-color: #ffffff; color: #0f172a; border-color: #cbd5e1;"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600">
                        @foreach ($angkatanList as $akt)
                            <option value="{{ $akt->id }}" @selected($angkatanId == $akt->id)>{{ $akt->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <label style="color: #0f172a;" class="text-xs font-bold">Status:</label>
                    <select name="kehadiran" onchange="this.form.submit()"
                            style="background-color: #ffffff; color: #0f172a; border-color: #cbd5e1;"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600">
                        <option value="">Semua Presensi</option>
                        <option value="belum_hadir" @selected(request('kehadiran') === 'belum_hadir')>Belum Hadir</option>
                        <option value="hadir" @selected(request('kehadiran') === 'hadir')>Sudah Hadir</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari Nama / NIK / Kode..."
                       style="background-color: #ffffff; color: #0f172a; border-color: #cbd5e1;"
                       class="rounded-xl border px-3.5 py-2 text-xs shadow-sm focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600">
                <x-button size="sm">Cari</x-button>
            </div>
        </form>

        <!-- Tabel Peserta Presensi -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead style="background-color: #f1f5f9; color: #0f172a;" class="border-b border-slate-200 text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-4">Nama Santri / Peserta</th>
                        <th class="px-5 py-4">Kode Pendaftaran & NIK</th>
                        <th class="px-5 py-4">Status Kehadiran</th>
                        <th class="px-5 py-4 text-right">Aksi Konfirmasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($listPendaftaran as $item)
                        <tr class="hover:bg-slate-50/90 transition-all">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span style="background-color: #d1fae5; color: #065f46;"
                                          class="grid size-10 shrink-0 place-items-center rounded-2xl text-xs font-black">
                                        {{ $item->peserta->initials }}
                                    </span>
                                    <div>
                                        <p style="color: #0f172a;" class="font-extrabold">{{ $item->peserta->nama }}</p>
                                        <p style="color: #475569;" class="text-xs font-medium">{{ $item->peserta->jenis_kelamin_label }} · {{ $item->peserta->kabupaten_kota ?: 'Domisili -' }}</p>
                                        <div class="mt-1 flex items-center gap-1.5 flex-wrap text-[10px]">
                                            <span class="rounded-full px-2 py-0.5 font-bold border {{ $item->status_pembayaran_pendaftaran === 'lunas' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-amber-100 text-amber-900 border-amber-300' }}">
                                                Reg: {{ $item->status_pembayaran_pendaftaran === 'lunas' ? 'Lunas' : 'Belum' }} (Rp 100rb)
                                            </span>
                                            <span class="rounded-full px-2 py-0.5 font-bold border bg-slate-100 text-slate-800 border-slate-300">
                                                Prog: {{ $item->status_pembayaran_program === 'lunas' ? 'Lunas' : ($item->status_pembayaran_program === 'dp_sebagian' ? 'DP' : 'Belum') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p style="color: #065f46;" class="font-mono text-xs font-bold">{{ $item->kode_pendaftaran }}</p>
                                <p style="color: #64748b;" class="font-mono text-xs">NIK: {{ $item->peserta->nik }}</p>
                            </td>

                            <td class="px-5 py-4">
                                @if ($item->status_kehadiran === 'hadir')
                                    <span style="background-color: #d1fae5; color: #065f46; border-color: #6ee7b7;"
                                          class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold border">
                                        ✓ HADIR ON-SITE
                                    </span>
                                    <p style="color: #64748b;" class="mt-1 text-[11px] font-semibold">
                                        {{ $item->waktu_kehadiran?->translatedFormat('H:i, d M Y') }}
                                    </p>
                                @else
                                    <span style="background-color: #f1f5f9; color: #334155; border-color: #cbd5e1;"
                                          class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold border">
                                        Belum Hadir
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right">
                                @if ($item->status_kehadiran !== 'hadir')
                                    <form method="POST" action="{{ route('pendaftaran.konfirmasi-kehadiran', $item) }}" class="inline">
                                        @csrf
                                        <x-button size="sm" type="button" onclick="konfirmasiHadirManual(this.closest('form'), '{{ addslashes($item->peserta->nama) }}')">Konfirmasi Hadir</x-button>
                                    </form>
                                @else
                                    <span style="color: #047857;" class="text-xs font-extrabold">✓ Terkonfirmasi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="color: #64748b;" class="p-10 text-center">
                                Tidak ada data pendaftaran yang sesuai dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($listPendaftaran->hasPages())
            <div style="border-top-color: #e2e8f0;" class="border-t p-5">
                {{ $listPendaftaran->links() }}
            </div>
        @endif
    </div>

    <!-- Pustaka Camera QR Code Scanner -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        let html5QrCode = null;
        let isCameraActive = false;
        let availableCameras = [];

        function konfirmasiHadirManual(form, namaSantri) {
            Swal.fire({
                title: 'Konfirmasi Kehadiran',
                html: `Tandai <strong>${namaSantri}</strong> sebagai HADIR di lokasi?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hadir!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Menyimpan data kehadiran',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading() }
                    });
                    form.submit();
                }
            });
        }

        function playSuccessSound() {
            try {
                const audio = new Audio("{{ asset('sounds/success.mp3') }}");
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => playSynthesizedChime(true));
                }
            } catch (e) {
                playSynthesizedChime(true);
            }
        }

        function playFailedSound() {
            try {
                const audio = new Audio("{{ asset('sounds/failed.mp3') }}");
                const playPromise = audio.play();
                if (playPromise !== undefined) {
                    playPromise.catch(() => playSynthesizedChime(false));
                }
            } catch (e) {
                playSynthesizedChime(false);
            }
        }

        function playSynthesizedChime(isSuccess = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);

                if (isSuccess) {
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                    osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1);
                    gain.gain.setValueAtTime(0.2, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.35);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, ctx.currentTime);
                    osc.frequency.setValueAtTime(160, ctx.currentTime + 0.15);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.4);
                }
            } catch (e) {}
        }

        function processCodeScan(code) {
            if (!code) return;

            fetch("{{ route('pendaftaran.konfirmasi-kehadiran') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ kode: code.trim() })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    playSuccessSound();

                    const resCard = document.getElementById('result-card');
                    document.getElementById('result-nama').innerText = data.data.nama;
                    document.getElementById('result-detail').innerText = `${data.data.kode_pendaftaran} · ${data.data.jenis_kelamin} (${data.data.angkatan || ''})`;
                    document.getElementById('result-waktu').innerText = data.data.waktu_kehadiran;

                    resCard.classList.remove('hidden');

                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    playFailedSound();
                    alert(data.message || "Data tidak ditemukan");
                }
            })
            .catch(err => {
                console.error(err);
                playFailedSound();
                alert("Gagal memproses presensi.");
            });
        }

        function submitScanManual(e) {
            e.preventDefault();
            const input = document.getElementById('input-kode-qr');
            if (input && input.value.trim()) {
                processCodeScan(input.value.trim());
                input.value = '';
            }
        }

        function showCameraError(msg) {
            const errBanner = document.getElementById('camera-error-banner');
            const errText = document.getElementById('camera-error-msg');
            if (errText) errText.innerText = msg;
            if (errBanner) errBanner.classList.remove('hidden');
        }

        function hideCameraError() {
            const errBanner = document.getElementById('camera-error-banner');
            if (errBanner) errBanner.classList.add('hidden');
        }

        function startScannerWithDevice(cameraIdOrConstraint) {
            const statusEl = document.getElementById('scanner-status');
            const badgeEl = document.getElementById('camera-status-badge');
            const placeholder = document.getElementById('qr-reader-placeholder');

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("qr-reader");
            }

            const config = { fps: 10, qrbox: { width: 170, height: 170 } };

            html5QrCode.start(
                cameraIdOrConstraint,
                config,
                (decodedText) => {
                    processCodeScan(decodedText);
                },
                () => {}
            ).then(() => {
                isCameraActive = true;
                hideCameraError();
                if (statusEl) statusEl.innerText = "🎥 Aktif Memindai";
                if (badgeEl) {
                    badgeEl.style.backgroundColor = "rgba(16, 185, 129, 0.2)";
                    badgeEl.style.color = "#34d399";
                    badgeEl.style.borderColor = "rgba(16, 185, 129, 0.4)";
                    badgeEl.innerHTML = '<span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span> 🟢 Kamera Aktif';
                }
                if (placeholder) placeholder.classList.add('hidden');
            }).catch(err => {
                console.warn("Failed starting camera with specific device, retrying user media fallback...", err);

                html5QrCode.start(
                    { facingMode: "user" },
                    config,
                    (decodedText) => processCodeScan(decodedText),
                    () => {}
                ).then(() => {
                    isCameraActive = true;
                    hideCameraError();
                    if (statusEl) statusEl.innerText = "Aktif Memindai";
                    if (badgeEl) {
                        badgeEl.style.backgroundColor = "rgba(16, 185, 129, 0.2)";
                        badgeEl.style.color = "#34d399";
                        badgeEl.style.borderColor = "rgba(16, 185, 129, 0.4)";
                        badgeEl.innerHTML = '<span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span> Kamera Aktif';
                    }
                    if (placeholder) placeholder.classList.add('hidden');
                }).catch(err2 => {
                    console.error("Camera access error:", err2);
                    showCameraError("Gagal membuka stream kamera: " + (err2.message || err2));
                });
            });
        }

        function startCameraScanner() {
            hideCameraError();

            if (typeof Html5Qrcode === 'undefined') {
                showCameraError("Pustaka Html5Qrcode belum selesai dimuat.");
                return;
            }

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length > 0) {
                    availableCameras = devices;

                    const selectEl = document.getElementById('select-camera');
                    const selectWrapper = document.getElementById('camera-select-wrapper');

                    if (selectEl) {
                        selectEl.innerHTML = '';
                        devices.forEach((dev, idx) => {
                            const opt = document.createElement('option');
                            opt.value = dev.id;
                            opt.innerText = dev.label || `Kamera ${idx + 1}`;
                            selectEl.appendChild(opt);
                        });
                        if (selectWrapper) selectWrapper.classList.remove('hidden');
                    }

                    let selectedCam = devices[0].id;
                    const backCam = devices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear'));
                    if (backCam) {
                        selectedCam = backCam.id;
                        if (selectEl) selectEl.value = backCam.id;
                    }

                    startScannerWithDevice(selectedCam);
                } else {
                    startScannerWithDevice({ facingMode: "environment" });
                }
            }).catch(err => {
                console.warn("getCameras failed, falling back to facingMode...", err);
                startScannerWithDevice({ facingMode: "environment" });
            });
        }

        function onCameraSelected() {
            const selectEl = document.getElementById('select-camera');
            if (selectEl && selectEl.value) {
                if (isCameraActive && html5QrCode) {
                    html5QrCode.stop().then(() => {
                        isCameraActive = false;
                        startScannerWithDevice(selectEl.value);
                    });
                } else {
                    startScannerWithDevice(selectEl.value);
                }
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            const btnStart = document.getElementById('btn-start-camera');
            const btnStop = document.getElementById('btn-stop-camera');
            const selectCamera = document.getElementById('select-camera');

            if (btnStart) {
                btnStart.addEventListener('click', () => {
                    startCameraScanner();
                    btnStart.classList.add('hidden');
                    if (btnStop) btnStop.classList.remove('hidden');
                });
            }

            if (btnStop) {
                btnStop.addEventListener('click', () => {
                    if (html5QrCode && isCameraActive) {
                        html5QrCode.stop().then(() => {
                            isCameraActive = false;
                            btnStop.classList.add('hidden');
                            if (btnStart) btnStart.classList.remove('hidden');
                            
                            const statusEl = document.getElementById('scanner-status');
                            if (statusEl) statusEl.innerText = "Kamera Dimatikan";
                            
                            const badgeEl = document.getElementById('camera-status-badge');
                            if (badgeEl) {
                                badgeEl.style.backgroundColor = "#1e293b";
                                badgeEl.style.color = "#94a3b8";
                                badgeEl.style.borderColor = "#334155";
                                badgeEl.innerHTML = '<span class="size-2 rounded-full bg-slate-500"></span> <span>Kamera Siap</span>';
                            }
                            
                            const placeholder = document.getElementById('qr-reader-placeholder');
                            if (placeholder) placeholder.classList.remove('hidden');
                        }).catch(err => {
                            console.error("Gagal mematikan kamera", err);
                        });
                    }
                });
            }

            if (selectCamera) {
                selectCamera.addEventListener('change', onCameraSelected);
            }
        });
    </script>
</x-layouts::app>
