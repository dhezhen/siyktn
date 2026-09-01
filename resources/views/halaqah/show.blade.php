<x-layouts::app :title="$halaqah->nama">
    <x-page-header :title="$halaqah->nama"
                   :subtitle="$halaqah->kode.' · '.$halaqah->jenis_kelamin_label.' · '.($halaqah->angkatan?->nama ?? 'tanpa angkatan')">
        <x-slot:actions>
            <x-button :href="route('halaqah.index')" variant="secondary" icon="arrow-left" class="hidden sm:inline-flex">Kembali</x-button>
            <x-button :href="route('halaqah.laporan', $halaqah)" variant="warning" icon="document-text">Rekap Syahadah</x-button>
            @can('halaqah.update')
                <x-button :href="route('halaqah.edit', $halaqah)" variant="primary" icon="pencil">Ubah</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @unless ($halaqah->is_aktif)
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-5 py-4">
            <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-slate-500" />
            <div>
                <p class="text-sm font-medium text-slate-900">Halaqah ini nonaktif</p>
                <p class="text-xs text-slate-600">
                    Santri baru tidak bisa ditempatkan di sini. Data yang sudah ada tetap tersimpan sebagai riwayat.
                </p>
            </div>
        </div>
    @endunless

    @if (! $halaqah->muhaffizh && $halaqah->is_aktif)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <x-icon name="warning" class="mt-0.5 size-5 shrink-0 text-amber-600" />
                <div>
                    <p class="text-sm font-medium text-amber-900">Belum ada muhaffizh pengampu</p>
                    <p class="text-xs text-amber-800">Tugaskan pembimbing agar setoran santri bisa dicatat atas namanya.</p>
                </div>
            </div>
            @can('halaqah.update')
                <x-button :href="route('halaqah.edit', $halaqah)" size="sm">Tugaskan Muhaffizh</x-button>
            @endcan
        </div>
    @endif

    <div class="mb-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Santri Aktif', 'value' => $anggotaAktif->count(), 'icon' => 'users'],
            ['label' => 'Sisa Kursi', 'value' => $halaqah->sisa_kuota ?? 'Tak dibatasi', 'icon' => 'list'],
            ['label' => 'Pernah Bergabung', 'value' => $anggotaAktif->count() + $riwayat->count(), 'icon' => 'identification'],
            ['label' => 'Belum Berhalaqah', 'value' => $calonSantri->count(), 'icon' => 'warning'],
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
        <div class="space-y-4 lg:col-span-2">
            <x-card padding="p-0" title="Santri Binaan"
                    :subtitle="$halaqah->kuota > 0
                        ? $anggotaAktif->count().' dari '.$halaqah->kuota.' kursi terisi.'
                        : $anggotaAktif->count().' santri, tanpa batas kuota.'">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Nomor Induk</th>
                                <th class="px-5 py-3 font-medium">Nama</th>
                                <th class="px-5 py-3 font-medium">Bergabung</th>
                                <th class="px-5 py-3 font-medium">Hafalan</th>
                                <th class="px-5 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($anggotaAktif as $item)
                                <tr class="tabel-baris hover:bg-slate-50">
                                    <td class="px-5 py-3 font-mono text-xs text-slate-600">
                                        {{ $item->pendaftaran?->nomor_induk ?: '—' }}
                                    </td>

                                    <td class="px-5 py-3">
                                        @if ($item->pendaftaran?->peserta)
                                            @can('peserta.view')
                                                <a href="{{ route('peserta.show', $item->pendaftaran->peserta) }}"
                                                   class="font-medium text-emerald-700 hover:underline">
                                                    {{ $item->pendaftaran->peserta->nama }}
                                                </a>
                                            @else
                                                <span class="font-medium text-slate-900">{{ $item->pendaftaran->peserta->nama }}</span>
                                            @endcan
                                            <p class="text-xs text-slate-500">{{ $item->pendaftaran->peserta->no_hp ?: '' }}</p>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-3 text-xs text-slate-600">
                                        {{ $item->tanggal_bergabung?->translatedFormat('d M Y') ?? '—' }}
                                    </td>

                                    @php($ziyadah = (float) ($item->ziyadah_halaman ?? 0))
                                    <td class="px-5 py-3">
                                        <div class="p-2">
                                            <p class="font-medium text-slate-800">
                                                {{ $item->setoranTerakhir ? $item->setoranTerakhir->bacaan : 'Belum setor' }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                Total: {{ rtrim(rtrim(number_format($ziyadah, 1, ',', '.'), '0'), ',') }} hlm
                                                &middot; {{ $item->setoran_count }}x setor
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-button icon="clock" size="sm" variant="secondary"
                                                      :href="route('setoran.index', ['anggota_halaqah_id' => $item->id, 'halaqah_id' => $halaqah->id])">
                                                Riwayat
                                            </x-button>

                                            @can('setoran.create')
                                                <x-button icon="plus" size="sm" variant="primary"
                                                          :href="route('setoran.create', ['halaqah_id' => $halaqah->id, 'anggota_halaqah_id' => $item->id])">
                                                    Setor
                                                </x-button>
                                            @endcan

                                            @can('halaqah.update')
                                                <x-halaqah.aksi-anggota :anggota="$item" :tujuan="$halaqahTujuan" />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state icon="users" title="Belum ada santri di halaqah ini"
                                                       message="Tempatkan santri lewat panel di bawah." />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            @can('halaqah.update')
                <x-card title="Tempatkan Santri"
                        :subtitle="'Santri '.$halaqah->angkatan?->nama.' yang belum punya halaqah dan cocok peruntukannya.'">
                    @if (! $halaqah->is_aktif)
                        <x-empty-state icon="info" title="Halaqah nonaktif"
                                       message="Aktifkan kembali halaqah ini sebelum menempatkan santri baru." />
                    @elseif ($halaqah->isPenuh())
                        <x-empty-state icon="warning" title="Kuota sudah penuh"
                                       message="Tambah kuota lewat menu Ubah Halaqah, atau pindahkan sebagian santri." />
                    @elseif ($calonSantri->isEmpty())
                        <x-empty-state icon="check-circle" title="Semua santri sudah punya halaqah"
                                       :message="'Tidak ada santri '.$halaqah->jenis_kelamin_label.' di '.($halaqah->angkatan?->nama ?? 'angkatan ini').' yang menunggu penempatan.'" />
                    @else
                        <form method="POST" action="{{ route('halaqah.anggota.store', $halaqah) }}"
                              x-data="{ terpilih: 0 }">
                            @csrf

                            <div class="max-h-72 overflow-y-auto rounded-lg ring-1 ring-slate-200">
                                <table class="w-full text-sm">
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($calonSantri as $calon)
                                            <tr class="hover:bg-slate-50">
                                                <td class="w-10 py-2.5 pl-4">
                                                    <input type="checkbox" name="pendaftaran_id[]" value="{{ $calon->id }}"
                                                           id="calon_{{ $calon->id }}"
                                                           @change="terpilih += $event.target.checked ? 1 : -1"
                                                           class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                                </td>
                                                <td class="py-2.5 pr-4">
                                                    <label for="calon_{{ $calon->id }}" class="block cursor-pointer">
                                                        <span class="font-medium text-slate-800">{{ $calon->peserta?->nama ?? '—' }}</span>
                                                        <span class="block font-mono text-xs text-slate-500">
                                                            {{ $calon->nomor_induk ?: $calon->kode_pendaftaran }}
                                                            @if ($calon->peserta?->tempat_lahir)
                                                                <span class="font-sans text-slate-400">&middot; {{ $calon->peserta->tempat_lahir }}</span>
                                                            @endif
                                                        </span>
                                                    </label>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
                                <div class="w-44">
                                    <x-input name="tanggal_bergabung" type="date" label="Tanggal Bergabung"
                                             :value="old('tanggal_bergabung', now()->toDateString())" />
                                </div>

                                <div class="flex items-center gap-3">
                                    <p class="text-xs text-slate-500">
                                        <span x-text="terpilih">0</span> santri dipilih
                                        @if ($halaqah->sisa_kuota !== null)
                                            <span class="block">Sisa kursi: {{ $halaqah->sisa_kuota }}</span>
                                        @endif
                                    </p>
                                    <x-button type="submit" icon="plus" x-bind:disabled="terpilih === 0">
                                        Tempatkan
                                    </x-button>
                                </div>
                            </div>
                        </form>
                    @endif
                </x-card>
            @endcan

            @can('setoran.view')
                <x-card padding="p-0" title="Setoran Terakhir"
                        subtitle="Sepuluh catatan terbaru di halaqah ini.">
                    <x-slot:actions>
                        <x-button :href="route('setoran.index', ['halaqah_id' => $halaqah->id])"
                                  variant="secondary" size="sm">Lihat Semua</x-button>
                    </x-slot:actions>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Tanggal</th>
                                    <th class="px-5 py-3 font-medium">Santri</th>
                                    <th class="px-5 py-3 font-medium">Jenis</th>
                                    <th class="px-5 py-3 font-medium">Halaman</th>
                                    <th class="px-5 py-3 font-medium">Dicatat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($setoranTerakhir as $item)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $item->tanggal?->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="px-5 py-3 text-slate-800">
                                            {{ $item->anggotaHalaqah?->pendaftaran?->peserta?->nama ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <x-badge :color="$item->jenis_color">{{ $item->jenis_label }}</x-badge>
                                        </td>
                                        <td class="px-5 py-3 text-slate-800">
                                            {{ rtrim(rtrim(number_format((float) $item->jumlah_halaman, 1, ',', '.'), '0'), ',') }}
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $item->pencatat?->name ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <x-empty-state icon="book" title="Belum ada setoran"
                                                           message="Pakai tombol + di baris santri untuk mencatat setoran pertamanya." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endcan

            @if ($riwayat->isNotEmpty())
                <x-card padding="p-0" title="Riwayat Keanggotaan"
                        subtitle="Santri yang pernah berada di halaqah ini.">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Nama</th>
                                    <th class="px-5 py-3 font-medium">Periode</th>
                                    <th class="px-5 py-3 font-medium">Alasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($riwayat as $item)
                                    <tr class="hover:bg-slate-50">
                                        <td class="px-5 py-3 text-slate-800">
                                            {{ $item->pendaftaran?->peserta?->nama ?? '—' }}
                                            <span class="block font-mono text-xs text-slate-500">
                                                {{ $item->pendaftaran?->nomor_induk ?: '' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $item->tanggal_bergabung?->translatedFormat('d M Y') ?? '—' }}
                                            &ndash;
                                            {{ $item->tanggal_keluar?->translatedFormat('d M Y') ?? '—' }}
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            {{ $item->alasan_pindah ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>

        <div class="space-y-4">
            <x-card title="Muhaffizh Pengampu">
                @if ($halaqah->muhaffizh)
                    <div class="flex items-center gap-3">
                        @if ($halaqah->muhaffizh->foto_url)
                            <img src="{{ $halaqah->muhaffizh->foto_url }}" alt="Foto {{ $halaqah->muhaffizh->nama }}"
                                 class="size-12 rounded-full object-cover ring-1 ring-slate-200">
                        @else
                            <span class="grid size-12 place-items-center rounded-full bg-slate-200 text-sm font-semibold text-slate-500">
                                {{ $halaqah->muhaffizh->initials }}
                            </span>
                        @endif
                        <div>
                            @can('muhaffizh.view')
                                <a href="{{ route('muhaffizh.show', $halaqah->muhaffizh) }}"
                                   class="font-medium text-emerald-700 hover:underline">{{ $halaqah->muhaffizh->nama }}</a>
                            @else
                                <p class="font-medium text-slate-900">{{ $halaqah->muhaffizh->nama }}</p>
                            @endcan
                            <p class="text-xs text-slate-500">
                                {{ $halaqah->muhaffizh->kode }}
                                @if ($halaqah->muhaffizh->sanad_riwayat)
                                    &middot; {{ $halaqah->muhaffizh->sanad_riwayat }}
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ditugaskan.</p>
                @endif
            </x-card>

            <x-card title="Informasi">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Status</dt>
                        <dd class="mt-0.5">
                            <x-badge :color="$halaqah->is_aktif ? 'emerald' : 'slate'">
                                {{ $halaqah->is_aktif ? 'Berjalan' : 'Nonaktif' }}
                            </x-badge>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Angkatan</dt>
                        <dd class="mt-0.5 text-slate-800">
                            @if ($halaqah->angkatan)
                                @can('angkatan.view')
                                    <a href="{{ route('angkatan.show', $halaqah->angkatan) }}"
                                       class="text-emerald-700 hover:underline">{{ $halaqah->angkatan->nama }}</a>
                                @else
                                    {{ $halaqah->angkatan->nama }}
                                @endcan
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    @foreach ([
                        'Peruntukan' => $halaqah->jenis_kelamin_label,
                        'Kuota' => $halaqah->kuota > 0 ? $halaqah->kuota.' santri' : 'Tidak dibatasi',
                        'Ruang' => $halaqah->ruang,
                        'Jadwal' => $halaqah->jadwal,
                    ] as $label => $value)
                        <div>
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-slate-800">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach

                    @if ($halaqah->keterangan)
                        <div>
                            <dt class="text-slate-500">Keterangan</dt>
                            <dd class="mt-0.5 whitespace-pre-line text-slate-800">{{ $halaqah->keterangan }}</dd>
                        </div>
                    @endif
                </dl>
            </x-card>
        </div>
    </div>
</x-layouts::app>
