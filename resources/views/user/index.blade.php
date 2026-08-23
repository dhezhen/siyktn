<x-layouts::app :title="'Pengguna'">
    <x-page-header title="Pengguna" subtitle="Kelola akun, status, dan role setiap pengguna sistem.">
        <x-slot:actions>
            @can('user.export')
                <x-button :href="route('user.export')" variant="secondary">Ekspor CSV</x-button>
            @endcan
            @can('user.import')
                <x-button :href="route('user.import.form')" variant="secondary">Impor CSV</x-button>
            @endcan
            @can('user.create')
                <x-button :href="route('user.create')" icon="users">Tambah Pengguna</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:user-table />
</x-layouts::app>
