@php($editing = $angkatan->exists)

<x-layouts::app :title="$editing ? 'Ubah Angkatan' : 'Tambah Angkatan'">
    <x-page-header :title="$editing ? 'Ubah Angkatan: '.$angkatan->nama : 'Tambah Angkatan'"
                   subtitle="Periode program beserta kuota pesertanya." />

    <form method="POST" action="{{ $editing ? route('angkatan.update', $angkatan) : route('angkatan.store') }}">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card class="mb-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-input name="nama" label="Nama Angkatan" required :value="old('nama', $angkatan->nama)"
                         placeholder="mis. Angkatan 12" />

                <x-input name="kode" label="Kode" required :value="old('kode', $angkatan->kode)"
                         placeholder="mis. AK-12"
                         hint="Dipakai sebagai awalan nomor induk peserta." />

                <x-input name="tahun" type="number" label="Tahun" required
                         :value="old('tahun', $angkatan->tahun)" min="2000" max="2100" />

                <x-select name="status" label="Status" required>
                    @foreach (['persiapan' => 'Persiapan', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $angkatan->status) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input name="tanggal_mulai" type="date" label="Tanggal Mulai"
                         :value="old('tanggal_mulai', $angkatan->tanggal_mulai?->format('Y-m-d'))" />

                <x-input name="tanggal_selesai" type="date" label="Tanggal Selesai"
                         :value="old('tanggal_selesai', $angkatan->tanggal_selesai?->format('Y-m-d'))" />

                <x-input name="kuota" type="number" label="Kuota Peserta" required min="0" max="9999"
                         :value="old('kuota', $angkatan->kuota ?? 0)"
                         hint="Isi 0 bila tidak dibatasi." />
            </div>

            <div class="mt-4">
                <label for="keterangan" class="mb-1 block text-sm font-medium text-slate-700">Keterangan</label>
                <textarea name="keterangan" id="keterangan" rows="3"
                          class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('keterangan', $angkatan->keterangan) }}</textarea>
                @error('keterangan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </x-card>

        <div class="flex justify-end gap-2">
            <x-button :href="route('angkatan.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Angkatan' }}</x-button>
        </div>
    </form>
</x-layouts::app>
