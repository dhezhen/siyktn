<x-layouts::app :title="($isEdit ? 'Edit Paket Program' : 'Tambah Paket Program')">
    <x-page-header :title="($isEdit ? 'Edit Paket Program' : 'Tambah Paket Program Baru')" subtitle="Isi rincian nama program, durasi hari, dan biaya pendaftaran & program.">
        <x-slot:actions>
            <x-button :href="route('program.index')" variant="secondary" icon="arrow-left">Kembali</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="max-w-2xl">
        <form method="POST" action="{{ $isEdit ? route('program.update', $program) : route('program.store') }}" class="space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <x-card title="Informasi Paket Program">
                <div class="space-y-4">
                    <div>
                        <x-input name="nama" id="nama" label="Nama Paket Program" :value="old('nama', $program->nama)" placeholder="misal: Karantina Tahfizh Al-Quran Program 3 Pekan" required />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input name="kode" id="kode" label="Kode Unik Program" :value="old('kode', $program->kode)" placeholder="misal: PROG-3W" required />
                        </div>
                        <div>
                            <x-input type="number" name="durasi_hari" id="durasi_hari" label="Durasi Program (Jumlah Hari)" :value="old('durasi_hari', $program->durasi_hari)" min="1" max="365" required />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input type="number" name="biaya_program" id="biaya_program" label="Biaya Program (Rp)" :value="old('biaya_program', $program->biaya_program)" min="0" required />
                        </div>
                        <div>
                            <x-input type="number" name="biaya_pendaftaran" id="biaya_pendaftaran" label="Biaya Registrasi / Pendaftaran (Rp)" :value="old('biaya_pendaftaran', $program->biaya_pendaftaran)" min="0" required />
                        </div>
                    </div>

                    <div>
                        <label for="keterangan" class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Deskripsi Ringkas</label>
                        <textarea name="keterangan" id="keterangan" rows="3"
                                  class="w-full rounded-xl border-0 p-3 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600 shadow-sm"
                                  placeholder="Catatan tambahan mengenai fasilitas atau ketentuan program…">{{ old('keterangan', $program->keterangan) }}</textarea>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="is_aktif" value="1" @checked(old('is_aktif', $program->is_aktif))
                                   class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-600">
                            <span class="text-sm font-semibold text-slate-800">Tampilkan & Aktifkan Paket Program Ini di Formulir Pendaftaran</span>
                        </label>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-3">
                <x-button :href="route('program.index')" variant="secondary">Batal</x-button>
                <x-button type="submit" icon="check-badge">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Program' }}</x-button>
            </div>
        </form>
    </div>
</x-layouts::app>
