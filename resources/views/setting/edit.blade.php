<x-layouts::app :title="'Pengaturan Aplikasi'">
    <x-page-header title="Pengaturan Aplikasi"
                   subtitle="Identitas lembaga dan tampilan sistem. Berlaku untuk semua pengguna." />

    <form method="POST" action="{{ route('setting.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        @foreach ($groups as $group => $settings)
            <x-card :title="Str::title($group)">
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($settings as $setting)
                        <div @class(['sm:col-span-2' => in_array($setting->type, ['textarea', 'image'], true)])>
                            <label for="setting-{{ $setting->key }}" class="mb-1 block text-sm font-medium text-slate-700">
                                {{ $setting->label }}
                            </label>

                            @if ($setting->type === 'textarea')
                                <textarea name="values[{{ $setting->key }}]" id="setting-{{ $setting->key }}" rows="3"
                                          class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">{{ old('values.'.$setting->key, $setting->value) }}</textarea>

                            @elseif ($setting->type === 'image')
                                <div class="flex items-center gap-4">
                                    @if ($setting->value)
                                        <img src="{{ Storage::disk('public')->url($setting->value) }}" alt="{{ $setting->label }}"
                                             class="h-12 rounded border border-slate-200 bg-white object-contain p-1">
                                    @else
                                        <span class="grid size-12 shrink-0 place-items-center rounded border border-dashed border-slate-300 text-xs text-slate-400">
                                            kosong
                                        </span>
                                    @endif

                                    <input type="file" name="values[{{ $setting->key }}]" id="setting-{{ $setting->key }}"
                                           accept="image/*"
                                           class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                                </div>
                                <p class="mt-1 text-xs text-slate-500">PNG/JPG, maksimal 1 MB. Kosongkan bila tidak diganti.</p>

                            @else
                                <input type="text" name="values[{{ $setting->key }}]" id="setting-{{ $setting->key }}"
                                       value="{{ old('values.'.$setting->key, $setting->value) }}"
                                       class="block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-emerald-600">
                            @endif

                            @error('values.'.$setting->key)
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </x-card>
        @endforeach

        @can('setting.update')
            <div class="flex justify-end">
                <x-button type="submit">Simpan Pengaturan</x-button>
            </div>
        @endcan
    </form>
</x-layouts::app>
