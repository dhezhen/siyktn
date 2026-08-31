<x-layouts::app :title="'Halaqah'">
    <x-page-header :title="$hanyaMilikSendiri ? 'Halaqah Saya' : 'Halaqah'"
                   :subtitle="$hanyaMilikSendiri
                        ? 'Kelompok binaan yang Anda ampu.'
                        : 'Kelompok binaan dalam satu angkatan, diampu seorang muhaffizh.'">
        <x-slot:actions>
            @can('halaqah.create')
                <x-button :href="route('halaqah.create')" icon="plus">Tambah Halaqah</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode halaqah…"
               class="min-w-56 flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <select name="angkatan_id" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua angkatan</option>
            @foreach ($daftarAngkatan as $item)
                <option value="{{ $item->id }}" @selected(request('angkatan_id') == $item->id)>{{ $item->nama }}</option>
            @endforeach
        </select>

        @unless ($hanyaMilikSendiri)
            <select name="muhaffizh_id" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                <option value="">Semua muhaffizh</option>
                @foreach ($daftarMuhaffizh as $item)
                    <option value="{{ $item->id }}" @selected(request('muhaffizh_id') == $item->id)>{{ $item->nama }}</option>
                @endforeach
            </select>
        @endunless

        <select name="jenis_kelamin" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Ikhwan &amp; Akhwat</option>
            @foreach (['L' => 'Ikhwan', 'P' => 'Akhwat'] as $value => $label)
                <option value="{{ $value }}" @selected(request('jenis_kelamin') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            @foreach (['aktif' => 'Berjalan', 'nonaktif' => 'Nonaktif'] as $value => $label)
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
                        <th class="px-5 py-3 font-medium">Halaqah</th>
                        <th class="px-5 py-3 font-medium">Angkatan</th>
                        <th class="px-5 py-3 font-medium">Muhaffizh</th>
                        <th class="px-5 py-3 font-medium">Santri</th>
                        <th class="px-5 py-3 font-medium">Jadwal</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($halaqah as $item)
                        <tr class="tabel-baris hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">{{ $item->nama }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $item->kode }} &middot; {{ $item->jenis_kelamin_label }}
                                </p>
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ $item->angkatan?->nama ?? '—' }}
                                <span class="block text-xs text-slate-500">{{ $item->angkatan?->tahun }}</span>
                            </td>

                            <td class="px-5 py-3">
                                @if ($item->muhaffizh)
                                    @can('muhaffizh.view')
                                        <a href="{{ route('muhaffizh.show', $item->muhaffizh) }}"
                                           class="text-emerald-700 hover:underline">{{ $item->muhaffizh->nama }}</a>
                                    @else
                                        <span class="text-slate-700">{{ $item->muhaffizh->nama }}</span>
                                    @endcan
                                @else
                                    <span class="text-amber-600">Belum ditugaskan</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-slate-700">
                                {{ $item->anggota_aktif_count }}
                                @if ($item->kuota > 0)
                                    <span class="text-slate-400">/ {{ $item->kuota }}</span>
                                    @if ($item->isPenuh())
                                        <x-badge color="rose">penuh</x-badge>
                                    @endif
                                @endif
                            </td>

                            <td class="px-5 py-3 text-xs text-slate-600">
                                {{ $item->jadwal ?: '—' }}
                                @if ($item->ruang)
                                    <span class="block text-slate-400">Ruang {{ $item->ruang }}</span>
                                @endif
                            </td>

                            <td class="px-5 py-3">
                                <x-badge :color="$item->is_aktif ? 'emerald' : 'slate'">
                                    {{ $item->is_aktif ? 'Berjalan' : 'Nonaktif' }}
                                </x-badge>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-0.5">
                                    <x-icon-button icon="eye" label="Lihat detail halaqah"
                                                   :href="route('halaqah.show', $item)" />

                                    @can('halaqah.update')
                                        <x-icon-button icon="pencil" label="Ubah halaqah"
                                                       :href="route('halaqah.edit', $item)" />
                                    @endcan

                                    @can('halaqah.delete')
                                        <x-confirm-delete :action="route('halaqah.destroy', $item)" icon-only
                                            label="Hapus halaqah"
                                            :title="'Hapus '.$item->nama.'?'"
                                            message="Halaqah yang sudah pernah berisi santri tidak dapat dihapus — nonaktifkan saja." />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty-state icon="book" title="Belum ada halaqah"
                                               message="Bentuk halaqah untuk membagi santri satu angkatan ke dalam kelompok binaan." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($halaqah->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $halaqah->links() }}</div>
        @endif
    </x-card>
</x-layouts::app>
