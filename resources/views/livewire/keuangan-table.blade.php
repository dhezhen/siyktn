<div>
    <!-- Area Filter: Diseragamkan dengan modul Halaqah & menggunakan Livewire -->
    <div class="mb-4 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama peserta..."
               class="min-w-56 flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

        <select wire:model.live="angkatan" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua angkatan</option>
            @foreach($angkatans as $item)
                <option value="{{ $item->id }}">{{ $item->nama }}</option>
            @endforeach
        </select>
        
        <select wire:model.live="status" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            <option value="lunas">Lunas</option>
            <option value="pending">Pending</option>
        </select>
    </div>

    <!-- Tabel Pendaftaran -->
    <x-card padding="p-0" class="relative">
        <div wire:loading.delay class="absolute inset-0 z-10 bg-white/50 backdrop-blur-[1px] rounded-xl flex items-center justify-center">
            <div class="flex items-center gap-2 text-emerald-700 font-medium">
                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memuat data...
            </div>
        </div>

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
                        <tr class="tabel-baris hover:bg-slate-50 group">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900 group-hover:text-emerald-700 transition-colors">{{ $pendaftaran->peserta?->nama ?? 'Peserta Terhapus' }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $pendaftaran->kode_pendaftaran }} &middot; {{ $pendaftaran->angkatan?->nama ?? '-' }}
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <div class="text-slate-900 font-medium">{{ $pendaftaran->formatted_biaya_pendaftaran }}</div>
                                <div class="mt-1">
                                    <x-badge :color="$pendaftaran->status_pembayaran_pendaftaran == 'lunas' || $pendaftaran->status_pembayaran_pendaftaran == 'bebas_biaya' ? 'emerald' : 'amber'">
                                        {{ str_replace('_', ' ', strtoupper($pendaftaran->status_pembayaran_pendaftaran)) }}
                                    </x-badge>
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <div class="text-slate-900 font-medium">{{ $pendaftaran->formatted_biaya_program }}</div>
                                <div class="mt-1">
                                    <x-badge :color="$pendaftaran->status_pembayaran_program == 'lunas' || $pendaftaran->status_pembayaran_program == 'bebas_biaya' ? 'emerald' : 'amber'">
                                        {{ str_replace('_', ' ', strtoupper($pendaftaran->status_pembayaran_program)) }}
                                    </x-badge>
                                </div>
                            </td>
                            <td class="px-5 py-3 align-top">
                                <div class="flex items-center justify-end gap-2">
                                    @can('keuangan.update')
                                        <x-icon-button icon="pencil" label="Ubah" wire:click="editStatus({{ $pendaftaran->id }})" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <x-empty-state icon="currency-dollar" title="Belum Ada Data" description="Belum ada satupun peserta yang terdaftar pada sistem." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if ($pendaftarans->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $pendaftarans->links() }}</div>
        @endif
    </x-card>

    <!-- Modal Edit Status Pembayaran (Single Instance) -->
    @can('keuangan.update')
    <x-modal name="edit-modal" title="Update Status Pembayaran" width="max-w-md">
        <form wire:submit="simpanPerubahan" id="form-edit-keuangan">
            <div class="space-y-5 text-left">
                <!-- Status Pendaftaran -->
                <div>
                    <label for="editStatusPendaftaran" class="block text-sm font-medium text-slate-700">Status Registrasi (Pendaftaran)</label>
                    <select id="editStatusPendaftaran" wire:model="editStatusPendaftaran" class="mt-1.5 block w-full rounded-lg border-slate-300 py-2 sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm">
                        <option value="pending">Pending</option>
                        <option value="lunas">Lunas</option>
                        <option value="bebas_biaya">Bebas Biaya</option>
                    </select>
                </div>

                <!-- Status Program -->
                <div>
                    <label for="editStatusProgram" class="block text-sm font-medium text-slate-700">Status Program (Karantina)</label>
                    <select id="editStatusProgram" wire:model="editStatusProgram" class="mt-1.5 block w-full rounded-lg border-slate-300 py-2 sm:text-sm focus:border-emerald-500 focus:ring-emerald-500 shadow-sm">
                        <option value="pending">Pending</option>
                        <option value="lunas">Lunas</option>
                        <option value="bebas_biaya">Bebas Biaya</option>
                    </select>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label for="editCatatan" class="block text-sm font-medium text-slate-700">Catatan Tambahan</label>
                    <textarea id="editCatatan" wire:model="editCatatan" rows="3" class="mt-1.5 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 placeholder:text-slate-400" placeholder="Opsional: Tuliskan catatan khusus atau referensi transfer..."></textarea>
                </div>
            </div>
        </form>
        
        <x-slot:footer>
            <x-button variant="secondary" @click="open = false" wire:loading.attr="disabled">Batal</x-button>
            <x-button type="submit" form="form-edit-keuangan" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="simpanPerubahan">Simpan Perubahan</span>
                <span wire:loading wire:target="simpanPerubahan">Menyimpan...</span>
            </x-button>
        </x-slot:footer>
    </x-modal>
    @endcan
</div>
