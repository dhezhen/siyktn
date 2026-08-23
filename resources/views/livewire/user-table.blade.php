<div>
    {{-- Filter --}}
    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <input type="search" wire:model.live.debounce.400ms="search"
                   placeholder="Cari nama, username, email, atau nomor HP…"
                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
        </div>

        <select wire:model.live="role"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua role</option>
            @foreach ($roles as $name)
                <option value="{{ $name }}">{{ $name }}</option>
            @endforeach
        </select>

        <select wire:model.live="status"
                class="rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
            <option value="">Semua status</option>
            <option value="aktif">Aktif</option>
            <option value="nonaktif">Nonaktif</option>
        </select>
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" wire:model.live="trashed"
                   class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            Tampilkan yang sudah dihapus
        </label>

        <div class="flex items-center gap-3">
            <span wire:loading class="text-xs text-slate-400">Memuat…</span>
            <button type="button" wire:click="resetFilters" class="text-xs text-slate-500 hover:underline">
                Reset filter
            </button>
        </div>
    </div>

    {{-- Tabel --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Pengguna</th>
                        <th class="px-5 py-3 font-medium">Kontak</th>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 font-medium">Login Terakhir</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt=""
                                             class="size-9 rounded-full object-cover ring-1 ring-slate-200">
                                    @else
                                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">
                                            {{ $user->initials }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-slate-900">{{ $user->name }}</p>
                                        <p class="truncate text-xs text-slate-500">&#64;{{ $user->username }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                <p class="text-slate-700">{{ $user->email }}</p>
                                <p class="text-xs text-slate-500">{{ $user->phone ?: '—' }}</p>
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        <x-badge color="emerald">{{ $role->name }}</x-badge>
                                    @empty
                                        <span class="text-xs text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>

                            <td class="px-5 py-3">
                                @if ($user->trashed())
                                    <x-badge color="slate">Dihapus</x-badge>
                                @elseif ($user->is_active)
                                    <x-badge color="emerald">Aktif</x-badge>
                                @else
                                    <x-badge color="rose">Nonaktif</x-badge>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-xs text-slate-500">
                                {{ $user->last_login_at?->translatedFormat('d M Y H:i') ?? 'Belum pernah' }}
                            </td>

                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    @if ($user->trashed())
                                        @can('user.delete')
                                            <form method="POST" action="{{ route('user.restore', $user->id) }}">
                                                @csrf
                                                <x-button type="submit" variant="secondary" size="sm">Pulihkan</x-button>
                                            </form>
                                        @endcan
                                    @else
                                        @can('user.update')
                                            <button type="button" wire:click="toggleActive({{ $user->id }})"
                                                    class="rounded-lg px-2 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100">
                                                {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            <x-button :href="route('user.edit', $user)" variant="secondary" size="sm">Ubah</x-button>
                                        @endcan

                                        @can('user.delete')
                                            @if ($user->id !== auth()->id())
                                                <x-confirm-delete :action="route('user.destroy', $user)" icon-only
                                                    :title="'Hapus '.$user->name.'?'"
                                                    message="Pengguna dipindahkan ke daftar terhapus dan bisa dipulihkan kembali." />
                                            @endif
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty-state title="Tidak ada pengguna yang cocok"
                                               message="Coba ubah kata kunci pencarian atau reset filter." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-3">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    {{-- Notifikasi dari aksi Livewire --}}
    <div x-data="{ show: false, type: 'success', message: '' }"
         x-on:notify.window="type = $event.detail.type; message = $event.detail.message; show = true; setTimeout(() => show = false, 4000)"
         x-show="show" x-cloak x-transition
         class="fixed bottom-5 right-5 z-50 max-w-sm rounded-lg px-4 py-3 text-sm text-white shadow-lg"
         x-bind:class="type === 'success' ? 'bg-emerald-600' : 'bg-rose-600'">
        <span x-text="message"></span>
    </div>
</div>
