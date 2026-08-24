<x-layouts::guest :title="'Pendaftaran Terkirim'">
    <div class="text-center">
        <span class="mx-auto mb-4 grid size-14 place-items-center rounded-full bg-emerald-100 text-emerald-600">
            <x-icon name="check-circle" class="size-8" />
        </span>

        <h2 class="text-lg font-semibold text-slate-900">
            {{ $ulang ? 'Pendaftaran Ulang Anda Terkirim' : 'Pendaftaran Anda Terkirim' }}
        </h2>

        <p class="mt-2 text-sm leading-relaxed text-slate-600">
            @if ($ulang)
                Selamat datang kembali. Data Anda yang tersimpan kami pakai ulang,
                dan berkas Anda akan diverifikasi biasanya dalam 1&ndash;3 hari kerja.
            @else
                Terima kasih. Berkas Anda akan diverifikasi oleh petugas kami,
                biasanya dalam 1&ndash;3 hari kerja.
            @endif
        </p>

        <div class="my-5 rounded-lg border border-dashed border-emerald-300 bg-emerald-50 px-4 py-4">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Kode Pendaftaran</p>
            <p class="mt-1 font-mono text-xl font-semibold text-emerald-900">{{ $kode }}</p>
        </div>

        <p class="text-sm leading-relaxed text-slate-600">
            Simpan kode di atas. Bukti pendaftaran sudah kami kirim ke
            <span class="font-medium text-slate-900">{{ $email }}</span>,
            dan hasil verifikasi akan dikirim ke alamat yang sama.
        </p>

        <p class="mt-3 text-xs text-slate-500">
            Tidak menerima email? Periksa folder spam terlebih dahulu, lalu hubungi kami
            dengan menyebutkan kode pendaftaran Anda.
        </p>

        <div class="mt-6">
            <x-button :href="route('pendaftaran.create')" variant="secondary" class="w-full">
                Daftarkan Orang Lain
            </x-button>
        </div>
    </div>
</x-layouts::guest>
