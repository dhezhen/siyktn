<x-layouts::app :title="$peserta->nama">
    <x-page-header :title="$peserta->nama" :subtitle="$peserta->nomor_induk.' · '.($peserta->angkatan?->nama ?? 'Tanpa angkatan')">
        <x-slot:actions>
            <x-button :href="route('peserta.index')" variant="secondary">Kembali</x-button>
            @can('peserta.update')
                <x-button :href="route('peserta.edit', $peserta)">Ubah Data</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Data Pribadi">
            <dl class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    'Nomor Induk' => $peserta->nomor_induk,
                    'Nama Lengkap' => $peserta->nama,
                    'Jenis Kelamin' => $peserta->jenis_kelamin_label,
                    'Angkatan' => $peserta->angkatan?->nama,
                    'Tempat, Tanggal Lahir' => trim(($peserta->tempat_lahir ?? '').($peserta->tanggal_lahir ? ', '.$peserta->tanggal_lahir->translatedFormat('d F Y') : ''), ', '),
                    'Nomor HP' => $peserta->no_hp,
                    'Tanggal Masuk' => $peserta->tanggal_masuk?->translatedFormat('d F Y'),
                ] as $label => $value)
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach

                <div class="sm:col-span-2">
                    <dt class="text-xs uppercase tracking-wide text-slate-500">Alamat</dt>
                    <dd class="mt-0.5 whitespace-pre-line text-sm text-slate-800">{{ $peserta->alamat ?: '—' }}</dd>
                </div>
            </dl>

            <div class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="mb-3 text-sm font-semibold text-slate-900">Data Wali</h3>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Nama Wali</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $peserta->nama_wali ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Nomor HP Wali</dt>
                        <dd class="mt-0.5 text-sm text-slate-800">{{ $peserta->no_hp_wali ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </x-card>

        <div class="space-y-4">
            <x-card>
                <div class="flex flex-col items-center text-center">
                    @if ($peserta->foto_url)
                        <img src="{{ $peserta->foto_url }}" alt="Foto {{ $peserta->nama }}"
                             class="size-24 rounded-full object-cover ring-1 ring-slate-200">
                    @else
                        <span class="grid size-24 place-items-center rounded-full bg-slate-200 text-2xl font-semibold text-slate-500">
                            {{ $peserta->initials }}
                        </span>
                    @endif

                    <p class="mt-3 font-medium text-slate-900">{{ $peserta->nama }}</p>
                    <p class="font-mono text-xs text-slate-500">{{ $peserta->nomor_induk }}</p>
                    <div class="mt-2">
                        <x-badge :color="$peserta->status_color">{{ Str::title($peserta->status) }}</x-badge>
                    </div>
                </div>
            </x-card>

            @if ($peserta->angkatan)
                <x-card title="Angkatan">
                    <p class="text-sm font-medium text-slate-900">{{ $peserta->angkatan->nama }}</p>
                    <p class="text-xs text-slate-500">{{ $peserta->angkatan->kode }} &middot; {{ $peserta->angkatan->tahun }}</p>
                    <div class="mt-3">
                        <x-button :href="route('angkatan.show', $peserta->angkatan)" variant="secondary" size="sm" class="w-full">
                            Lihat Angkatan
                        </x-button>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts::app>
