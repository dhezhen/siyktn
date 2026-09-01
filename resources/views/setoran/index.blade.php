<x-layouts::app :title="'Setoran Hafalan'">
    <x-page-header title="Setoran Hafalan" subtitle="Catatan hafalan santri dalam satuan halaman.">
        <x-slot:actions>
            @can('setoran.export')
                <x-button :href="route('setoran.export', request()->only('halaqah_id'))" variant="secondary" icon="download">
                    Ekspor CSV
                </x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        @foreach ($rekap as $label => $nilai)
            <x-card padding="p-5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $nilai }}</p>
            </x-card>
        @endforeach
    </div>

    @if(isset($grafik) && $grafik->isNotEmpty())
        <x-card padding="p-5" class="mb-4">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Grafik Setoran {{ $santriGrafik->pendaftaran->peserta->nama ?? '' }}</h3>
                    <p class="text-sm text-slate-500">Jumlah halaman setoran harian</p>
                </div>
                <a href="{{ route('setoran.index', request()->except('anggota_halaqah_id')) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-700">Tutup Grafik</a>
            </div>
            
            <div x-data="chartGrafik()" x-init="initChart()" class="w-full">
                <div id="chart"></div>
            </div>
            
            <!-- Load ApexCharts -->
            <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('chartGrafik', () => ({
                        initChart() {
                            const dataGrafik = @json($grafik);
                            
                            const options = {
                                series: [{
                                    name: 'Halaman Setoran',
                                    data: dataGrafik.map(item => item.y)
                                }],
                                chart: {
                                    type: 'area',
                                    height: 250,
                                    toolbar: { show: false },
                                    fontFamily: 'inherit',
                                    animations: {
                                        enabled: true,
                                        easing: 'easeinout',
                                        speed: 800
                                    }
                                },
                                colors: ['#059669'], // Emerald 600
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.4,
                                        opacityTo: 0.05,
                                        stops: [0, 100]
                                    }
                                },
                                dataLabels: { enabled: false },
                                stroke: {
                                    curve: 'smooth',
                                    width: 2
                                },
                                xaxis: {
                                    categories: dataGrafik.map(item => item.x),
                                    axisBorder: { show: false },
                                    axisTicks: { show: false },
                                    tooltip: { enabled: false }
                                },
                                yaxis: {
                                    min: 0,
                                    labels: {
                                        formatter: function (val) {
                                            return val + " hlm";
                                        }
                                    }
                                }
                            };
                            
                            const chart = new ApexCharts(document.querySelector("#chart"), options);
                            chart.render();
                        }
                    }))
                });
            </script>
        </x-card>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama santri…"
               class="min-w-56 flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <select name="halaqah_id" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua halaqah</option>
            @foreach ($daftarHalaqah as $item)
                <option value="{{ $item->id }}" @selected(request('halaqah_id') == $item->id)>
                    {{ $item->nama }} ({{ $item->kode }}){{ $item->angkatan ? ' — '.$item->angkatan->nama : '' }}
                </option>
            @endforeach
        </select>

        <select name="jenis" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua jenis</option>
            @foreach (['ziyadah' => 'Ziyadah', 'murajaah' => "Muraja'ah"] as $value => $label)
                <option value="{{ $value }}" @selected(request('jenis') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <input type="date" name="dari" value="{{ request('dari') }}" aria-label="Tanggal mulai"
               class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        <input type="date" name="sampai" value="{{ request('sampai') }}" aria-label="Tanggal akhir"
               class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Waktu</th>
                        @if(!isset($santriGrafik))
                            <th class="px-5 py-3 font-medium">Santri</th>
                        @endif
                        <th class="px-5 py-3 font-medium">Capaian Hafalan</th>
                        <th class="px-5 py-3 font-medium">Kualitas</th>
                        <th class="px-5 py-3 font-medium">Dicatat</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @php
                        $groupedSetoran = $setoran->groupBy(fn($item) => $item->tanggal?->format('Y-m-d') ?? '—');
                    @endphp

                    @forelse ($groupedSetoran as $date => $items)
                        <tr class="bg-slate-100/60">
                            <td colspan="{{ isset($santriGrafik) ? 5 : 6 }}" class="px-5 py-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-slate-800">
                                        <x-icon name="calendar" class="size-4 text-slate-500" />
                                        <h4 class="font-semibold">
                                            {{ $date === '—' ? 'Tanggal Tidak Diketahui' : \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
                                        </h4>
                                    </div>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Total Harian: {{ rtrim(rtrim(number_format((float) $items->sum('jumlah_halaman'), 1, ',', '.'), '0'), ',') }} hlm
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @foreach ($items as $item)
                            <tr class="tabel-baris hover:bg-slate-50">
                                <td class="px-5 py-3 text-sm font-medium text-slate-700">
                                    {{ $item->tanggal?->translatedFormat('H:i') }}
                                </td>

                                @if(!isset($santriGrafik))
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-slate-900">
                                            {{ $item->anggotaHalaqah?->pendaftaran?->peserta?->nama ?? '—' }}
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $item->anggotaHalaqah?->halaqah?->nama }}</p>
                                    </td>
                                @endif

                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-badge :color="$item->jenis_color">{{ $item->jenis_label }}</x-badge>
                                        <span class="font-medium text-slate-800">{{ $item->bacaan }}</span>
                                        <span class="text-sm text-slate-500">
                                            ({{ rtrim(rtrim(number_format((float) $item->jumlah_halaman, 1, ',', '.'), '0'), ',') }} hlm)
                                        </span>
                                    </div>
                                </td>

                                <td class="px-5 py-3">
                                    <x-badge :color="$item->kualitas_color">{{ $item->kualitas_label }}</x-badge>
                                </td>

                                <td class="px-5 py-3 text-xs text-slate-600">
                                    {{ $item->pencatat?->name ?? '—' }}
                                    @if ($item->muhaffizh && $item->pencatat?->muhaffizh?->id !== $item->muhaffizh_id)
                                        <span class="block text-slate-400">
                                            menyimak: {{ $item->muhaffizh->nama }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        @can('setoran.update')
                                            <x-icon-button icon="pencil" label="Ubah setoran"
                                                           :href="route('setoran.edit', $item)" />
                                        @endcan

                                        @can('setoran.delete')
                                            <x-confirm-delete :action="route('setoran.destroy', $item)" icon-only
                                                label="Hapus setoran"
                                                title="Hapus setoran ini?"
                                                message="Catatan setoran yang dihapus tidak ikut dalam rekap hafalan." />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty-state icon="book" title="Belum ada setoran"
                                               message="Catat setoran lewat halaman detail halaqah." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($setoran->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $setoran->links() }}</div>
        @endif
    </x-card>
</x-layouts::app>
