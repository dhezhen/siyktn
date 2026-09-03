<div class="grid gap-4 lg:grid-cols-3">

    {{-- Pohon menu --}}
    <div class="lg:col-span-2">
        <x-card padding="p-0" title="Susunan Menu"
                subtitle="Seret untuk mengubah urutan atau memindahkan ke dalam menu lain.">
            <x-slot:actions>
                @can('menu.create')
                    <x-button size="sm" icon="plus" wire:click="openCreate" busy-target="openCreate">Tambah Menu</x-button>
                @endcan
            </x-slot:actions>

            <div class="p-3"
                 x-data
                 x-init="
                    const build = () => {
                        const payload = [];
                        $el.querySelectorAll('[data-menu-id]').forEach(node => {
                            payload.push({
                                id: Number(node.dataset.menuId),
                                parent_id: Number(node.closest('[data-list]').dataset.parentId) || null,
                            });
                        });
                        return payload;
                    };

                    $el.querySelectorAll('[data-list]').forEach(list => {
                        window.Sortable.create(list, {
                            group: 'menu',
                            handle: '[data-handle]',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            ghostClass: 'opacity-40',
                            onEnd: () => $wire.reorder(build()),
                        });
                    });
                 ">

                <ul data-list data-parent-id="0" class="space-y-1">
                    @forelse ($menus as $menu)
                        @include('livewire.partials.menu-node', ['menu' => $menu, 'depth' => 0])
                    @empty
                        <li>
                            <x-empty-state title="Belum ada menu"
                                           message="Tambahkan menu pertama, atau jalankan php artisan db:seed --class=MenuSeeder." />
                        </li>
                    @endforelse
                </ul>
            </div>
        </x-card>

        <p class="mt-3 text-xs text-slate-500">
            Menu bertipe <strong>route</strong> otomatis disembunyikan dari sidebar bila nama route-nya
            belum terdaftar, dan hanya tampil bagi pengguna yang punya permission-nya.
        </p>
    </div>

    {{-- Form --}}
    <div>
        @if ($showForm)
            <x-card :title="$editingId ? 'Ubah Menu' : 'Tambah Menu'">
                <form wire:submit="save" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipe</label>
                        <select wire:model.live="type"
                                class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                            <option value="route">Route (halaman dalam sistem)</option>
                            <option value="url">URL (tautan luar)</option>
                            <option value="header">Header (judul kelompok)</option>
                            <option value="divider">Divider (garis pemisah)</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Judul <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" wire:model="title"
                               class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($type === 'route')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Nama Route <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="route" list="route-options" placeholder="mis. user.index"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                            <datalist id="route-options">
                                @foreach ($availableRoutes as $name)
                                    <option value="{{ $name }}"></option>
                                @endforeach
                            </datalist>
                            <p class="mt-1 text-xs text-slate-500">
                                Pakai nama route, bukan URL, agar tautan tetap benar walau alamatnya berubah.
                            </p>
                            @error('route') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    @if ($type === 'url')
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                URL <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="url" placeholder="https://…"
                                   class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                            @error('url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror

                            <label class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" wire:model="target" value="_blank"
                                       class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                Buka di tab baru
                            </label>
                        </div>
                    @endif

                    @if (in_array($type, ['route', 'url'], true))
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Ikon</label>
                            @php
                                $availableIcons = [
                                    'squares' => 'Dashboard',
                                    'users' => 'Users / Peserta',
                                    'shield' => 'Perisai / Keamanan',
                                    'list' => 'Daftar',
                                    'cog' => 'Pengaturan',
                                    'info' => 'Informasi',
                                    'book' => 'Buku / Halaqah',
                                    'academic' => 'Akademik / Guru',
                                    'document-text' => 'Dokumen / Laporan',
                                    'check-circle' => 'Selesai / Validasi',
                                    'key' => 'Akses / Kunci',
                                    'pencil' => 'Ubah / Edit',
                                    'plus' => 'Tambah',
                                    'user-circle' => 'Profil / Akun',
                                    'map-pin' => 'Lokasi / Wilayah',
                                    'light-bulb' => 'Bantuan / Ide',
                                    'identification' => 'Identitas / Kartu',
                                    'qr-code' => 'QR Code / Pindai',
                                    'bolt' => 'Petir / Cepat',
                                ];
                            @endphp
                            
                            <div x-data="{ open: false }" class="relative">
                                <!-- Trigger Button -->
                                <button type="button" @click="open = !open" @click.away="open = false"
                                        class="flex w-full items-center justify-between rounded-lg bg-white px-3 py-2 text-sm border ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-emerald-600">
                                    <span class="flex items-center gap-2 text-slate-700">
                                        @if($icon)
                                            <x-icon name="{{ $icon }}" class="w-5 h-5 text-slate-500" />
                                            <span>{{ $availableIcons[$icon] ?? $icon }}</span>
                                        @else
                                            <span>Tanpa ikon</span>
                                        @endif
                                    </span>
                                    <x-icon name="chevron-down" class="w-4 h-4 text-slate-400" />
                                </button>

                                <!-- Dropdown Menu -->
                                <div x-show="open" style="display: none;" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute z-10 mt-1 w-full max-h-60 overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    
                                    <div @click="$wire.set('icon', ''); open = false" 
                                         class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-emerald-50 hover:text-emerald-900">
                                         <span class="block truncate">Tanpa ikon</span>
                                    </div>

                                    @foreach($availableIcons as $val => $label)
                                        <div @click="$wire.set('icon', '{{ $val }}'); open = false" 
                                             class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-emerald-50 hover:text-emerald-900 flex items-center gap-3">
                                            <x-icon name="{{ $val }}" class="w-5 h-5 text-slate-500" />
                                            <span class="block truncate">{{ $label }}</span>
                                            @if($icon === $val)
                                                <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-emerald-600">
                                                    <x-icon name="check" class="w-4 h-4" />
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">Permission</label>
                            <select wire:model="permission"
                                    class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                                <option value="">Tampil untuk semua pengguna</option>
                                @foreach ($permissions as $name)
                                    <option value="{{ $name }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('permission') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Menu Induk</label>
                        <select wire:model="parent_id"
                                class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                            <option value="">— Menu utama —</option>
                            @foreach ($parentOptions as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Pilih headernya untuk memasukkan menu ini ke sebuah kelompok.
                        </p>
                        @error('parent_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="is_active"
                               class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Tampilkan menu ini
                    </label>

                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <x-button type="button" variant="secondary" size="sm" wire:click="closeForm">Batal</x-button>
                        <x-button type="submit" size="sm" icon="check" busy-target="save">Simpan</x-button>
                    </div>
                </form>
            </x-card>
        @else
            <x-card>
                <x-empty-state title="Tidak ada menu yang dibuka"
                               message="Pilih menu di sebelah kiri untuk mengubahnya, atau tambah menu baru." />
            </x-card>
        @endif
    </div>
</div>
