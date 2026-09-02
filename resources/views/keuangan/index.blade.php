<x-layouts::app :title="'Rekap Keuangan'">
    <x-page-header title="Rekap Keuangan" subtitle="Ringkasan pemasukan dari pendaftaran dan biaya program." />

    <!-- Kalkulasi/Statistik -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-blue-100 p-3 text-blue-600">
                <x-icon name="currency-dollar" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Tagihan</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
                <x-icon name="check-circle" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pemasukan</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-rose-100 p-3 text-rose-600">
                <x-icon name="clock" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Piutang (Pending)</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-indigo-100 p-3 text-indigo-600">
                <x-icon name="users" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Status Pendaftar</p>
                <p class="text-lg font-semibold text-slate-900">{{ $jumlahLunas }} Lunas / {{ $jumlahPending }} Pending</p>
            </div>
        </x-card>
    </div>

    <!-- Filter Tab -->
    <div class="mb-4 flex gap-2">
        <a href="{{ route('keuangan.index') }}" class="px-4 py-2 text-sm rounded-lg {{ !request('status') ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Semua</a>
        <a href="{{ route('keuangan.index', ['status' => 'pending']) }}" class="px-4 py-2 text-sm rounded-lg {{ request('status') == 'pending' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Pending</a>
        <a href="{{ route('keuangan.index', ['status' => 'lunas']) }}" class="px-4 py-2 text-sm rounded-lg {{ request('status') == 'lunas' ? 'bg-indigo-50 text-indigo-700 font-medium' : 'text-slate-600 hover:bg-slate-50' }}">Lunas</a>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 flex gap-2 items-center">
            <x-icon name="check-circle" class="h-5 w-5" />
            {{ session('success') }}
        </div>
    @endif

    <!-- Tabel Pendaftaran -->
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Pendaftar & Angkatan</th>
                        <th class="px-5 py-3 font-medium">Biaya Pendaftaran</th>
                        <th class="px-5 py-3 font-medium">Biaya Program</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pendaftarans as $pendaftaran)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-900">{{ $pendaftaran->peserta?->nama ?? 'Peserta Terhapus' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $pendaftaran->kode_pendaftaran }} &middot; {{ $pendaftaran->angkatan?->nama ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-900">{{ $pendaftaran->formatted_biaya_pendaftaran }}</div>
                                <div class="mt-1">
                                    <x-badge :color="$pendaftaran->status_pembayaran_pendaftaran == 'lunas' || $pendaftaran->status_pembayaran_pendaftaran == 'bebas_biaya' ? 'emerald' : 'amber'">
                                        {{ str_replace('_', ' ', strtoupper($pendaftaran->status_pembayaran_pendaftaran)) }}
                                    </x-badge>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-slate-900">{{ $pendaftaran->formatted_biaya_program }}</div>
                                <div class="mt-1">
                                    <x-badge :color="$pendaftaran->status_pembayaran_program == 'lunas' || $pendaftaran->status_pembayaran_program == 'bebas_biaya' ? 'emerald' : 'amber'">
                                        {{ str_replace('_', ' ', strtoupper($pendaftaran->status_pembayaran_program)) }}
                                    </x-badge>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                @can('keuangan.update')
                                    <button type="button" onclick="document.getElementById('edit-modal-{{ $pendaftaran->id }}').classList.remove('hidden')" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        Update
                                    </button>
                                @endcan
                            </td>
                        </tr>

                        <!-- Modal Edit Status Pembayaran -->
                        <div id="edit-modal-{{ $pendaftaran->id }}" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl relative text-left">
                                <h3 class="text-lg font-bold text-slate-900 mb-4">Update Status Pembayaran</h3>
                                <form action="{{ route('keuangan.update', $pendaftaran) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-slate-700">Peserta</label>
                                        <p class="text-slate-900 font-medium">{{ $pendaftaran->peserta?->nama }}</p>
                                    </div>

                                    <div class="mb-4">
                                        <label for="status_pembayaran_pendaftaran_{{ $pendaftaran->id }}" class="block text-sm font-medium text-slate-700">Status Pendaftaran ({{ $pendaftaran->formatted_biaya_pendaftaran }})</label>
                                        <select id="status_pembayaran_pendaftaran_{{ $pendaftaran->id }}" name="status_pembayaran_pendaftaran" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="pending" {{ $pendaftaran->status_pembayaran_pendaftaran == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="lunas" {{ $pendaftaran->status_pembayaran_pendaftaran == 'lunas' ? 'selected' : '' }}>Lunas</option>
                                            <option value="bebas_biaya" {{ $pendaftaran->status_pembayaran_pendaftaran == 'bebas_biaya' ? 'selected' : '' }}>Bebas Biaya</option>
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="status_pembayaran_program_{{ $pendaftaran->id }}" class="block text-sm font-medium text-slate-700">Status Program ({{ $pendaftaran->formatted_biaya_program }})</label>
                                        <select id="status_pembayaran_program_{{ $pendaftaran->id }}" name="status_pembayaran_program" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="pending" {{ $pendaftaran->status_pembayaran_program == 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="lunas" {{ $pendaftaran->status_pembayaran_program == 'lunas' ? 'selected' : '' }}>Lunas</option>
                                            <option value="bebas_biaya" {{ $pendaftaran->status_pembayaran_program == 'bebas_biaya' ? 'selected' : '' }}>Bebas Biaya</option>
                                        </select>
                                    </div>

                                    <div class="mb-6">
                                        <label for="catatan_pembayaran_{{ $pendaftaran->id }}" class="block text-sm font-medium text-slate-700">Catatan</label>
                                        <textarea id="catatan_pembayaran_{{ $pendaftaran->id }}" name="catatan_pembayaran" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $pendaftaran->catatan_pembayaran }}</textarea>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <button type="button" onclick="document.getElementById('edit-modal-{{ $pendaftaran->id }}').classList.add('hidden')" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 border border-slate-200">Batal</button>
                                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">Belum ada data pendaftaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">
        {{ $pendaftarans->links() }}
    </div>
</x-layouts::app>
