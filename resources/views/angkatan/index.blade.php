<x-layouts::app :title="'Angkatan'">
    <x-page-header title="Angkatan" subtitle="Kelompok peserta per periode program.">
        <x-slot:actions>
            @can('angkatan.create')
                <x-button :href="route('angkatan.create')" icon="plus">Tambah Angkatan</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode angkatan…"
               class="min-w-64 flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <select name="status" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            @foreach (['persiapan' => 'Persiapan', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $value => $label)
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
                        <th class="px-5 py-3 font-medium">Angkatan</th>
                        <th class="px-5 py-3 font-medium">Periode</th>
                        <th class="px-5 py-3 font-medium">Peserta Aktif</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($angkatan as $item)
                        <tr class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $item->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $item->kode }} &middot; {{ $item->tahun }}</p>
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                @if ($item->tanggal_mulai)
                                    {{ $item->tanggal_mulai->translatedFormat('d M Y') }}
                                    &ndash;
                                    {{ $item->tanggal_selesai?->translatedFormat('d M Y') ?? 'belum ditentukan' }}
                                @else
                                    <span class="text-slate-400">Belum dijadwalkan</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ $item->peserta_aktif_count }}
                                @if ($item->kuota > 0)
                                    <span class="text-slate-400">/ {{ $item->kuota }}</span>
                                    @if ($item->sisa_kuota === 0)
                                        <x-badge color="rose">penuh</x-badge>
                                    @endif
                                @endif
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->status_color">{{ $item->status_label }}</x-badge>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <x-icon-button icon="eye" label="Lihat detail angkatan"
                                                   :href="route('angkatan.show', $item)" />

                                    @can('peserta.create')
                                        <x-icon-button icon="plus" label="Tambah peserta ke angkatan ini"
                                                       :href="route('peserta.create', ['angkatan_id' => $item->id])" />
                                    @endcan

                                    @can('angkatan.update')
                                        <x-icon-button icon="pencil" label="Ubah angkatan"
                                                       :href="route('angkatan.edit', $item)" />
                                    @endcan

                                    @can('angkatan.delete')
                                        <x-confirm-delete :action="route('angkatan.destroy', $item)" icon-only
                                            label="Hapus angkatan"
                                            :title="'Hapus '.$item->nama.'?'"
                                            message="Angkatan yang masih memiliki peserta tidak dapat dihapus." />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state title="Belum ada angkatan"
                                               message="Buat angkatan terlebih dahulu sebelum menambahkan peserta." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($angkatan->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $angkatan->links() }}</div>
        @endif
    </x-card>
</x-layouts::app>
