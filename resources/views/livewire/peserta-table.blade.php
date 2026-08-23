<div>
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, nomor induk, no HP, atau nama wali…"
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

        <select wire:model.live="jenisKelamin"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua jenis kelamin</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
        </select>
    </div>

    <div class="mb-3 flex items-center justify-between gap-2">
        <p class="text-sm text-slate-500">
            Menampilkan {{ $peserta->count() }} dari {{ $peserta->total() }} peserta.
        </p>
        <div class="flex items-center gap-3">
            <span wire:loading class="text-xs text-slate-400">Memuat…</span>
            <button type="button" wire:click="resetFilters" class="text-xs text-slate-500 hover:underline">Reset filter</button>
        </div>
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Peserta</th>
                        <th class="px-5 py-3 font-medium">Angkatan</th>
                        <th class="px-5 py-3 font-medium">Kontak</th>
                        <th class="px-5 py-3 font-medium">Wali</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($peserta as $item)
                        <tr wire:key="peserta-{{ $item->id }}" class="hover:bg-slate-50">
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
                                            {{ $item->nomor_induk }} &middot; {{ $item->jenis_kelamin_label }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-700">{{ $item->angkatan?->nama ?? '—' }}</td>

                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $item->no_hp ?: '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $item->tempat_lahir ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $item->nama_wali ?: '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $item->no_hp_wali ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->status_color">{{ Str::title($item->status) }}</x-badge>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <x-button :href="route('peserta.show', $item)" variant="ghost" size="sm">Detail</x-button>

                                    @can('peserta.update')
                                        <x-button :href="route('peserta.edit', $item)" variant="secondary" size="sm">Ubah</x-button>
                                    @endcan

                                    @can('peserta.delete')
                                        <x-confirm-delete :action="route('peserta.destroy', $item)" icon-only
                                            :title="'Hapus '.$item->nama.'?'"
                                            message="Data peserta dipindahkan ke daftar terhapus, bukan dihapus permanen." />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
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
