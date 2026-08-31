@php($editing = $setoran->exists)

<x-layouts::app :title="$editing ? 'Ubah Setoran' : 'Catat Setoran'">
    <x-page-header :title="$editing ? 'Ubah Setoran' : 'Catat Setoran'"
                   :subtitle="$halaqah->nama.' · '.$halaqah->kode.' · '.($halaqah->angkatan?->nama ?? '')">
        <x-slot:actions>
            <x-button :href="route('halaqah.show', $halaqah)" variant="secondary">Kembali ke Halaqah</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($anggota->isEmpty())
        <x-card>
            <x-empty-state icon="users" title="Belum ada santri di halaqah ini"
                           message="Tempatkan santri terlebih dahulu sebelum mencatat setoran." />
        </x-card>
    @else
        <form method="POST" action="{{ $editing ? route('setoran.update', $setoran) : route('setoran.store') }}">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <x-card title="Setoran">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-select name="anggota_halaqah_id" label="Santri" required>
                                @foreach ($anggota as $item)
                                    <option value="{{ $item->id }}"
                                        @selected(old('anggota_halaqah_id', $anggotaTerpilih) == $item->id)>
                                        {{ $item->pendaftaran?->peserta?->nama ?? '—' }}
                                        ({{ $item->pendaftaran?->nomor_induk ?: 'tanpa nomor' }})
                                    </option>
                                @endforeach
                            </x-select>

                            <x-input name="tanggal" type="date" label="Tanggal" required
                                     :max="now()->toDateString()"
                                     :value="old('tanggal', $setoran->tanggal?->format('Y-m-d'))" />

                            <x-select name="jenis" label="Jenis" required
                                      hint="Ziyadah menambah hafalan baru, muraja'ah mengulang yang lama.">
                                @foreach (['ziyadah' => 'Ziyadah (hafalan baru)', 'murajaah' => "Muraja'ah (mengulang)"] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('jenis', $setoran->jenis) === $value)>{{ $label }}</option>
                                @endforeach
                            </x-select>

                            <x-input name="jumlah_halaman" type="number" label="Jumlah Halaman" required
                                     step="0.5" min="0.5" max="100"
                                     :value="old('jumlah_halaman', $setoran->jumlah_halaman ? rtrim(rtrim((string) $setoran->jumlah_halaman, '0'), '.') : '1')"
                                     hint="Boleh setengah halaman, mis. 1,5 — ditulis 1.5." />
                        </div>
                    </x-card>

                    <x-card title="Bacaan" subtitle="Opsional, tetapi sangat membantu saat muraja'ah.">
                        <div class="grid gap-4 sm:grid-cols-4">
                            <x-input name="juz" type="number" label="Juz" min="1" max="30"
                                     :value="old('juz', $setoran->juz)" />

                            <div class="sm:col-span-3">
                                <x-input name="surah" label="Surah" maxlength="60"
                                         :value="old('surah', $setoran->surah)"
                                         placeholder="mis. Al-Baqarah" />
                            </div>

                            <x-input name="ayat_dari" type="number" label="Ayat Awal" min="1" max="286"
                                     :value="old('ayat_dari', $setoran->ayat_dari)" />

                            <x-input name="ayat_sampai" type="number" label="Ayat Akhir" min="1" max="286"
                                     :value="old('ayat_sampai', $setoran->ayat_sampai)" />
                        </div>
                    </x-card>
                </div>

                <div class="space-y-4">
                    <x-card title="Penilaian">
                        <x-select name="kualitas" label="Kualitas" required>
                            @foreach ([
                                'mumtaz' => 'Mumtaz (istimewa)',
                                'jayyid' => 'Jayyid (baik)',
                                'maqbul' => 'Maqbul (cukup)',
                                'perlu_diulang' => 'Perlu Diulang',
                            ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('kualitas', $setoran->kualitas) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>

                        <div class="mt-4">
                            <label for="catatan" class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                            <textarea name="catatan" id="catatan" rows="4"
                                      placeholder="mis. perbaiki mad pada ayat 5"
                                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('catatan', $setoran->catatan) }}</textarea>
                            @error('catatan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </x-card>

                    <x-card title="Penyimak">
                        <p class="text-sm text-slate-800">
                            {{ $halaqah->muhaffizh?->nama ?? 'Belum ada pengampu' }}
                        </p>
                        <p class="mt-2 text-xs leading-relaxed text-slate-500">
                            @if ($editing)
                                Penyimak dibekukan pada saat setoran dicatat dan tidak ikut berubah
                                bila pengampu halaqah diganti.
                            @else
                                Diambil dari pengampu halaqah ini, lalu disimpan permanen di catatan
                                setoran. Nama Anda tercatat terpisah sebagai pengentri.
                            @endif
                        </p>
                    </x-card>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <x-button :href="route('halaqah.show', $halaqah)" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check">{{ $editing ? 'Simpan Perubahan' : 'Catat Setoran' }}</x-button>
            </div>
        </form>
    @endif
</x-layouts::app>
