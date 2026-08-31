<x-layouts::app :title="'Muhaffizh'">
    <x-page-header title="Muhaffizh" subtitle="Pembimbing hafalan beserta halaqah yang diampu.">
        <x-slot:actions>
            @can('muhaffizh.export')
                <x-button :href="route('muhaffizh.export')" variant="secondary" icon="download">Ekspor CSV</x-button>
            @endcan
            @can('muhaffizh.create')
                <x-button :href="route('muhaffizh.create')" icon="plus">Tambah Muhaffizh</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, kode, HP, atau email…"
               class="min-w-64 flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <select name="jenis_kelamin" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua jenis kelamin</option>
            @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $value => $label)
                <option value="{{ $value }}" @selected(request('jenis_kelamin') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Muhaffizh</th>
                        <th class="px-5 py-3 font-medium">Kontak</th>
                        <th class="px-5 py-3 font-medium">Sanad / Pendidikan</th>
                        <th class="px-5 py-3 font-medium">Halaqah Aktif</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($muhaffizh as $item)
                        <tr class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($item->foto_url)
                                        <img src="{{ $item->foto_url }}" alt="Foto {{ $item->nama }}"
                                             class="size-9 shrink-0 rounded-full object-cover ring-1 ring-slate-200">
                                    @else
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-500">
                                            {{ $item->initials }}
                                        </span>
                                    @endif
                                    <div>
                                        <p class="font-medium text-slate-900">{{ $item->nama }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ $item->kode }} &middot; {{ $item->jenis_kelamin_label }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                <p>{{ $item->no_hp ?: '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $item->email ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                <p>{{ $item->sanad_riwayat ?: '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $item->pendidikan ?: '' }}</p>
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ $item->halaqah_count }}
                                <span class="text-slate-400">halaqah</span>
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->status_color">{{ ucfirst($item->status) }}</x-badge>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <x-icon-button icon="eye" label="Lihat detail muhaffizh"
                                                   :href="route('muhaffizh.show', $item)" />

                                    @can('muhaffizh.update')
                                        <x-icon-button icon="pencil" label="Ubah muhaffizh"
                                                       :href="route('muhaffizh.edit', $item)" />
                                    @endcan

                                    @can('muhaffizh.delete')
                                        <x-confirm-delete :action="route('muhaffizh.destroy', $item)" icon-only
                                            label="Hapus muhaffizh"
                                            :title="'Hapus '.$item->nama.'?'"
                                            message="Muhaffizh yang sudah pernah mengampu halaqah tidak dapat dihapus — nonaktifkan saja." />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state icon="academic" title="Belum ada muhaffizh"
                                               message="Daftarkan pembimbing hafalan terlebih dahulu sebelum membentuk halaqah." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($muhaffizh->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $muhaffizh->links() }}</div>
        @endif
    </x-card>
</x-layouts::app>
