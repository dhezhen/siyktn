<x-layouts::app :title="'Log Aktivitas'">
    <x-page-header title="Log Aktivitas"
                   subtitle="Catatan setiap perubahan data: siapa, apa, dan kapan." />

    <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari keterangan…"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <select name="log" class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua jenis data</option>
            @foreach ($logNames as $name)
                <option value="{{ $name }}" @selected(request('log') === $name)>{{ $name }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <select name="event" class="flex-1 rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                <option value="">Semua aksi</option>
                @foreach (['created' => 'Tambah', 'updated' => 'Ubah', 'deleted' => 'Hapus', 'restored' => 'Pulihkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </div>
    </form>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Waktu</th>
                        <th class="px-5 py-3 font-medium">Pelaku</th>
                        <th class="px-5 py-3 font-medium">Keterangan</th>
                        <th class="px-5 py-3 font-medium">Perubahan</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($activities as $activity)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 text-xs text-slate-500">
                                {{ $activity->created_at->translatedFormat('d M Y') }}
                                <span class="block">{{ $activity->created_at->format('H:i:s') }}</span>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-800">{{ $activity->causer?->name ?? 'Sistem' }}</p>
                                @if ($activity->causer)
                                    <p class="text-xs text-slate-400">&#64;{{ $activity->causer->username }}</p>
                                @endif
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-800">{{ $activity->description }}</p>
                                <x-badge color="slate">{{ $activity->log_name }}</x-badge>
                            </td>

                            <td class="px-5 py-3">
                                @php($changes = $activity->properties->get('attributes', []))
                                @php($previous = $activity->properties->get('old', []))

                                @if ($changes === [] || $changes === null)
                                    <span class="text-xs text-slate-400">—</span>
                                @else
                                    <ul class="space-y-0.5 text-xs">
                                        @foreach ($changes as $field => $value)
                                            <li class="text-slate-600">
                                                <span class="font-medium text-slate-700">{{ $field }}:</span>
                                                @if (array_key_exists($field, (array) $previous))
                                                    <span class="text-rose-600 line-through">{{ Str::limit((string) $previous[$field], 30) ?: '—' }}</span>
                                                    <span class="text-slate-400">&rarr;</span>
                                                @endif
                                                <span class="text-emerald-700">{{ Str::limit((string) $value, 30) ?: '—' }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-empty-state title="Belum ada aktivitas tercatat"
                                               message="Setiap penambahan, perubahan, dan penghapusan data akan muncul di sini." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">{{ $activities->links() }}</div>
        @endif
    </x-card>
</x-layouts::app>
