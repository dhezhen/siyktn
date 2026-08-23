@php($editing = $peserta->exists)

<x-layouts::app :title="$editing ? 'Ubah Peserta' : 'Tambah Peserta'">
    <x-page-header :title="$editing ? 'Ubah Peserta: '.$peserta->nama : 'Tambah Peserta'"
                   subtitle="Data pribadi, wali, dan status keikutsertaan." />

    @if ($angkatan->isEmpty())
        <x-card>
            <x-empty-state title="Belum ada angkatan"
                           message="Peserta harus tergabung dalam sebuah angkatan. Buat angkatan terlebih dahulu.">
                <x-slot:actions>
                    <x-button :href="route('angkatan.create')">Buat Angkatan</x-button>
                </x-slot:actions>
            </x-empty-state>
        </x-card>
    @else
        <form method="POST" action="{{ $editing ? route('peserta.update', $peserta) : route('peserta.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <x-card title="Data Peserta">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-select name="angkatan_id" label="Angkatan" required>
                                @foreach ($angkatan as $item)
                                    <option value="{{ $item->id }}" @selected(old('angkatan_id', $peserta->angkatan_id) == $item->id)>
                                        {{ $item->nama }} ({{ $item->kode }})
                                    </option>
                                @endforeach
                            </x-select>

                            <x-input name="nomor_induk" label="Nomor Induk"
                                     :value="old('nomor_induk', $peserta->nomor_induk)"
                                     :hint="$editing ? 'Ubah dengan hati-hati, nomor ini dipakai di dokumen lain.' : 'Kosongkan agar dibuat otomatis dari kode angkatan.'" />

                            <x-input name="nama" label="Nama Lengkap" required :value="old('nama', $peserta->nama)" />

                            <x-input name="nik" label="NIK" :value="old('nik', $peserta->nik)"
                                     inputmode="numeric" maxlength="16" placeholder="16 digit sesuai KTP" />

                            <x-input name="email" type="email" label="Email" :value="old('email', $peserta->email)"
                                     placeholder="nama@contoh.id"
                                     hint="Bila diisi, peserta menerima email pemberitahuan pendaftaran." />

                            <x-select name="jenis_kelamin" label="Jenis Kelamin" required>
                                <option value="L" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('jenis_kelamin', $peserta->jenis_kelamin) === 'P')>Perempuan</option>
                            </x-select>

                            <x-input name="tempat_lahir" label="Tempat Lahir" :value="old('tempat_lahir', $peserta->tempat_lahir)" />

                            <x-input name="tanggal_lahir" type="date" label="Tanggal Lahir"
                                     :value="old('tanggal_lahir', $peserta->tanggal_lahir?->format('Y-m-d'))" />

                            <x-input name="no_hp" label="Nomor HP" :value="old('no_hp', $peserta->no_hp)"
                                     placeholder="08xxxxxxxxxx" />

                            <x-input name="tanggal_masuk" type="date" label="Tanggal Masuk"
                                     :value="old('tanggal_masuk', $peserta->tanggal_masuk?->format('Y-m-d'))" />
                        </div>

                        <div class="mt-4">
                            <label for="alamat" class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
                            <textarea name="alamat" id="alamat" rows="3"
                                      class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('alamat', $peserta->alamat) }}</textarea>
                            @error('alamat')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </x-card>

                    <x-card title="Data Wali">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-input name="nama_wali" label="Nama Wali" :value="old('nama_wali', $peserta->nama_wali)" />
                            <x-input name="no_hp_wali" label="Nomor HP Wali" :value="old('no_hp_wali', $peserta->no_hp_wali)"
                                     placeholder="08xxxxxxxxxx" />
                        </div>
                    </x-card>
                </div>

                <div class="space-y-4">
                    <x-card title="Foto">
                        <div class="flex items-center gap-4">
                            @if ($peserta->foto_url)
                                <img src="{{ $peserta->foto_url }}" alt="Foto peserta"
                                     class="size-16 rounded-full object-cover ring-1 ring-slate-200">
                            @else
                                <span class="grid size-16 place-items-center rounded-full bg-slate-200 text-lg font-semibold text-slate-500">
                                    {{ $peserta->initials }}
                                </span>
                            @endif
                            <div class="flex-1">
                                <x-input name="foto" type="file" accept="image/*" hint="JPG/PNG, maksimal 2 MB." />
                            </div>
                        </div>
                    </x-card>

                    <x-card title="Berkas KTP">
                        @if ($peserta->ktp_path)
                            <p class="mb-3 flex items-center gap-2 text-sm text-slate-600">
                                <x-icon name="check-circle" class="size-4 text-emerald-600" />
                                Berkas sudah dilampirkan.
                            </p>
                            <x-button :href="route('pendaftaran.ktp', $peserta)" variant="secondary" size="sm"
                                      target="_blank" class="mb-3 w-full">
                                Lihat KTP Terlampir
                            </x-button>
                        @endif

                        <x-input name="ktp" type="file" :label="$peserta->ktp_path ? 'Ganti Berkas' : 'Unggah KTP'"
                                 accept=".jpg,.jpeg,.png,.pdf"
                                 hint="JPG, PNG, atau PDF. Maksimal 2 MB. Disimpan tertutup, tidak dapat diakses publik." />
                    </x-card>

                    <x-card title="Status">
                        <x-select name="status" label="Status Keikutsertaan" required>
                            @foreach (['aktif' => 'Aktif', 'lulus' => 'Lulus', 'keluar' => 'Keluar'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $peserta->status) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select>

                        @if ($editing)
                            <div class="mt-4 border-t border-slate-100 pt-4">
                                <p class="text-xs text-slate-500">Status pendaftaran</p>
                                <div class="mt-1">
                                    <x-badge :color="$peserta->status_pendaftaran_color">
                                        {{ $peserta->status_pendaftaran_label }}
                                    </x-badge>
                                </div>
                                @if ($peserta->isMenunggu())
                                    <p class="mt-2 text-xs text-slate-500">
                                        Setujui atau tolak lewat halaman
                                        <a href="{{ route('pendaftaran.index') }}" class="text-emerald-700 hover:underline">Pendaftaran</a>.
                                    </p>
                                @endif
                            </div>
                        @endif
                    </x-card>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <x-button :href="route('peserta.index')" variant="secondary">Batal</x-button>
                <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Peserta' }}</x-button>
            </div>
        </form>
    @endif
</x-layouts::app>
