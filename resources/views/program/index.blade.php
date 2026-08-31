<x-layouts::app :title="'Paket Program Karantina'">
    <x-page-header title="Paket Program Karantina Tahfizh" subtitle="Kelola master paket program, durasi, biaya registrasi, dan biaya program.">
        <x-slot:actions>
            @can('program.create')
                <x-button :href="route('program.create')" icon="plus">Tambah Paket Program</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-900 shadow-sm flex items-center gap-2">
            <x-icon name="check-badge" class="size-4 text-emerald-600 shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form method="GET" class="mb-4 flex flex-wrap gap-3">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama, kode, atau keterangan…"
               class="min-w-64 flex-1 rounded-xl border-0 px-3.5 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 shadow-sm">

        <select name="status" class="rounded-xl border-0 px-3.5 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600 shadow-sm">
            <option value="">Semua Status</option>
            <option value="aktif" @selected(request('status') === 'aktif')>Aktif Ditampilkan</option>
            <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
        </select>

        <x-button type="submit" variant="secondary" icon="filter">Filter</x-button>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-medium">Paket Program</th>
                        <th class="px-5 py-3.5 font-medium">Durasi Hari</th>
                        <th class="px-5 py-3.5 font-medium">Biaya Program</th>
                        <th class="px-5 py-3.5 font-medium">Biaya Registrasi</th>
                        <th class="px-5 py-3.5 font-medium">Status</th>
                        <th class="px-5 py-3.5 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($programs as $item)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-extrabold text-slate-900">{{ $item->nama }}</p>
                                <div class="mt-0.5 flex items-center gap-2 text-xs">
                                    <span class="font-mono text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ $item->kode }}</span>
                                    @if ($item->keterangan)
                                        <span class="text-slate-500 truncate max-w-xs">{{ $item->keterangan }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-bold text-slate-800">{{ $item->durasi_hari }} Hari</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-extrabold text-emerald-800 text-sm">{{ $item->formatted_biaya_program }}</span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="font-bold text-slate-700">{{ $item->formatted_biaya_pendaftaran }}</span>
                            </td>

                            <td class="px-5 py-4">
                                @if ($item->is_aktif)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 border border-emerald-300 px-2.5 py-0.5 text-xs font-bold text-emerald-900">
                                        <x-icon name="check-badge" class="size-3.5 text-emerald-700" />
                                        <span>Aktif</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 border border-slate-300 px-2.5 py-0.5 text-xs font-bold text-slate-600">
                                        <span>Nonaktif</span>
                                    </span>
                                @endif
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    @can('program.update')
                                        <x-button :href="route('program.edit', $item)" size="sm" variant="secondary">
                                            Edit
                                        </x-button>
                                    @endcan

                                    @can('program.delete')
                                        <form method="POST" action="{{ route('program.destroy', $item) }}"
                                              onsubmit="event.preventDefault(); Swal.fire({ title: 'Hapus Paket Program?', text: 'Data paket program {{ addslashes($item->nama) }} akan dihapus.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', cancelButtonColor: '#64748b', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((res) => { if(res.isConfirmed) this.submit(); });">
                                            @csrf
                                            @method('DELETE')
                                            <x-button size="sm" variant="danger">
                                                Hapus
                                            </x-button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-500">
                                <x-icon name="document-text" class="mx-auto size-8 text-slate-400 mb-2" />
                                Belum ada data paket program yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($programs->hasPages())
            <div class="border-t border-slate-200 p-4">
                {{ $programs->links() }}
            </div>
        @endif
    </x-card>
</x-layouts::app>
