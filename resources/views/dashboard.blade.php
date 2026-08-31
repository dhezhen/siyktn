<x-layouts::app :title="'Dashboard'">
    <x-page-header title="Dashboard" :subtitle="$sambutan" />

    @if (filled($stats ?? []))
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $stat)
                <x-card padding="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
                        </div>
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                            <x-icon :name="$stat['icon']" class="size-5" />
                        </span>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    @isset($grafik)
        @php($adaSetoran = collect($grafik['pekan']['ziyadah'])->sum() + collect($grafik['pekan']['murajaah'])->sum() > 0)

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <x-card class="lg:col-span-2"
                    :title="$grafik['milikSendiri'] ? 'Setoran Bimbingan Saya' : 'Setoran Seluruh Halaqah'"
                    subtitle="Jumlah halaman per pekan, delapan pekan terakhir.">
                @if ($adaSetoran)
                    <x-grafik.garis
                        :labels="$grafik['pekan']['labels']"
                        :seri="[
                            ['nama' => 'Ziyadah', 'warna' => \App\Support\Grafik::ZIYADAH, 'data' => $grafik['pekan']['ziyadah']],
                            ['nama' => 'Muraja\'ah', 'warna' => \App\Support\Grafik::MURAJAAH, 'data' => $grafik['pekan']['murajaah']],
                        ]" />
                @else
                    <x-empty-state icon="book" title="Belum ada setoran delapan pekan terakhir"
                                   message="Grafik akan terisi begitu setoran pertama dicatat." />
                @endif
            </x-card>

            <x-card title="Sebaran Kualitas"
                    :subtitle="$grafik['milikSendiri'] ? 'Seluruh setoran bimbingan Anda.' : 'Seluruh setoran tercatat.'">
                @if (collect($grafik['kualitas'])->sum('nilai') > 0)
                    <x-grafik.tumpuk :data="$grafik['kualitas']" />
                @else
                    <x-empty-state icon="info" title="Belum ada penilaian"
                                   message="Kualitas terisi otomatis dari setiap setoran." />
                @endif
            </x-card>
        </div>

        @if (filled($grafik['peringkat']))
            <div class="mt-4">
                <x-card :title="$grafik['milikSendiri'] ? 'Hafalan Santri Bimbingan' : 'Hafalan per Halaqah'"
                        :subtitle="($grafik['milikSendiri'] ? 'Sepuluh santri' : 'Sepuluh halaqah').' dengan ziyadah terbanyak, dalam halaman.'">
                    <x-grafik.batang :data="$grafik['peringkat']" />
                </x-card>
            </div>
        @endif
    @endisset

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        {{-- Log aktivitas hanya milik yang boleh melihatnya; tanpa izin, kartunya
             tidak ditampilkan sama sekali agar tidak terbaca "belum ada aktivitas". --}}
        @can('activity.view')
            <x-card class="lg:col-span-2" title="Aktivitas Terakhir" subtitle="10 perubahan data paling baru.">
                @forelse ($activities ?? [] as $activity)
                    <div class="flex items-start gap-3 border-b border-slate-100 py-2.5 last:border-0">
                        <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-slate-700">{{ $activity->description }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $activity->causer?->name ?? 'Sistem' }} &middot; {{ $activity->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <x-empty-state title="Belum ada aktivitas"
                                   message="Perubahan data akan tercatat di sini secara otomatis." />
                @endforelse
            </x-card>
        @endcan

        {{-- Tanpa kartu aktivitas di sebelahnya, kartu ini mengambil lebar penuh. --}}
        <x-card :class="auth()->user()->can('activity.view') ? '' : 'lg:col-span-3'" title="Selamat Datang">
            <p class="text-sm leading-relaxed text-slate-600">
                Halo <span class="font-medium text-slate-900">{{ auth()->user()->name }}</span>,
                Anda masuk sebagai
                <span class="font-medium text-slate-900">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'pengguna tanpa role' }}</span>.
            </p>

            {{-- Pintasan mengikuti izin, sehingga tiap peran mendarat langsung
                 di pekerjaannya masing-masing. --}}
            @if (filled($pintasan ?? []))
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($pintasan as $p)
                        <x-button :href="$p['url']" variant="secondary" size="sm">{{ $p['label'] }}</x-button>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm leading-relaxed text-slate-600">
                    Gunakan menu di samping untuk mengelola data. Menu yang tampil menyesuaikan
                    hak akses yang Anda miliki.
                </p>
            @endif
        </x-card>
    </div>
</x-layouts::app>
