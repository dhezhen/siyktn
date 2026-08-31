<x-layouts::app :title="$angkatan->nama">
    <x-page-header :title="$angkatan->nama" :subtitle="$angkatan->kode.' · Tahun '.$angkatan->tahun">
        <x-slot:actions>
            @can('peserta.create')
                <x-button :href="route('peserta.create', ['angkatan_id' => $angkatan->id])" variant="secondary">
                    Tambah Peserta
                </x-button>
            @endcan
            @can('angkatan.update')
                <x-button :href="route('angkatan.edit', $angkatan)">Ubah Angkatan</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Total Peserta', 'value' => $angkatan->pendaftaran_count, 'icon' => 'users'],
            ['label' => 'Peserta Aktif', 'value' => $angkatan->peserta_aktif_count, 'icon' => 'check-circle'],
            ['label' => 'Sudah Lulus', 'value' => $angkatan->peserta_lulus_count, 'icon' => 'shield'],
            ['label' => 'Sisa Kuota', 'value' => $angkatan->sisa_kuota ?? 'Tak dibatasi', 'icon' => 'list'],
        ] as $stat)
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

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" padding="p-0" title="Daftar Peserta">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nomor Induk</th>
                            <th class="px-5 py-3 font-medium">Nama</th>
                            <th class="px-5 py-3 font-medium">L/P</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pendaftaran as $item)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-mono text-xs text-slate-600">{{ $item->nomor_induk ?: '—' }}</td>
                                <td class="px-5 py-3">
                                    @if ($item->peserta)
                                        <a href="{{ route('peserta.show', $item->peserta) }}" class="font-medium text-emerald-700 hover:underline">
                                            {{ $item->peserta->nama }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $item->peserta?->jenis_kelamin ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-col items-start gap-1">
                                        <x-badge :color="$item->status_pendaftaran_color">{{ $item->status_pendaftaran_label }}</x-badge>
                                        @if ($item->status_pendaftaran === 'disetujui')
                                            <x-badge :color="$item->status_color">{{ $item->status_label }}</x-badge>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <x-empty-state title="Belum ada peserta di angkatan ini"
                                                   message="Tambahkan peserta lewat tombol di kanan atas." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pendaftaran->hasPages())
                <div class="border-t border-slate-200 px-5 py-3">{{ $pendaftaran->links() }}</div>
            @endif
        </x-card>

        <x-card title="Informasi">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-slate-500">Status</dt>
                    <dd class="mt-0.5"><x-badge :color="$angkatan->status_color">{{ $angkatan->status_label }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-slate-500">Periode</dt>
                    <dd class="mt-0.5 text-slate-800">
                        {{ $angkatan->tanggal_mulai?->translatedFormat('d F Y') ?? '—' }}
                        s.d.
                        {{ $angkatan->tanggal_selesai?->translatedFormat('d F Y') ?? '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kuota Total</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $angkatan->kuota > 0 ? $angkatan->kuota.' peserta (sisa: '.($angkatan->sisa_kuota ?? 0).')' : 'Tidak dibatasi' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kuota Putra (Laki-laki)</dt>
                    <dd class="mt-0.5 text-slate-800">
                        {{ $angkatan->kuota_putra > 0 ? $angkatan->kuota_putra.' kursi (terisi: '.$angkatan->peserta_putra_aktif_count.', sisa: '.($angkatan->sisa_kuota_putra ?? 'unlimited').')' : 'Tidak dibatasi' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Kuota Putri (Perempuan)</dt>
                    <dd class="mt-0.5 text-slate-800">
                        {{ $angkatan->kuota_putri > 0 ? $angkatan->kuota_putri.' kursi (terisi: '.$angkatan->peserta_putri_aktif_count.', sisa: '.($angkatan->sisa_kuota_putri ?? 'unlimited').')' : 'Tidak dibatasi' }}
                    </dd>
                </div>
                @if ($angkatan->keterangan)
                    <div>
                        <dt class="text-slate-500">Keterangan</dt>
                        <dd class="mt-0.5 whitespace-pre-line text-slate-800">{{ $angkatan->keterangan }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>
    </div>
</x-layouts::app>
