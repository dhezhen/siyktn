<x-layouts::app :title="'Pendaftaran Peserta'">
    <x-page-header title="Pendaftaran Peserta"
                   subtitle="Tinjau berkas pendaftaran yang masuk, lalu setujui atau tolak.">
        <x-slot:actions>
            <x-button :href="route('pendaftaran.presensi')" variant="secondary">
                📷 Verifikasi Kehadiran (Scan QR)
            </x-button>
            <x-button :href="route('pendaftaran.create')" variant="secondary" icon="eye" target="_blank">
                Buka Formulir Publik
            </x-button>
            @can('peserta.create')
                <x-button :href="route('peserta.create')" icon="plus">Input Manual</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:pendaftaran-table />
</x-layouts::app>
