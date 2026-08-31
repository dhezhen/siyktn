@php($editing = $muhaffizh->exists)

<x-layouts::app :title="$editing ? 'Ubah Muhaffizh' : 'Tambah Muhaffizh'">
    <x-page-header :title="$editing ? 'Ubah Muhaffizh: '.$muhaffizh->nama : 'Tambah Muhaffizh'"
                   subtitle="Data pembimbing hafalan. Halaqah yang diampu diatur dari modul Halaqah." />

    <form method="POST" action="{{ $editing ? route('muhaffizh.update', $muhaffizh) : route('muhaffizh.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <x-card title="Data Diri">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="nama" label="Nama Lengkap" required :value="old('nama', $muhaffizh->nama)" />

                        <x-input name="kode" label="Kode" required :value="old('kode', $muhaffizh->kode)"
                                 placeholder="mis. MHF-001"
                                 hint="Dipakai sebagai nomor pengenal muhaffizh." />

                        <x-select name="jenis_kelamin" label="Jenis Kelamin" required
                                  hint="Menentukan halaqah mana yang boleh diampu.">
                            <option value="L" @selected(old('jenis_kelamin', $muhaffizh->jenis_kelamin) === 'L')>Laki-laki</option>
                            <option value="P" @selected(old('jenis_kelamin', $muhaffizh->jenis_kelamin) === 'P')>Perempuan</option>
                        </x-select>

                        <x-input name="tanggal_bergabung" type="date" label="Tanggal Bergabung"
                                 :value="old('tanggal_bergabung', $muhaffizh->tanggal_bergabung?->format('Y-m-d'))" />

                        <x-input name="no_hp" label="Nomor HP" :value="old('no_hp', $muhaffizh->no_hp)"
                                 placeholder="08xxxxxxxxxx" />

                        <x-input name="email" type="email" label="Email" :value="old('email', $muhaffizh->email)"
                                 placeholder="nama@contoh.id" />
                    </div>
                </x-card>

                <x-card title="Keilmuan">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input name="pendidikan" label="Pendidikan Terakhir"
                                 :value="old('pendidikan', $muhaffizh->pendidikan)"
                                 placeholder="mis. S1 Ilmu Al-Qur'an" />

                        <x-input name="sanad_riwayat" label="Sanad / Riwayat"
                                 :value="old('sanad_riwayat', $muhaffizh->sanad_riwayat)"
                                 placeholder="mis. Hafsh 'an 'Ashim" />
                    </div>

                    <div class="mt-4">
                        <label for="keterangan" class="mb-1 block text-sm font-medium text-slate-700">Keterangan</label>
                        <textarea name="keterangan" id="keterangan" rows="3"
                                  class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('keterangan', $muhaffizh->keterangan) }}</textarea>
                        @error('keterangan')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </x-card>
            </div>

            <div class="space-y-4">
                <x-card title="Foto">
                    <div class="flex items-center gap-4">
                        @if ($muhaffizh->foto_url)
                            <img src="{{ $muhaffizh->foto_url }}" alt="Foto muhaffizh"
                                 class="size-16 rounded-full object-cover ring-1 ring-slate-200">
                        @else
                            <span class="grid size-16 place-items-center rounded-full bg-slate-200 text-lg font-semibold text-slate-500">
                                {{ $muhaffizh->initials }}
                            </span>
                        @endif
                        <div class="flex-1">
                            <x-input name="foto" type="file" accept="image/*" hint="JPG/PNG, maksimal 2 MB." />
                        </div>
                    </div>
                </x-card>

                <x-card title="Status">
                    <x-select name="status" label="Status Kepegawaian" required
                              hint="Muhaffizh nonaktif tidak lagi ditawarkan saat membuat halaqah baru.">
                        @foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $muhaffizh->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-card>

                <x-card title="Akun Login"
                        subtitle="Opsional — hubungkan bila muhaffizh perlu masuk sendiri ke sistem.">
                    <x-select name="user_id" label="Akun Pengguna"
                              hint="Hanya akun yang belum memikul peran lain yang ditawarkan.">
                        <option value="">— Belum punya akun —</option>
                        @foreach ($akunTersedia as $akun)
                            <option value="{{ $akun->id }}" @selected(old('user_id', $muhaffizh->user_id) == $akun->id)>
                                {{ $akun->name }} ({{ $akun->username }})
                            </option>
                        @endforeach
                    </x-select>

                    <p class="mt-3 rounded-lg bg-sky-50 p-3 text-xs leading-relaxed text-sky-900">
                        Role <strong>muhaffizh</strong> menempel otomatis begitu akun ditautkan, dan dicabut
                        lagi bila tautannya dilepas.
                        @if ($editing && ! $muhaffizh->user_id)
                            Belum punya akun sama sekali? Pakai tombol <strong>Buatkan Akun</strong>
                            di halaman detail.
                        @endif
                    </p>
                </x-card>
            </div>
        </div>

        <div class="mt-4 flex justify-end gap-2">
            <x-button :href="route('muhaffizh.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Muhaffizh' }}</x-button>
        </div>
    </form>
</x-layouts::app>
