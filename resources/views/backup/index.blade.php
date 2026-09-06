<x-layouts::app :title="'Backup & Restore'">
    <x-page-header title="Backup & Restore"
                   subtitle="Manajemen pencadangan data sistem dan file unggahan." />

    <x-card>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4 dark:border-slate-700">
            <div>
                <h3 class="text-lg font-medium text-slate-900 dark:text-white">Daftar Backup</h3>
                <p class="text-sm text-slate-500">File hasil backup berisi keseluruhan database dan folder storage (foto/dokumen).</p>
            </div>
            <form action="{{ route('backup.create') }}" method="POST">
                @csrf
                <x-button type="submit" icon="cloud-arrow-up">Buat Backup Baru</x-button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-400">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    <tr>
                        <th class="px-5 py-3 font-medium">Nama File</th>
                        <th class="px-5 py-3 font-medium">Ukuran</th>
                        <th class="px-5 py-3 font-medium">Tanggal Pembuatan</th>
                        <th class="px-5 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                    @forelse ($backups as $backup)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-5 py-3 font-medium text-slate-900 dark:text-slate-200">
                                {{ $backup['file_name'] }}
                            </td>
                            <td class="px-5 py-3">{{ $backup['file_size'] }}</td>
                            <td class="px-5 py-3">{{ $backup['last_modified']->translatedFormat('d F Y, H:i:s') }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <form action="{{ route('backup.destroy', $backup['file_name']) }}" method="POST"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus backup ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-button type="submit" variant="danger" size="sm" icon="trash">Hapus</x-button>
                                    </form>

                                    <x-button href="{{ route('backup.download', $backup['file_name']) }}" size="sm" icon="arrow-down-tray">Unduh</x-button>

                                    <!-- Instruksi Restore Sesuai Opsi A -->
                                    <x-button type="button" variant="secondary" size="sm" icon="arrow-path" 
                                              onclick="alert('Untuk merestore backup ini:\n1. Unduh file zip ini.\n2. Ekstrak isinya.\n3. Jalankan file SQL (.sql) di dalam folder db-dumps menggunakan command: mysql -u {username} {database} < file.sql\n4. Salin isi folder public ke dalam direktori storage/app/public server Anda.\n\nLangkah ini dilakukan manual demi keamanan integritas database Anda yang sedang berjalan.')">
                                        Restore
                                    </x-button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-slate-500">
                                <x-icon name="folder-open" class="mx-auto mb-2 size-8 text-slate-300" />
                                <p>Belum ada file backup yang tersedia.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::app>
