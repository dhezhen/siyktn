<x-layouts::app :title="'Impor Pengguna'">
    <x-page-header title="Impor Pengguna" subtitle="Tambah banyak pengguna sekaligus dari berkas CSV." />

    <div class="grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Unggah Berkas">
            <form method="POST" action="{{ route('user.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <x-input name="file" type="file" label="Berkas CSV" required accept=".csv,text/csv"
                         hint="Maksimal 2 MB. Gunakan pemisah koma." />

                <div class="flex justify-end gap-2">
                    <x-button :href="route('user.index')" variant="secondary">Batal</x-button>
                    <x-button type="submit">Impor Sekarang</x-button>
                </div>
            </form>
        </x-card>

        <x-card title="Format Kolom">
            <p class="mb-3 text-sm text-slate-600">Baris pertama harus berisi nama kolom berikut:</p>

            <div class="overflow-x-auto rounded-lg bg-slate-900 p-3">
                <code class="whitespace-pre text-xs text-slate-100">nama,username,email,no_hp,role
Budi Santoso,budi,budi@contoh.id,081234567890,operator
Siti Aminah,siti,siti@contoh.id,,pengguna</code>
            </div>

            <ul class="mt-4 space-y-1.5 text-xs text-slate-600">
                <li>&bull; <span class="font-medium">no_hp</span> dan <span class="font-medium">role</span> boleh dikosongkan.</li>
                <li>&bull; <span class="font-medium">role</span> harus sama persis dengan nama role yang ada.</li>
                <li>&bull; Username dan email yang sudah terpakai akan dilewati, bukan menimpa data lama.</li>
                <li>&bull; Kata sandi dibuat acak dan wajib diganti saat login pertama.</li>
            </ul>
        </x-card>
    </div>
</x-layouts::app>
