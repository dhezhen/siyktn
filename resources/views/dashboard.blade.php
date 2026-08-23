<x-layouts::app :title="'Dashboard'">
    <x-page-header title="Dashboard" subtitle="Ringkasan singkat kondisi sistem." />

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats ?? [] as $stat)
            <x-card padding="p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $stat['value'] }}</p>
                    </div>
                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-600">
                        <x-icon :name="$stat['icon']" class="size-5" />
                    </span>
                </div>
            </x-card>
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <x-card class="lg:col-span-2" title="Aktivitas Terakhir" subtitle="10 perubahan data paling baru.">
            @forelse ($activities ?? [] as $activity)
                <div class="flex items-start gap-3 border-b border-slate-100 py-2.5 last:border-0">
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-slate-700">{{ $activity->description }}</p>
                        <p class="text-xs text-slate-400">
                            {{ $activity->causer?->name ?? 'Sistem' }} &middot; {{ $activity->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <x-empty-state title="Belum ada aktivitas"
                               message="Perubahan data akan tercatat di sini secara otomatis." />
            @endforelse
        </x-card>

        <x-card title="Selamat Datang">
            <p class="text-sm leading-relaxed text-slate-600">
                Halo <span class="font-medium text-slate-900">{{ auth()->user()->name }}</span>,
                Anda masuk sebagai
                <span class="font-medium text-slate-900">{{ auth()->user()->roles->pluck('name')->join(', ') ?: 'pengguna tanpa role' }}</span>.
            </p>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">
                Gunakan menu di samping untuk mengelola data. Menu yang tampil menyesuaikan
                hak akses yang Anda miliki.
            </p>
        </x-card>
    </div>
</x-layouts::app>
