<div x-data="{ zoomImage: null, zoomTitle: '' }">
    {{-- Tab status --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @php
            $tabs = [
                'menunggu' => ['label' => 'Menunggu Verifikasi', 'aktif' => 'bg-amber-600 text-white'],
                'disetujui' => ['label' => 'Disetujui', 'aktif' => 'bg-emerald-600 text-white'],
                'ditolak' => ['label' => 'Ditolak', 'aktif' => 'bg-rose-600 text-white'],
                '' => ['label' => 'Semua', 'aktif' => 'bg-slate-800 text-white'],
            ];
            
            if (auth()->user()?->hasRole('super-admin')) {
                $tabs['sampah'] = ['label' => 'Tong Sampah', 'aktif' => 'bg-slate-900 text-white'];
            }
        @endphp
        @foreach ($tabs as $value => $tab)
            <button type="button" wire:click="pilihStatus('{{ $value }}')"
                    class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium shadow-sm transition duration-150 ease-out active:scale-[0.97] {{ $status === $value ? $tab['aktif'] : 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50 hover:ring-slate-400' }}">
                {{ $tab['label'] }}
                @if ($value !== '')
                    <span class="rounded-full px-1.5 py-0.5 text-xs {{ $status === $value ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">
                        {{ $this->jumlah[$value] ?? 0 }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, kode, NIK, asal kabupaten…"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <select wire:model.live="angkatan"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua angkatan</option>
            @foreach ($daftarAngkatan as $item)
                <option value="{{ $item->id }}">{{ $item->nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="riwayat"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Baru & ulang</option>
            <option value="baru">Pendaftar baru</option>
            <option value="ulang">Pendaftaran ulang</option>
        </select>

        <select wire:model.live="sumber"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua sumber</option>
            <option value="mandiri">Pendaftaran mandiri</option>
            <option value="admin">Input petugas</option>
        </select>

        <div class="grid grid-cols-2 gap-2">
            <input type="date" wire:model.live="dari" title="Didaftarkan dari tanggal"
                   class="block w-full rounded-lg border-0 px-2 py-2 text-xs ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <input type="date" wire:model.live="sampai" title="Didaftarkan sampai tanggal"
                   class="block w-full rounded-lg border-0 px-2 py-2 text-xs ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>
    </div>

    {{-- Baris Pengurutan (Sorting Controls) --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 shadow-sm text-xs">
        <div class="flex items-center gap-1.5 flex-wrap">
            <span class="font-bold text-slate-700">Urutkan Berdasarkan:</span>
            <button type="button" wire:click="sortBy('didaftarkan_pada')"
                    class="rounded-lg px-2.5 py-1 font-semibold transition-all {{ $sortField === 'didaftarkan_pada' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                Tanggal {{ $sortField === 'didaftarkan_pada' ? ($sortDirection === 'desc' ? '↓ Terbaru' : '↑ Terlama') : '' }}
            </button>
            <button type="button" wire:click="sortBy('nama')"
                    class="rounded-lg px-2.5 py-1 font-semibold transition-all {{ $sortField === 'nama' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                Nama Peserta {{ $sortField === 'nama' ? ($sortDirection === 'asc' ? '↑ A-Z' : '↓ Z-A') : '' }}
            </button>
            <button type="button" wire:click="sortBy('jenis_kelamin')"
                    class="rounded-lg px-2.5 py-1 font-semibold transition-all {{ $sortField === 'jenis_kelamin' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                Jenis Kelamin {{ $sortField === 'jenis_kelamin' ? ($sortDirection === 'asc' ? '↑ L-P' : '↓ P-L') : '' }}
            </button>
            <button type="button" wire:click="sortBy('kabupaten_kota')"
                    class="rounded-lg px-2.5 py-1 font-semibold transition-all {{ $sortField === 'kabupaten_kota' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-100' }}">
                Asal Kabupaten {{ $sortField === 'kabupaten_kota' ? ($sortDirection === 'asc' ? '↑ A-Z' : '↓ Z-A') : '' }}
            </button>
        </div>

        <div class="flex items-center gap-3 text-slate-500">
            <span>{{ $pendaftaran->total() }} Data Pendaftaran</span>
            <button type="button" wire:click="resetFilters" class="text-slate-600 hover:underline font-bold">Reset</button>
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-500">
            Menampilkan {{ $pendaftaran->count() }} dari {{ $pendaftaran->total() }} pendaftaran.
        </p>
        <div class="flex items-center gap-3">
            <span wire:loading.delay class="flex items-center gap-1.5 text-xs text-slate-400">
                <svg class="size-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                    <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z" />
                </svg>
                Memuat…
            </span>
        </div>
    </div>

    {{-- Daftar Pendaftaran --}}
    <div class="space-y-3">
        @forelse ($pendaftaran as $item)
            @php($orang = $item->peserta)
            @php($ulang = ($orang?->pendaftaran_count ?? 1) > 1)

            <x-card wire:key="daftar-{{ $item->id }}" padding="p-4" x-data="{ showKtp: false }">
                <div class="flex flex-wrap items-start gap-4">
                    <!-- Foto Profil Peserta -->
                    <div class="shrink-0">
                        @if ($orang?->foto_url)
                            <button type="button"
                                    @click="zoomImage = '{{ $orang->foto_url }}'; zoomTitle = 'Foto Profil - {{ addslashes($orang->nama) }}'"
                                    title="Klik untuk Auto-Zoom Foto Profil"
                                    class="group relative block size-12 overflow-hidden rounded-full ring-2 ring-emerald-500/30 transition-all hover:scale-105 hover:ring-emerald-600 focus:outline-none shadow-sm">
                                <img src="{{ $orang->foto_url }}" alt="Foto {{ $orang->nama }}" class="size-full object-cover">
                                <span class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity text-white text-xs">
                                    🔍
                                </span>
                            </button>
                        @else
                            <span class="grid size-12 place-items-center rounded-full bg-emerald-100 text-xs font-black text-emerald-900 border border-emerald-200 shadow-sm">
                                {{ $orang?->initials ?? '?' }}
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('peserta.show', $orang) }}" class="font-bold text-base text-slate-900 hover:underline">
                                {{ $orang?->nama ?? '—' }}
                            </a>
                            <x-badge :color="$orang?->jenis_kelamin === 'L' ? 'sky' : 'rose'">
                                {{ $orang?->jenis_kelamin_label ?? '—' }}
                            </x-badge>
                            <x-badge :color="$item->status_pendaftaran_color">{{ $item->status_pendaftaran_label }}</x-badge>
                            <x-badge :color="$item->sumber_pendaftaran === 'mandiri' ? 'sky' : 'slate'">
                                {{ $item->sumber_pendaftaran === 'mandiri' ? 'mandiri' : 'petugas' }}
                            </x-badge>
                            @if ($ulang)
                                <x-badge color="amber">pendaftaran ke-{{ $orang->pendaftaran_count }}</x-badge>
                            @endif
                            @if ($orang && ! $orang->boleh_mendaftar_lagi)
                                <x-badge color="rose">dicekal</x-badge>
                            @endif
                        </div>

                        <p class="mt-0.5 font-mono text-xs text-slate-500">
                            {{ $item->kode_pendaftaran }}
                            @if ($item->nomor_induk)
                                &middot; NIS {{ $item->nomor_induk }}
                            @endif
                        </p>

                        @if ($ulang)
                            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                Orang ini sudah pernah terdaftar — identitasnya kemungkinan sudah pernah
                                diverifikasi. <a href="{{ route('peserta.show', $orang) }}"
                                   class="font-medium underline">Lihat riwayatnya</a>.
                            </p>
                        @endif

                        <dl class="mt-3 grid gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Angkatan</dt>
                                <dd class="truncate text-slate-800 font-medium">{{ $item->angkatan?->nama ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">NIK</dt>
                                <dd class="truncate font-mono text-xs text-slate-800 font-bold">{{ $orang?->nik ?: '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Jenis Kelamin</dt>
                                <dd class="text-slate-800 font-medium">{{ $orang?->jenis_kelamin_label ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Asal Kabupaten</dt>
                                <dd class="truncate text-slate-800 font-bold text-emerald-800">
                                    @if ($orang?->kewarganegaraan === 'WNA')
                                        {{ $orang->negara ?: 'Luar Negeri' }} ({{ $orang->kabupaten_kota ?: 'WNA' }})
                                    @else
                                        {{ $orang?->kabupaten_kota ?: '—' }}{{ $orang?->provinsi ? ', '.$orang->provinsi : '' }}
                                    @endif
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Email & HP</dt>
                                <dd class="truncate text-slate-800">{{ $orang?->email ?: '-' }} · {{ $orang?->no_hp ?: '-' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Didaftarkan</dt>
                                <dd class="text-slate-800">
                                    {{ $item->didaftarkan_pada?->translatedFormat('d M Y H:i') ?? '—' }}
                                </dd>
                            </div>
                        </dl>

                        {{-- Panel Validasi Pembayaran Sederhana Petugas --}}
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/80 p-3 shadow-sm text-xs space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-bold text-slate-500 uppercase tracking-wide text-[10px]">Paket Program:</span>
                                    <p class="font-bold text-slate-900 text-xs">
                                        {{ $item->paket_program_label }}
                                        <span class="text-emerald-700 font-extrabold">({{ $item->formatted_biaya_program }})</span>
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Opsi 1: Status Biaya Pendaftaran (Rp 100.000) --}}
                                    <button type="button" wire:click="toggleBayarPendaftaran({{ $item->id }})"
                                            title="Klik untuk mengubah status bayar pendaftaran"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-extrabold transition-all border shadow-sm active:scale-95 cursor-pointer {{ $item->status_pembayaran_pendaftaran === 'lunas' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-50 text-rose-900 border-rose-300 hover:bg-rose-100' }}">
                                        @if ($item->status_pembayaran_pendaftaran === 'lunas')
                                            <x-icon name="check-badge" class="size-4 text-emerald-700" />
                                            <span>Biaya Reg (100rb): Sudah Bayar</span>
                                        @else
                                            <x-icon name="exclamation-triangle" class="size-4 text-rose-600" />
                                            <span>Biaya Reg (100rb): Belum Bayar</span>
                                        @endif
                                    </button>

                                    {{-- Opsi 2: Status Biaya Program --}}
                                    <button type="button" wire:click="toggleBayarProgram({{ $item->id }})"
                                            title="Klik untuk mengubah status bayar biaya program"
                                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-extrabold transition-all border shadow-sm active:scale-95 cursor-pointer {{ $item->status_pembayaran_program === 'lunas' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-50 text-rose-900 border-rose-300 hover:bg-rose-100' }}">
                                        @if ($item->status_pembayaran_program === 'lunas')
                                            <x-icon name="check-badge" class="size-4 text-emerald-700" />
                                            <span>Biaya Program: Sudah Bayar</span>
                                        @else
                                            <x-icon name="exclamation-triangle" class="size-4 text-rose-600" />
                                            <span>Biaya Program: Belum Bayar</span>
                                        @endif
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Pratinjau KTP Drawer --}}
                        @if ($orang?->ktp_path)
                            <div x-show="showKtp" x-collapse x-cloak class="mt-3.5 rounded-xl border border-slate-200 bg-slate-50 p-3 shadow-inner">
                                <div class="flex items-center justify-between mb-2 border-b border-slate-200/80 pb-2">
                                    <p class="text-xs font-bold text-slate-800">
                                        📷 Pratinjau KTP/KK · NIK: <span class="font-mono text-emerald-700">{{ $orang->nik ?: '—' }}</span>
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                @click="zoomImage = '{{ route('pendaftaran.ktp', $orang) }}'; zoomTitle = 'Dokumen KTP/KK - {{ addslashes($orang->nama) }}'"
                                                class="text-xs font-bold text-emerald-700 hover:underline">
                                            🔍 Auto Zoom
                                        </button>
                                        <a href="{{ route('pendaftaran.ktp', $orang) }}" target="_blank" class="text-xs text-slate-500 hover:underline">
                                            Tab Baru ↗
                                        </a>
                                    </div>
                                </div>
                                <div class="text-center">
                                    @php($ext = strtolower(pathinfo($orang->ktp_path, PATHINFO_EXTENSION)))
                                    @if ($ext === 'pdf')
                                        <iframe src="{{ route('pendaftaran.ktp', $orang) }}" class="h-56 w-full rounded-lg border border-slate-200"></iframe>
                                    @else
                                        <img src="{{ route('pendaftaran.ktp', $orang) }}" alt="KTP/KK {{ $orang->nama }}"
                                             @click="zoomImage = '{{ route('pendaftaran.ktp', $orang) }}'; zoomTitle = 'Dokumen KTP/KK - {{ addslashes($orang->nama) }}'"
                                             class="max-h-60 rounded-lg border border-slate-200 object-contain mx-auto bg-white shadow-sm cursor-pointer hover:scale-[1.02] transition-transform">
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if ($item->status_pendaftaran === 'ditolak' && $item->alasan_penolakan)
                            <p class="mt-3 rounded-lg bg-rose-50 p-3 text-xs text-rose-800">
                                <span class="font-medium">Alasan penolakan:</span> {{ $item->alasan_penolakan }}
                            </p>
                        @endif

                        @if ($item->ditinjau_pada)
                            <p class="mt-2 text-xs text-slate-400">
                                Ditinjau {{ $item->ditinjau_pada->translatedFormat('d M Y H:i') }}
                                oleh {{ $item->peninjau?->name ?? 'petugas' }}
                            </p>
                        @endif
                    </div>

                    {{-- Aksi --}}
                    <div class="flex w-full shrink-0 flex-wrap items-center gap-1.5 sm:w-auto">
                        @if ($status === 'sampah')
                            @if (auth()->user()?->hasRole('super-admin'))
                                <button type="button" wire:click="pulihkanPendaftaran({{ $item->id }})" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-sm transition-all bg-sky-50 text-sky-800 border border-sky-300 hover:bg-sky-100">
                                    <x-icon name="arrow-path" class="size-4 text-sky-600" />
                                    <span>Pulihkan</span>
                                </button>
                                <button type="button" wire:click="hapusPermanenPendaftaran({{ $item->id }})" wire:confirm="Yakin ingin menghapus permanen data ini? Data tidak dapat dikembalikan." class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-sm transition-all bg-rose-50 text-rose-800 border border-rose-300 hover:bg-rose-100">
                                    <x-icon name="trash" class="size-4 text-rose-600" />
                                    <span>Hapus Permanen</span>
                                </button>
                            @endif
                        @else
                            <x-icon-button icon="eye" label="Lihat detail peserta"
                                           :href="route('peserta.show', $orang)" />

                            @if ($orang?->ktp_path)
                                @can('peserta.view')
                                    <button type="button" @click="showKtp = !showKtp"
                                            title="Pratinjau Berkas KTP/KK Langsung"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-sm transition-all bg-emerald-50 text-emerald-800 border border-emerald-300 hover:bg-emerald-100">
                                        <x-icon name="identification" class="size-4 text-emerald-600" />
                                        <span x-text="showKtp ? 'Tutup KTP/KK' : 'Lihat KTP/KK'"></span>
                                    </button>
                                @endcan
                            @else
                                <x-icon-button icon="identification" label="Tanpa berkas KTP/KK" disabled />
                            @endif
                            
                            @if (auth()->user()?->can('peserta.delete') || $item->status_pendaftaran === 'ditolak')
                                <button type="button" wire:click="hapusPendaftaran({{ $item->id }})" wire:confirm="Yakin ingin menghapus data ini ke tong sampah?" title="Hapus Data (Soft Delete)" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-bold shadow-sm transition-all bg-rose-50 text-rose-800 border border-rose-300 hover:bg-rose-100 ml-auto">
                                    <x-icon name="trash" class="size-4 text-rose-600" />
                                    <span>Hapus</span>
                                </button>
                            @endif
                        @endif

                        @if ($item->isMenunggu())
                            @can('peserta.approve')
                                <form method="POST" action="{{ route('pendaftaran.setujui', $item) }}">
                                    @csrf
                                    <x-button type="submit" size="sm" icon="check">Setujui</x-button>
                                </form>

                                <div x-data="{ open: false }">
                                    <x-button variant="danger" size="sm" icon="x-mark" @click="open = true">Tolak</x-button>

                                    <template x-teleport="body">
                                        <div x-show="open" x-cloak @keydown.escape.window="open = false"
                                             class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                                            <div x-show="open" @click="open = false"
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-150"
                                                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                                 class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>

                                            <form method="POST" action="{{ route('pendaftaran.tolak', $item) }}"
                                                  x-show="open"
                                                  x-transition:enter="transition ease-out duration-200"
                                                  x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                                                  x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                  x-transition:leave="transition ease-in duration-150"
                                                  x-transition:leave-start="opacity-100 scale-100"
                                                  x-transition:leave-end="opacity-0 scale-95"
                                                  class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl ring-1 ring-black/5">
                                                @csrf
                                                <h3 class="font-semibold text-slate-900">Tolak pendaftaran {{ $orang?->nama }}?</h3>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    Alasan di bawah ini akan dikirim ke email pendaftar, jadi tuliskan
                                                    dengan jelas dan sopan.
                                                </p>

                                                <textarea name="alasan_penolakan" rows="3" required minlength="10"
                                                          placeholder="mis. Foto KTP/KK tidak terbaca, mohon unggah ulang dengan pencahayaan lebih baik."
                                                          class="mt-3 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-rose-500"></textarea>

                                                <div class="mt-4 flex justify-end gap-2">
                                                    <x-button type="button" variant="secondary" size="sm" @click="open = false">Batal</x-button>
                                                    <x-button type="submit" variant="danger" size="sm" icon="x-mark">Kirim Penolakan</x-button>
                                                </div>
                                            </form>
                                        </div>
                                    </template>
                                </div>
                            @endcan
                        @endif
                    </div>
                </div>
            </x-card>
        @empty
            <x-card>
                <x-empty-state
                    :title="$status === 'menunggu' ? 'Tidak ada pendaftaran yang menunggu' : 'Tidak ada data yang cocok'"
                    :message="$status === 'menunggu'
                        ? 'Semua pendaftaran yang masuk sudah ditinjau. Pendaftaran baru akan muncul di sini.'
                        : 'Coba ubah kata kunci atau reset filter.'" />
            </x-card>
        @endforelse
    </div>

    @if ($pendaftaran->hasPages())
        <div class="mt-4">{{ $pendaftaran->links() }}</div>
    @endif

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

    {{-- Modal Verifikasi Pembayaran Petugas --}}
    @if ($selectedPendaftaranId && $selectedPendaftaran)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm animate-fade-in" x-data>
            <div class="w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl border border-slate-100 space-y-5 animate-scale-up" @click.outside="$wire.tutupModalPembayaran()">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                            <x-icon name="check-badge" class="size-5 text-emerald-600" />
                            <span>Verifikasi Pembayaran Santri</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ $selectedPendaftaran->peserta?->nama }} · Kode: <span class="font-mono text-emerald-700 font-bold">{{ $selectedPendaftaran->kode_pendaftaran }}</span>
                        </p>
                    </div>
                    <button type="button" wire:click="tutupModalPembayaran()" class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors">
                        ✕
                    </button>
                </div>

                {{-- Rincian Tagihan --}}
                <div class="rounded-2xl bg-emerald-50/80 border border-emerald-200 p-4 text-xs space-y-2 text-emerald-950">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Paket Program:</span>
                        <span class="font-bold text-slate-900">{{ $selectedPendaftaran->paket_program_label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Biaya Registrasi Pendaftaran:</span>
                        <span class="font-bold text-slate-900">{{ $selectedPendaftaran->formatted_biaya_pendaftaran }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-600">Biaya Program Karantina:</span>
                        <span class="font-bold text-slate-900">{{ $selectedPendaftaran->formatted_biaya_program }}</span>
                    </div>
                    <div class="border-t border-emerald-200 pt-2 flex justify-between font-extrabold text-sm text-emerald-950">
                        <span>Total Kewajiban Pembayaran:</span>
                        <span class="text-emerald-700">Rp {{ number_format((float) ($selectedPendaftaran->biaya_pendaftaran + $selectedPendaftaran->biaya_program), 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- Form Status Pembayaran --}}
                <div class="space-y-4 text-xs">
                    {{-- Status Biaya Registrasi Rp 100.000 --}}
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Status Biaya Registrasi Pendaftaran (Rp 100.000)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('editStatusPendaftaran', 'pending')"
                                    class="rounded-xl border p-2.5 text-center font-bold transition-all {{ $editStatusPendaftaran === 'pending' ? 'bg-amber-100 text-amber-900 border-amber-400 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                ⏳ Belum Lunas
                            </button>
                            <button type="button" wire:click="$set('editStatusPendaftaran', 'lunas')"
                                    class="rounded-xl border p-2.5 text-center font-bold transition-all {{ $editStatusPendaftaran === 'lunas' ? 'bg-emerald-100 text-emerald-900 border-emerald-400 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                ✅ Lunas (Rp 100rb)
                            </button>
                        </div>
                    </div>

                    {{-- Status Biaya Program --}}
                    <div>
                        <label class="block font-bold text-slate-700 mb-1.5">Status Biaya Program Karantina Tahfizh</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" wire:click="$set('editStatusProgram', 'pending')"
                                    class="rounded-xl border p-2 text-center font-bold transition-all {{ $editStatusProgram === 'pending' ? 'bg-rose-100 text-rose-900 border-rose-400 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                ❌ Belum Bayar
                            </button>
                            <button type="button" wire:click="$set('editStatusProgram', 'dp_sebagian')"
                                    class="rounded-xl border p-2 text-center font-bold transition-all {{ $editStatusProgram === 'dp_sebagian' ? 'bg-amber-100 text-amber-900 border-amber-400 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                💵 DP / Dicicil
                            </button>
                            <button type="button" wire:click="$set('editStatusProgram', 'lunas')"
                                    class="rounded-xl border p-2 text-center font-bold transition-all {{ $editStatusProgram === 'lunas' ? 'bg-emerald-100 text-emerald-900 border-emerald-400 shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100' }}">
                                ✅ Lunas Program
                            </button>
                        </div>
                    </div>

                    {{-- Catatan Pembayaran / No. Struk --}}
                    <div>
                        <label for="catatanPembayaran" class="block font-bold text-slate-700 mb-1">Catatan Pembayaran / Nomor Struk Transfer (Opsional)</label>
                        <input type="text" id="catatanPembayaran" wire:model="catatanPembayaran" placeholder="misal: Tunai di lokasi / Transfer BCA a.n Santri"
                               class="w-full rounded-xl border-0 p-2.5 text-xs ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 shadow-sm">
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <button type="button" wire:click="quickLunasSemua({{ $selectedPendaftaran->id }})"
                            class="inline-flex items-center gap-1 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 px-3 py-2 text-xs font-extrabold transition-all">
                        <x-icon name="bolt" class="size-4 text-amber-600" />
                        <span>⚡ 1-Klik Lunas Semua</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="tutupModalPembayaran()" class="rounded-xl bg-slate-100 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-200 transition-all">
                            Batal
                        </button>
                        <button type="button" wire:click="simpanVerifikasiPembayaran()" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 text-xs font-extrabold shadow-md transition-all">
                            Simpan Verifikasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
