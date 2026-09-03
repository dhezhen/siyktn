<x-layouts::app :title="'Rekap Keuangan'">
    <x-page-header title="Rekap Keuangan" subtitle="Ringkasan pemasukan dari pendaftaran dan biaya program." />

    <!-- Kalkulasi/Statistik -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-blue-100 p-3 text-blue-600">
                <x-icon name="currency-dollar" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Tagihan</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-emerald-100 p-3 text-emerald-600">
                <x-icon name="check-circle" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Pemasukan</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-rose-100 p-3 text-rose-600">
                <x-icon name="clock" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Total Piutang (Pending)</p>
                <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</p>
            </div>
        </x-card>

        <x-card padding="p-5" class="flex items-center gap-4">
            <div class="rounded-full bg-indigo-100 p-3 text-indigo-600">
                <x-icon name="users" class="h-6 w-6" />
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">Status Pendaftar</p>
                <p class="text-lg font-semibold text-slate-900">{{ $jumlahLunas }} Lunas / {{ $jumlahPending }} Pending</p>
            </div>
        </x-card>
    </div>

    @if (session('success'))
        <x-alert variant="success" class="mb-4">
            {{ session('success') }}
        </x-alert>
    @endif

    <!-- Memanggil Komponen Livewire Tabel Keuangan -->
    <livewire:keuangan-table />

</x-layouts::app>
