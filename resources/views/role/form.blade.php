@php($editing = $role->exists)

<x-layouts::app :title="$editing ? 'Ubah Role' : 'Tambah Role'">
    <x-page-header :title="$editing ? 'Ubah Role: '.$role->name : 'Tambah Role'"
                   subtitle="Centang hak akses yang boleh dilakukan role ini." />

    <form method="POST" action="{{ $editing ? route('role.update', $role) : route('role.store') }}"
          x-data="{ toggleAll(scope, checked) { scope.querySelectorAll('input[type=checkbox]').forEach(c => c.checked = checked) } }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-card title="Identitas Role" class="mb-4">
            <div class="max-w-md">
                <x-input name="name" label="Nama Role" required :value="old('name', $role->name)"
                         placeholder="mis. kepala-bidang"
                         hint="Huruf kecil, tanpa spasi. Dipakai di kode, jadi hindari mengubahnya sesudah dipakai." />
            </div>
        </x-card>

        @foreach ($matrix as $group => $modules)
            <x-card :title="$group" class="mb-4" padding="p-0">
                <x-slot:actions>
                    <button type="button" class="text-xs text-emerald-700 hover:underline"
                            @click="toggleAll($el.closest('[data-group]'), true)">Pilih semua</button>
                    <button type="button" class="text-xs text-slate-500 hover:underline"
                            @click="toggleAll($el.closest('[data-group]'), false)">Kosongkan</button>
                </x-slot:actions>

                <div data-group class="divide-y divide-slate-100">
                    @foreach ($modules as $moduleLabel => $permissions)
                        <div class="flex flex-col gap-2 px-5 py-3.5 sm:flex-row sm:items-center">
                            <p class="w-full shrink-0 text-sm font-medium text-slate-700 sm:w-52">{{ $moduleLabel }}</p>

                            <div class="flex flex-wrap gap-x-5 gap-y-2">
                                @foreach ($permissions as $permission => $actionLabel)
                                    <label class="flex items-center gap-2 text-sm text-slate-600">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission }}"
                                               @checked(in_array($permission, old('permissions', $assigned), true))
                                               class="size-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                        {{ $actionLabel }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        <div class="flex justify-end gap-2">
            <x-button :href="route('role.index')" variant="secondary">Batal</x-button>
            <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Simpan Role' }}</x-button>
        </div>
    </form>
</x-layouts::app>
