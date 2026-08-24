<div>
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, NIK, nomor induk, email, no HP…"
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
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Peserta</th>
                        <th class="px-5 py-3 font-medium">Kontak</th>
                        <th class="px-5 py-3 font-medium">Angkatan Terakhir</th>
                        <th class="px-5 py-3 font-medium">Riwayat</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($peserta as $item)
                        @php($terakhir = $item->pendaftaranTerakhir)
                        <tr wire:key="peserta-{{ $item->id }}" class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($item->foto_url)
                                        <img src="{{ $item->foto_url }}" alt=""
                                             class="size-9 rounded-full object-cover ring-1 ring-slate-200">
                                    @else
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
                                            {{ $item->initials }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ $item->nama }}</p>
                                        <p class="truncate text-xs text-slate-500">
                                            {{ $terakhir?->nomor_induk ?: ($item->nik ?: '—') }}
                                            &middot; {{ $item->jenis_kelamin_label }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $item->no_hp ?: '—' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item->email ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $terakhir?->angkatan?->nama ?? '—' }}</p>
                                @if ($terakhir)
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <x-badge :color="$terakhir->status_color">{{ $terakhir->status_label }}</x-badge>
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

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <x-icon-button icon="eye" label="Lihat detail"
                                                   :href="route('peserta.show', $item)" />

                                    @if ($item->ktp_path)
                                        @can('peserta.view')
                                            <x-icon-button icon="identification" label="Lihat berkas KTP"
                                                           :href="route('pendaftaran.ktp', $item)" target="_blank" />
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
                            <td colspan="5">
                                <x-empty-state title="Belum ada peserta yang cocok"
                                               message="Tambahkan peserta baru atau ubah filter pencarian." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($peserta->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $peserta->links() }}</div>
        @endif
    </x-card>
</div>
