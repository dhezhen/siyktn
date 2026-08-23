<div>
    {{-- Tab status --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ([
            'menunggu' => ['label' => 'Menunggu Verifikasi', 'aktif' => 'bg-amber-600 text-white', 'diam' => 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50'],
            'disetujui' => ['label' => 'Disetujui', 'aktif' => 'bg-emerald-600 text-white', 'diam' => 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50'],
            'ditolak' => ['label' => 'Ditolak', 'aktif' => 'bg-rose-600 text-white', 'diam' => 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50'],
            '' => ['label' => 'Semua', 'aktif' => 'bg-slate-800 text-white', 'diam' => 'bg-white text-slate-600 ring-1 ring-slate-300 hover:bg-slate-50'],
        ] as $value => $tab)
            <button type="button" wire:click="pilihStatus('{{ $value }}')"
                    class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition {{ $status === $value ? $tab['aktif'] : $tab['diam'] }}">
                {{ $tab['label'] }}
                @if ($value !== '')
                    <span class="rounded-full px-1.5 py-0.5 text-xs {{ $status === $value ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">
                        {{ $this->jumlah[$value] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Filter --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, kode, NIK, email, no HP…"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <select wire:model.live="angkatan"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua angkatan</option>
            @foreach ($daftarAngkatan as $item)
                <option value="{{ $item->id }}">{{ $item->nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="sumber"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua sumber</option>
            <option value="mandiri">Pendaftaran mandiri</option>
            <option value="admin">Input petugas</option>
        </select>

        <div>
            <input type="date" wire:model.live="dari" title="Didaftarkan dari tanggal"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <div>
            <input type="date" wire:model.live="sampai" title="Didaftarkan sampai tanggal"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-slate-500">
            Menampilkan {{ $pendaftaran->count() }} dari {{ $pendaftaran->total() }} pendaftaran.
        </p>
        <div class="flex items-center gap-3">
            <span wire:loading class="text-xs text-slate-400">Memuat…</span>
            <button type="button" wire:click="resetFilters" class="text-xs text-slate-500 hover:underline">Reset filter</button>
        </div>
    </div>

    {{-- Daftar --}}
    <div class="space-y-3">
        @forelse ($pendaftaran as $item)
            <x-card wire:key="daftar-{{ $item->id }}" padding="p-4">
                <div class="flex flex-wrap items-start gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('peserta.show', $item) }}" class="font-medium text-slate-900 hover:underline">
                                {{ $item->nama }}
                            </a>
                            <x-badge :color="$item->status_pendaftaran_color">{{ $item->status_pendaftaran_label }}</x-badge>
                            <x-badge :color="$item->sumber_pendaftaran === 'mandiri' ? 'sky' : 'slate'">
                                {{ $item->sumber_pendaftaran === 'mandiri' ? 'mandiri' : 'petugas' }}
                            </x-badge>
                        </div>

                        <p class="mt-1 font-mono text-xs text-slate-500">
                            {{ $item->kode_pendaftaran ?: '—' }}
                            @if ($item->nomor_induk)
                                &middot; NIS {{ $item->nomor_induk }}
                            @endif
                        </p>

                        <dl class="mt-3 grid gap-x-6 gap-y-1.5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Angkatan</dt>
                                <dd class="truncate text-slate-800">{{ $item->angkatan?->nama ?? '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">NIK</dt>
                                <dd class="truncate font-mono text-xs text-slate-800">{{ $item->nik ?: '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">L/P</dt>
                                <dd class="text-slate-800">{{ $item->jenis_kelamin_label }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Email</dt>
                                <dd class="truncate text-slate-800">{{ $item->email ?: '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">No HP</dt>
                                <dd class="truncate text-slate-800">{{ $item->no_hp ?: '—' }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-slate-500">Didaftarkan</dt>
                                <dd class="text-slate-800">
                                    {{ $item->didaftarkan_pada?->translatedFormat('d M Y H:i') ?? '—' }}
                                </dd>
                            </div>
                        </dl>

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
                    <div class="flex w-full shrink-0 flex-wrap items-center gap-2 sm:w-auto">
                        @if ($item->ktp_path)
                            @can('peserta.view')
                                <x-button :href="route('pendaftaran.ktp', $item)" variant="secondary" size="sm" target="_blank">
                                    Lihat KTP
                                </x-button>
                            @endcan
                        @else
                            <span class="text-xs text-slate-400">Tanpa berkas KTP</span>
                        @endif

                        @if ($item->isMenunggu())
                            @can('peserta.approve')
                                <form method="POST" action="{{ route('pendaftaran.setujui', $item) }}">
                                    @csrf
                                    <x-button type="submit" size="sm">Setujui</x-button>
                                </form>

                                <div x-data="{ open: false }">
                                    <x-button variant="danger" size="sm" @click="open = true">Tolak</x-button>

                                    <template x-teleport="body">
                                        <div x-show="open" x-cloak @keydown.escape.window="open = false"
                                             class="fixed inset-0 z-50 flex items-center justify-center p-4">
                                            <div x-show="open" x-transition.opacity @click="open = false"
                                                 class="absolute inset-0 bg-slate-900/50"></div>

                                            <form method="POST" action="{{ route('pendaftaran.tolak', $item) }}"
                                                  x-show="open" x-transition
                                                  class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                                                @csrf
                                                <h3 class="font-semibold text-slate-900">Tolak pendaftaran {{ $item->nama }}?</h3>
                                                <p class="mt-1 text-sm text-slate-500">
                                                    Alasan di bawah ini akan dikirim ke email pendaftar, jadi tuliskan
                                                    dengan jelas dan sopan.
                                                </p>

                                                <textarea name="alasan_penolakan" rows="3" required minlength="10"
                                                          placeholder="mis. Foto KTP tidak terbaca, mohon unggah ulang dengan pencahayaan lebih baik."
                                                          class="mt-3 block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-rose-500"></textarea>

                                                <div class="mt-4 flex justify-end gap-2">
                                                    <x-button type="button" variant="secondary" size="sm" @click="open = false">Batal</x-button>
                                                    <x-button type="submit" variant="danger" size="sm">Kirim Penolakan</x-button>
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
</div>
