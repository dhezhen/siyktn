<x-layouts::app :title="'Dashboard Pimpinan'">
    <x-page-header title="Dashboard Pimpinan" subtitle="Ringkasan data operasional, kepesertaan, dan keuangan instansi.">
        @if (isset($angkatans) && $angkatans->isNotEmpty())
            <x-slot:actions>
                <form method="GET" action="">
                    <select name="angkatan_id" class="py-1.5 pl-3 pr-8 text-sm border-slate-200 dark:border-slate-700 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 dark:bg-slate-800 dark:text-slate-200" onchange="this.form.submit()">
                        <option value="">Semua Angkatan</option>
                        @foreach ($angkatans as $angkatan)
                            <option value="{{ $angkatan->id }}" @selected(($selectedAngkatan ?? '') == $angkatan->id)>{{ $angkatan->nama }}</option>
                        @endforeach
                    </select>
                </form>
            </x-slot:actions>
        @endif
    </x-page-header>

    @include('pimpinan.partials.widgets')

</x-layouts::app>
