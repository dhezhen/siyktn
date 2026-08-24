<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Peserta — {{ setting('app_name', config('app.name')) }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-100 antialiased">

<div class="mx-auto max-w-3xl px-4 py-10">

    <div class="mb-8 text-center">
        <div class="mx-auto mb-3 grid size-14 place-items-center rounded-xl bg-emerald-600 text-2xl font-bold text-white">
            {{ Str::substr(setting('app_name', config('app.name')), 0, 1) }}
        </div>
        <h1 class="text-2xl font-semibold text-slate-900">Pendaftaran Peserta</h1>
        <p class="mt-2 text-sm text-slate-600">
            {{ setting('organization', setting('app_name', config('app.name'))) }}
        </p>
    </div>

    <x-alert />

    @if ($angkatan->isEmpty())
        <x-card>
            <x-empty-state title="Pendaftaran sedang ditutup"
                           message="Saat ini belum ada angkatan yang menerima pendaftaran, atau kuotanya sudah penuh. Silakan periksa kembali beberapa waktu lagi." />
        </x-card>
    @else
        <form method="POST" action="{{ route('pendaftaran.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <x-card title="Angkatan yang Dituju">
                <x-select name="angkatan_id" label="Pilih Angkatan" required>
                    @foreach ($angkatan as $item)
                        <option value="{{ $item->id }}" @selected(old('angkatan_id') == $item->id)>
                            {{ $item->nama }} ({{ $item->tahun }})
                            @if ($item->sisa_kuota !== null)
                                — sisa {{ $item->sisa_kuota }} kursi
                            @endif
                        </option>
                    @endforeach
                </x-select>
            </x-card>

            <x-card title="Data Diri" subtitle="Isi sesuai yang tertera di KTP Anda.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="nama" label="Nama Lengkap" required :value="old('nama')" autofocus />

                    <x-input name="nik" label="NIK (16 digit)" required :value="old('nik')"
                             inputmode="numeric" maxlength="16" placeholder="3208xxxxxxxxxxxx" />

                    <x-select name="jenis_kelamin" label="Jenis Kelamin" required>
                        <option value="L" @selected(old('jenis_kelamin') === 'L')>Laki-laki</option>
                        <option value="P" @selected(old('jenis_kelamin') === 'P')>Perempuan</option>
                    </x-select>

                    <x-input name="tempat_lahir" label="Tempat Lahir" required :value="old('tempat_lahir')" />

                    <x-input name="tanggal_lahir" type="date" label="Tanggal Lahir" required
                             :value="old('tanggal_lahir')" />

                    <x-input name="no_hp" label="Nomor HP Aktif" required :value="old('no_hp')"
                             placeholder="08xxxxxxxxxx" />

                    <div class="sm:col-span-2">
                        <x-input name="email" type="email" label="Email Aktif" required :value="old('email')"
                                 placeholder="nama@contoh.id"
                                 hint="Bukti pendaftaran dan hasil verifikasi dikirim ke alamat ini." />
                    </div>

                    <div class="sm:col-span-2">
                        <label for="alamat" class="mb-1 block text-sm font-medium text-slate-700">
                            Alamat Lengkap <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="alamat" id="alamat" rows="3" required
                                  class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('alamat') }}</textarea>
                        @error('alamat')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </x-card>

            <x-card title="Data Wali" subtitle="Orang tua atau wali yang bisa kami hubungi.">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="nama_wali" label="Nama Wali" required :value="old('nama_wali')" />
                    <x-input name="no_hp_wali" label="Nomor HP Wali" required :value="old('no_hp_wali')"
                             placeholder="08xxxxxxxxxx" />
                </div>
            </x-card>

            <x-card title="Lampiran KTP" subtitle="Wajib, untuk verifikasi identitas.">
                <x-input name="ktp" type="file" label="Foto atau Scan KTP"
                         accept=".jpg,.jpeg,.png,.pdf"
                         hint="Format JPG, PNG, atau PDF. Maksimal 2 MB. Pastikan NIK dan nama terbaca jelas." />

                <p class="mt-3 rounded-lg bg-sky-50 p-3 text-xs leading-relaxed text-sky-900">
                    Berkas KTP Anda disimpan secara tertutup dan hanya dapat dibuka oleh petugas
                    yang berwenang melakukan verifikasi. Berkas ini tidak dapat diakses publik.
                </p>

                <p class="mt-2 rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-600">
                    <span class="font-medium text-slate-800">Pernah mendaftar sebelumnya?</span>
                    Isi NIK dan tanggal lahir persis seperti pendaftaran terdahulu. Data Anda akan
                    dipakai kembali, dan Anda tidak perlu mengunggah ulang KTP.
                </p>
            </x-card>

            <x-card>
                <label class="flex items-start gap-3 text-sm text-slate-700">
                    <input type="checkbox" name="persetujuan" value="1" required @checked(old('persetujuan'))
                           class="mt-0.5 size-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>
                        Saya menyatakan seluruh data yang saya isi di atas adalah benar, dan saya bersedia
                        data tersebut diverifikasi oleh pihak lembaga.
                    </span>
                </label>
                @error('persetujuan')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror

                <div class="mt-5 flex justify-end">
                    <x-button type="submit">Kirim Pendaftaran</x-button>
                </div>
            </x-card>
        </form>
    @endif

    <p class="mt-8 text-center text-xs text-slate-400">
        Sudah punya akun petugas?
        <a href="{{ route('login') }}" class="text-emerald-700 hover:underline">Masuk di sini</a>
    </p>
</div>

</body>
</html>
