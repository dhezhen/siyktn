@props(['anggota', 'tujuan'])

@php
    $nama = $anggota->pendaftaran?->peserta?->nama ?? 'Santri ini';
@endphp

{{--
    Dua aksi keanggotaan dalam satu sel tabel. Modalnya dipindahkan ke <body>
    lewat x-teleport supaya tidak terpotong oleh tabel yang overflow-x-auto,
    persis seperti pada x-confirm-delete.
--}}
<div class="flex items-center justify-end gap-2">

    {{-- Pindah halaqah --}}
    <div x-data="{ open: false }" class="inline-flex">
        <x-button icon="switch" size="sm" variant="secondary" @click="open = true">Pindah</x-button>

        <template x-teleport="body">
            <div x-show="open" x-cloak @keydown.escape.window="open = false"
                 class="fixed inset-0 z-[60] flex items-center justify-center p-4">

                <div x-show="open" @click="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md rounded-xl bg-white p-6 text-left shadow-xl ring-1 ring-black/5">

                    <h3 class="font-semibold text-slate-900">Pindahkan {{ $nama }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">
                        Keanggotaan di halaqah sekarang ditutup dan dicatat sebagai riwayat,
                        bukan dihapus.
                    </p>

                    @if ($tujuan->isEmpty())
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                            Belum ada halaqah lain yang sepadan di angkatan ini.
                            Buat halaqah baru terlebih dahulu.
                        </p>

                        <div class="mt-5 flex justify-end">
                            <x-button variant="secondary" size="sm" @click="open = false">Tutup</x-button>
                        </div>
                    @else
                        <form method="POST" action="{{ route('halaqah.anggota.pindah', $anggota) }}" class="mt-4">
                            @csrf
                            @method('PUT')

                            <label for="halaqah_id_{{ $anggota->id }}"
                                   class="mb-1 block text-sm font-medium text-slate-700">Halaqah Tujuan</label>
                            <select name="halaqah_id" id="halaqah_id_{{ $anggota->id }}" required
                                    class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                                @foreach ($tujuan as $pilihan)
                                    <option value="{{ $pilihan->id }}" @disabled($pilihan->isPenuh())>
                                        {{ $pilihan->nama }} ({{ $pilihan->kode }}) —
                                        {{ $pilihan->anggota_aktif_count }}{{ $pilihan->kuota > 0 ? '/'.$pilihan->kuota : '' }} santri
                                        {{ $pilihan->isPenuh() ? '· penuh' : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <label for="alasan_pindah_{{ $anggota->id }}"
                                   class="mb-1 mt-4 block text-sm font-medium text-slate-700">Alasan Pindah</label>
                            <input type="text" name="alasan_pindah" id="alasan_pindah_{{ $anggota->id }}"
                                   maxlength="255" placeholder="Opsional, mis. penyesuaian tingkat hafalan"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

                            <div class="mt-5 flex justify-end gap-2">
                                <x-button variant="secondary" size="sm" @click="open = false">Batal</x-button>
                                <x-button type="submit" size="sm" icon="switch">Pindahkan</x-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </template>
    </div>

    {{-- Keluarkan dari halaqah --}}
    <div x-data="{ open: false }" class="inline-flex">
        <x-button icon="logout" size="sm" variant="danger" @click="open = true">Keluar</x-button>

        <template x-teleport="body">
            <div x-show="open" x-cloak @keydown.escape.window="open = false"
                 class="fixed inset-0 z-[60] flex items-center justify-center p-4">

                <div x-show="open" @click="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md rounded-xl bg-white p-6 text-left shadow-xl ring-1 ring-black/5">

                    <div class="flex items-start gap-3">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-amber-100 text-amber-600">
                            <x-icon name="warning" class="size-5" />
                        </span>
                        <div>
                            <h3 class="font-semibold text-slate-900">Keluarkan {{ $nama }}?</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-500">
                                Santri menjadi tanpa halaqah dan bisa ditempatkan lagi kapan saja.
                                Riwayat pembimbingannya tetap tersimpan.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('halaqah.anggota.keluar', $anggota) }}" class="mt-4">
                        @csrf
                        @method('DELETE')

                        <label for="alasan_keluar_{{ $anggota->id }}"
                               class="mb-1 block text-sm font-medium text-slate-700">Alasan</label>
                        <input type="text" name="alasan_pindah" id="alasan_keluar_{{ $anggota->id }}"
                               maxlength="255" placeholder="Opsional, mis. izin pulang"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">

                        <div class="mt-5 flex justify-end gap-2">
                            <x-button variant="secondary" size="sm" @click="open = false">Batal</x-button>
                            <x-button type="submit" variant="danger" size="sm" icon="logout">Keluarkan</x-button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>
</div>
