<!-- Pimpinan Widgets Partial -->

    <!-- 1. KPI Cards (Key Performance Indicators) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Total Pendapatan -->
        <div class="rounded-xl p-5 flex items-center gap-4 bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-md shadow-emerald-500/20">
            <div class="rounded-full bg-white/20 p-4 text-white">
                <x-icon name="currency-dollar" class="h-8 w-8" />
            </div>
            <div>
                <p class="text-sm font-medium text-emerald-50 uppercase tracking-wide">Pemasukan Valid</p>
                <p class="text-2xl font-bold text-white mt-0.5">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Total Piutang/Tunggakan -->
        <div class="rounded-xl p-5 flex items-center gap-4 bg-gradient-to-br from-rose-500 to-rose-600 shadow-md shadow-rose-500/20">
            <div class="rounded-full bg-white/20 p-4 text-white">
                <x-icon name="exclamation-circle" class="h-8 w-8" />
            </div>
            <div>
                <p class="text-sm font-medium text-rose-50 uppercase tracking-wide">Total Tunggakan</p>
                <p class="text-2xl font-bold text-white mt-0.5">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Total Santri -->
        <div class="rounded-xl p-5 flex items-center gap-4 bg-gradient-to-br from-blue-500 to-blue-600 shadow-md shadow-blue-500/20">
            <div class="rounded-full bg-white/20 p-4 text-white">
                <x-icon name="users" class="h-8 w-8" />
            </div>
            <div>
                <p class="text-sm font-medium text-blue-50 uppercase tracking-wide">Total Peserta Aktif</p>
                <p class="text-2xl font-bold text-white mt-0.5">{{ number_format($totalPeserta) }} Santri</p>
            </div>
        </div>

        <!-- Total Halaqah & Muhaffizh -->
        <div class="rounded-xl p-5 flex items-center gap-4 bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-md shadow-indigo-500/20">
            <div class="rounded-full bg-white/20 p-4 text-white">
                <x-icon name="book-open" class="h-8 w-8" />
            </div>
            <div>
                <p class="text-sm font-medium text-indigo-50 uppercase tracking-wide">Halaqah Aktif</p>
                <p class="text-2xl font-bold text-white mt-0.5">{{ number_format($totalHalaqah) }} Kelas</p>
                <p class="text-xs font-medium text-indigo-100 mt-0.5">{{ number_format($totalMuhaffizh) }} Pengajar</p>
            </div>
        </div>
    </div>

    <!-- 2. Charts Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Tren Pendaftaran Chart -->
        <x-card class="col-span-1 lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Tren Pendaftaran Bulanan</h3>
            <p class="text-sm text-slate-500 mb-6">Pertumbuhan peserta dalam 6 bulan terakhir</p>
            <div class="relative w-full h-72">
                <canvas id="pendaftaranChart"></canvas>
            </div>
        </x-card>

        <!-- Gender Doughnut Chart -->
        <x-card class="col-span-1">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Rasio Demografi</h3>
            <p class="text-sm text-slate-500 mb-6">Perbandingan gender pendaftar</p>
            <div class="relative w-full h-72 flex items-center justify-center">
                <canvas id="demografiChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- 2b. Secondary Charts Area -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Wilayah Bar Chart -->
        <x-card class="col-span-1 lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Sebaran Wilayah Asal</h3>
            <p class="text-sm text-slate-500 mb-6">Top daerah asal peserta karantina</p>
            <div class="relative w-full h-72">
                <canvas id="wilayahChart"></canvas>
            </div>
        </x-card>

        <!-- Usia Polar/Doughnut Chart -->
        <x-card class="col-span-1">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Distribusi Usia</h3>
            <p class="text-sm text-slate-500 mb-6">Kelompok umur peserta</p>
            <div class="relative w-full h-72 flex items-center justify-center">
                <canvas id="usiaChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- 2c. Operational Charts Area -->
    <div class="grid grid-cols-1 mb-8">
        <!-- Beban Muhaffizh Horizontal Bar Chart -->
        <x-card class="w-full">
            <h3 class="text-lg font-semibold text-slate-900 mb-1">Sebaran Santri per Muhaffizh (Beban Mengajar)</h3>
            <p class="text-sm text-slate-500 mb-6">Distribusi jumlah santri aktif untuk setiap pengajar</p>
            <div class="relative w-full h-80">
                <canvas id="muhaffizhChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- 3. Progress Harian (Setoran Terbaru) & Pendaftaran Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Progress Harian Peserta -->
        <x-card padding="p-0" class="flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <x-icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">Progress Hafalan Harian (Real-Time)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Santri</th>
                            <th class="px-5 py-3 font-medium">Hafalan Baru</th>
                            <th class="px-5 py-3 font-medium text-right">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($setoranTerbaru as $setoran)
                            <tr class="tabel-baris hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900 dark:text-slate-200">{{ $setoran->anggotaHalaqah?->pendaftaran?->peserta?->nama ?? '-' }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Muhaffizh: {{ $setoran->anggotaHalaqah?->halaqah?->muhaffizh?->nama ?? '-' }}</div>
                                </td>
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-300">
                                    {{ $setoran->batas_baru }} <br>
                                    <span class="text-xs bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-1.5 py-0.5 rounded">{{ $setoran->nilai }}</span>
                                </td>
                                <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400 text-xs whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($setoran->created_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada progress hafalan harian.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Pendaftar Terbaru -->
        <x-card padding="p-0" class="flex flex-col">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-2">
                <x-icon name="user-plus" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">Pendaftar Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nama Peserta</th>
                            <th class="px-5 py-3 font-medium">Angkatan</th>
                            <th class="px-5 py-3 font-medium text-right">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($pendaftarTerbaru as $daftar)
                            <tr class="tabel-baris hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-300 font-semibold shrink-0">
                                            {{ substr($daftar->peserta?->nama ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-slate-900 dark:text-slate-200">{{ $daftar->peserta?->nama ?? 'Terhapus' }}</div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $daftar->peserta?->jenis_kelamin == 'L' ? 'Ikhwan' : 'Akhwat' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-slate-700 dark:text-slate-300">{{ $daftar->angkatan?->nama ?? '-' }}</td>
                                <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400 text-xs">
                                    {{ \Carbon\Carbon::parse($daftar->didaftarkan_pada)->translatedFormat('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-8 text-center text-slate-500 dark:text-slate-400">Belum ada peserta terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Inisialisasi Chart -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data untuk Tren Pendaftaran Bulanan
            const labelBulan = {!! json_encode($chartPendaftaran->pluck('bulan')->map(function($val) { 
                return \Carbon\Carbon::parse($val . '-01')->translatedFormat('M Y'); 
            })) !!};
            const dataPendaftaran = {!! json_encode($chartPendaftaran->pluck('total')) !!};

            const ctxLine = document.getElementById('pendaftaranChart').getContext('2d');
            
            // Buat gradient garis untuk kesan elegan
            let gradientLine = ctxLine.createLinearGradient(0, 0, 0, 400);
            gradientLine.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
            gradientLine.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            const chartPendaftaran = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: labelBulan,
                    datasets: [{
                        label: 'Peserta Baru',
                        data: dataPendaftaran,
                        borderColor: '#10b981', // Emerald 500
                        backgroundColor: gradientLine,
                        borderWidth: 3,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4 // Garis melengkung halus
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleFont: { size: 13, family: 'Inter' },
                            bodyFont: { size: 14, family: 'Inter' },
                            displayColors: false,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter', size: 12 },
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter', size: 12 }
                            }
                        }
                    }
                }
            });

            // Data untuk Doughnut Demografi
            const ikhwan = {{ $ikhwan }};
            const akhwat = {{ $akhwat }};

            const ctxDoughnut = document.getElementById('demografiChart').getContext('2d');
            const chartDemografi = new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: ['Ikhwan', 'Akhwat'],
                    datasets: [{
                        data: [ikhwan, akhwat],
                        backgroundColor: [
                            '#3b82f6', // Blue 500
                            '#ec4899', // Pink 500
                        ],
                        hoverBackgroundColor: [
                            '#2563eb', 
                            '#db2777', 
                        ],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '75%', // Lubang tengah yang elegan
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: '#475569',
                                font: { family: 'Inter', size: 13 }
                            }
                        }
                    }
                }
            });

            // Data untuk Bar Chart Wilayah
            const labelWilayah = {!! json_encode($chartWilayah->pluck('tempat_lahir')) !!};
            const dataWilayah = {!! json_encode($chartWilayah->pluck('total')) !!};

            const ctxWilayah = document.getElementById('wilayahChart').getContext('2d');
            const chartWilayah = new Chart(ctxWilayah, {
                type: 'bar',
                data: {
                    labels: labelWilayah,
                    datasets: [{
                        label: 'Jumlah Peserta',
                        data: dataWilayah,
                        backgroundColor: '#8b5cf6', // Violet 500
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter', size: 12 },
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#64748b', font: { family: 'Inter', size: 12 } }
                        }
                    }
                }
            });

            // Data untuk Doughnut Usia
            const ctxUsia = document.getElementById('usiaChart').getContext('2d');
            const chartUsia = new Chart(ctxUsia, {
                type: 'doughnut',
                data: {
                    labels: ['< 17 (Remaja)', '17-25 (Pemuda)', '26-35 (Dewasa)', '> 35 (Tua)'],
                    datasets: [{
                        data: [
                            {{ $dataUsia->usia_remaja ?? 0 }}, 
                            {{ $dataUsia->usia_pemuda ?? 0 }}, 
                            {{ $dataUsia->usia_dewasa ?? 0 }}, 
                            {{ $dataUsia->usia_tua ?? 0 }}
                        ],
                        backgroundColor: ['#f59e0b', '#10b981', '#3b82f6', '#64748b'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                color: '#475569',
                                font: { family: 'Inter', size: 12 }
                            }
                        }
                    }
                }
            });
            // Data untuk Beban Muhaffizh (Horizontal Bar)
            const labelMuhaffizh = {!! json_encode($bebanMuhaffizh->pluck('nama')->map(fn($nama) => Str::limit($nama, 15))) !!};
            const dataBeban = {!! json_encode($bebanMuhaffizh->pluck('total_santri')) !!};

            const ctxMuhaffizh = document.getElementById('muhaffizhChart').getContext('2d');
            const chartMuhaffizh = new Chart(ctxMuhaffizh, {
                type: 'bar',
                data: {
                    labels: labelMuhaffizh,
                    datasets: [{
                        label: 'Jumlah Santri',
                        data: dataBeban,
                        backgroundColor: '#f43f5e', // Rose 500
                        borderRadius: 6,
                        barPercentage: 0.7
                    }]
                },
                options: {
                    indexAxis: 'y', // Membalik sumbu untuk Horizontal Bar
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9',
                                drawBorder: false,
                            },
                            ticks: {
                                color: '#64748b',
                                font: { family: 'Inter', size: 12 },
                                stepSize: 1
                            }
                        },
                        y: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#475569', font: { family: 'Inter', size: 12, weight: '500' } }
                        }
                    }
                }
            });

            // Sinkronisasi warna grid saat tema berubah
            const charts = [chartPendaftaran, chartDemografi, chartWilayah, chartUsia, chartMuhaffizh];
            
            window.addEventListener('theme-changed', (e) => {
                const isDark = e.detail.isDark;
                const gridColor = isDark ? '#334155' : '#f1f5f9';
                const tickColor = isDark ? '#94a3b8' : '#64748b';
                
                charts.forEach(c => {
                    if (c.options.scales?.x) {
                        if (c.options.scales.x.grid) c.options.scales.x.grid.color = gridColor;
                        if (c.options.scales.x.ticks) c.options.scales.x.ticks.color = tickColor;
                    }
                    if (c.options.scales?.y) {
                        if (c.options.scales.y.grid) c.options.scales.y.grid.color = gridColor;
                        if (c.options.scales.y.ticks) c.options.scales.y.ticks.color = tickColor;
                    }
                    if (c.options.plugins?.legend?.labels) {
                        c.options.plugins.legend.labels.color = tickColor;
                    }
                    c.update();
                });
            });

            // Pemicu awal agar chart mengikuti tema saat dimuat
            if (document.documentElement.classList.contains('dark')) {
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: true } }));
            }
        });
    </script>

