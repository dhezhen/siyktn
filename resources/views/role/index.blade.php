<x-layouts::app :title="'Role & Hak Akses'">
    <x-page-header title="Role & Hak Akses" subtitle="Atur kelompok pengguna dan apa saja yang boleh mereka lakukan.">
        <x-slot:actions>
            @can('role.create')
                <x-button :href="route('role.create')" icon="shield">Tambah Role</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Role</th>
                        <th class="px-5 py-3 font-medium">Hak Akses</th>
                        <th class="px-5 py-3 font-medium">Pengguna</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($roles as $role)
                        @php($isSuper = $role->name === config('permission.super_admin_role'))
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-slate-900">{{ $role->name }}</span>
                                    @if ($isSuper)<x-badge color="amber">bawaan</x-badge>@endif
                                </div>
                                @if ($descriptions[$role->name] ?? null)
                                    <p class="mt-0.5 text-xs text-slate-500">{{ $descriptions[$role->name] }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($isSuper)
                                    <x-badge color="emerald">Akses penuh</x-badge>
                                @else
                                    <span class="text-slate-700">{{ $role->permissions_count }} hak akses</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $role->users_count }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    @can('role.update')
                                        @unless ($isSuper)
                                            <x-button :href="route('role.edit', $role)" variant="secondary" size="sm">Atur</x-button>
                                        @endunless
                                    @endcan
                                    @can('role.delete')
                                        @unless ($isSuper)
                                            <x-confirm-delete :action="route('role.destroy', $role)" icon-only
                                                :title="'Hapus role '.$role->name.'?'"
                                                message="Pengguna yang memakai role ini akan kehilangan hak aksesnya." />
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</x-layouts::app>
