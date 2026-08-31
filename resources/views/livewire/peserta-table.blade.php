<div x-data="{ zoomImage: null, zoomTitle: '' }">
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, NIK, asal kabupaten, email, no HP…"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <select wire:model.live="angkatan"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua angkatan</option>
            @foreach ($daftarAngkatan as $item)
                <option value="{{ $item->id }}">{{ $item->nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="status"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="lulus">Lulus</option>
            <option value="keluar">Keluar</option>
        </select>

        <select wire:model.live="statusPendaftaran"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status pendaftaran</option>
            <option value="menunggu">Menunggu verifikasi</option>
            <option value="disetujui">Disetujui</option>
            <option value="ditolak">Ditolak</option>
        </select>

        <select wire:model.live="riwayat"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua riwayat</option>
            <option value="ulang">Pernah daftar &gt; 1 kali</option>
            <option value="alumni">Alumni (pernah lulus)</option>
            <option value="cekal">Tidak boleh mendaftar lagi</option>
        </select>
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-500">
            Menampilkan {{ $peserta->count() }} dari {{ $peserta->total() }} peserta.
        </p>
        <div class="flex items-center gap-3">
            <span wire:loading.delay class="flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z" />
                </svg>
                Memuat…
            </span>
            <button type="button" wire:click="resetFilters" class="text-xs text-slate-500 hover:underline">Reset filter</button>
        </div>
    </div>

    <x-card padding="p-0">
        <div class="memuat-halus overflow-x-auto" wire:loading.class="opacity-55">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600 select-none">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('nama')">
                            <div class="flex items-center gap-1">
                                <span>Peserta</span>
                                @if ($sortField === 'nama')
                                    <span class="text-emerald-700 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3.5 font-semibold cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('jenis_kelamin')">
                            <div class="flex items-center gap-1">
                                <span>Jenis Kelamin</span>
                                @if ($sortField === 'jenis_kelamin')
                                    <span class="text-emerald-700 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3.5 font-semibold cursor-pointer hover:bg-slate-100 transition-colors" wire:click="sortBy('kabupaten_kota')">
                            <div class="flex items-center gap-1">
                                <span>Asal Kabupaten / Wilayah</span>
                                @if ($sortField === 'kabupaten_kota')
                                    <span class="text-emerald-700 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="px-5 py-3.5 font-semibold">Kontak</th>
                        <th class="px-5 py-3.5 font-semibold">{{ $angkatan ? 'Data Angkatan' : 'Angkatan Terakhir' }}</th>
                        <th class="px-5 py-3.5 font-semibold">Riwayat</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($peserta as $item)
                        @php($terakhir = $item->pendaftaran->first())
                        <tr wire:key="peserta-{{ $item->id }}" class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($item->foto_url)
                                        <button type="button"
                                                @click="zoomImage = '{{ $item->foto_url }}'; zoomTitle = 'Foto Profil - {{ addslashes($item->nama) }}'"
                                                title="Klik untuk Auto-Zoom Foto Profil"
                                                class="group relative block size-10 shrink-0 overflow-hidden rounded-full ring-2 ring-emerald-500/30 transition-all hover:scale-110 hover:ring-emerald-600 focus:outline-none shadow-sm">
                                            <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}" class="size-full object-cover">
                                        </button>
                                    @else
                                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-black text-emerald-900 border border-emerald-200 shadow-sm">
                                            {{ $item->initials }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <a href="{{ route('peserta.show', $item) }}" class="truncate font-bold text-slate-900 hover:underline hover:text-emerald-700">
                                            {{ $item->nama }}
                                        </a>
                                        <p class="truncate font-mono text-xs text-slate-500">
                                            {{ $terakhir?->nomor_induk ?: ($item->nik ?: 'NIK -') }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->jenis_kelamin === 'L' ? 'sky' : 'rose'">
                                    {{ $item->jenis_kelamin_label }}
                                </x-badge>
                            </td>

                            <td class="px-5 py-3">
                                <p class="font-bold text-xs text-slate-800">
                                    @if ($item->kewarganegaraan === 'WNA')
                                        {{ $item->negara ?: 'Luar Negeri' }} ({{ $item->kabupaten_kota ?: 'WNA' }})
                                    @else
                                        {{ $item->kabupaten_kota ?: '—' }}
                                    @endif
                                </p>
                                @if ($item->provinsi && $item->kewarganegaraan !== 'WNA')
                                    <p class="text-[11px] text-slate-500">{{ $item->provinsi }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-800 font-medium text-xs">{{ $item->no_hp ?: '—' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item->email ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-800 font-medium text-xs">{{ $terakhir?->angkatan?->nama ?? '—' }}</p>
                                @if ($terakhir)
                                    <div class="mt-1 flex flex-wrap gap-1 items-center">
                                        @can('peserta.update')
                                            <select wire:change="updateStatusPendaftaran({{ $terakhir->id }}, $event.target.value)" 
                                                    class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium border-0 ring-1 ring-inset focus:ring-2 focus:ring-inset cursor-pointer
                                                    {{ $terakhir->status === 'aktif' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 focus:ring-emerald-600' : 
                                                       ($terakhir->status === 'lulus' ? 'bg-sky-50 text-sky-700 ring-sky-600/20 focus:ring-sky-600' : 
                                                       'bg-rose-50 text-rose-700 ring-rose-600/20 focus:ring-rose-600') }}"
                                                    style="padding-right: 1.5rem; background-position: right 0.2rem center; background-size: 1em;">
                                                <option value="aktif" @selected($terakhir->status === 'aktif')>Aktif</option>
                                                <option value="lulus" @selected($terakhir->status === 'lulus')>Lulus</option>
                                                <option value="keluar" @selected($terakhir->status === 'keluar')>Keluar</option>
                                            </select>
                                        @else
                                            <x-badge :color="$terakhir->status_color">{{ $terakhir->status_label }}</x-badge>
                                        @endcan
                                        @if ($terakhir->status_pendaftaran !== 'disetujui')
                                            <x-badge :color="$terakhir->status_pendaftaran_color">
                                                {{ $terakhir->status_pendaftaran_label }}
                                            </x-badge>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <x-badge :color="$item->pendaftaran_count > 1 ? 'sky' : 'slate'">
                                        {{ $item->pendaftaran_count }}x daftar
                                    </x-badge>
                                    @unless ($item->boleh_mendaftar_lagi)
                                        <x-badge color="rose">dicekal</x-badge>
                                    @endunless
                                </div>
                            </td>

                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <x-icon-button icon="eye" label="Lihat detail"
                                                   :href="route('peserta.show', $item)" />

                                    @if ($item->ktp_path)
                                        @can('peserta.view')
                                            <button type="button"
                                                    @click="zoomImage = '{{ route('pendaftaran.ktp', $item) }}'; zoomTitle = 'Dokumen KTP/KK - {{ addslashes($item->nama) }}'"
                                                    title="Auto Zoom Berkas KTP/KK"
                                                    class="inline-flex items-center justify-center rounded-lg p-1.5 text-emerald-700 hover:bg-emerald-50 focus:outline-none">
                                                <x-icon name="identification" class="size-4" />
                                            </button>
                                        @endcan
                                    @endif

                                    @can('peserta.update')
                                        <x-icon-button icon="pencil" label="Ubah data"
                                                       :href="route('peserta.edit', $item)" />
                                    @endcan

                                    @can('peserta.delete')
                                        <x-confirm-delete :action="route('peserta.destroy', $item)" icon-only
                                            label="Hapus peserta"
                                            :title="'Hapus '.$item->nama.'?'"
                                            message="Peserta beserta seluruh riwayat pendaftarannya dipindahkan ke daftar terhapus." />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-slate-500">
                                tidak ada data peserta yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($peserta->hasPages())
            <div class="border-t border-slate-200 p-4">{{ $peserta->links() }}</div>
        @endif
    </x-card>

    {{-- Modal Auto-Zoom Popup Lightbox --}}
    <template x-teleport="body">
        <div x-show="zoomImage" x-cloak @keydown.escape.window="zoomImage = null"
             class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <!-- Backdrop Overlay -->
            <div x-show="zoomImage" @click="zoomImage = null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

            <!-- Modal Body Container -->
            <div x-show="zoomImage"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-90"
                 class="relative z-10 max-w-4xl w-full rounded-2xl bg-white p-5 shadow-2xl border border-slate-200 overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                    <h3 class="font-bold text-slate-900 text-sm" x-text="zoomTitle || 'Pratinjau Foto'"></h3>
                    <button type="button" @click="zoomImage = null" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <x-icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <div class="overflow-auto max-h-[75vh] grid place-items-center bg-slate-950/5 rounded-xl p-3">
                    <img :src="zoomImage" :alt="zoomTitle" class="max-h-[70vh] w-auto object-contain rounded-lg shadow-lg">
                </div>
                <div class="mt-3 flex justify-between items-center text-xs text-slate-500 px-1">
                    <span>🔍 Tekan ESC atau klik luar foto untuk menutup</span>
                    <a :href="zoomImage" target="_blank" class="font-bold text-emerald-700 hover:underline">Buka Ukuran Asli ↗</a>
                </div>
            </div>
        </div>
    </template>
</div>
