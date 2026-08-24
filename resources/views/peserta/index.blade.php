<x-layouts::app :title="'Peserta'">
    <x-page-header title="Peserta" subtitle="Data peserta program dari seluruh angkatan.">
        <x-slot:actions>
            @can('peserta.export')
                <x-button :href="route('peserta.export')" variant="secondary" icon="download">Ekspor CSV</x-button>
            @endcan
            @can('peserta.create')
                <x-button :href="route('peserta.create')" icon="plus">Tambah Peserta</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:peserta-table />
</x-layouts::app>
