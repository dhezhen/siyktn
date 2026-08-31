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
                        <th class="px-5 py-3 font-medium">Tanggal</th>
                        <th class="px-5 py-3 font-medium">Santri</th>
                        <th class="px-5 py-3 font-medium">Jenis</th>
                        <th class="px-5 py-3 font-medium">Halaman</th>
                        <th class="px-5 py-3 font-medium">Bacaan</th>
                        <th class="px-5 py-3 font-medium">Kualitas</th>
                        <th class="px-5 py-3 font-medium">Dicatat</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($setoran as $item)
                        <tr class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3 text-xs text-slate-600">
                                {{ $item->tanggal?->translatedFormat('d M Y') }}
                            </td>

                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">
                                    {{ $item->anggotaHalaqah?->pendaftaran?->peserta?->nama ?? '—' }}
                                </p>
                                <p class="text-xs text-slate-500">{{ $item->anggotaHalaqah?->halaqah?->nama }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->jenis_color">{{ $item->jenis_label }}</x-badge>
                            </td>

                            <td class="px-5 py-3 font-medium text-slate-800">
                                {{ rtrim(rtrim(number_format((float) $item->jumlah_halaman, 1, ',', '.'), '0'), ',') }}
                            </td>

                            <td class="px-5 py-3 text-slate-700">{{ $item->bacaan }}</td>

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
                                <div class="flex items-center justify-end gap-0.5">
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
