@props([
    'action',
    'label' => 'Hapus',
    'title' => 'Hapus data ini?',
    'message' => 'Data yang dihapus tidak dapat dikembalikan. Lanjutkan?',
    'iconOnly' => false,
    'icon' => 'trash',
])

<div x-data="{ open: false }" class="inline-flex">
    @if ($iconOnly)
        <x-icon-button :icon="$icon" :label="$label" variant="danger" @click="open = true" />
    @else
        <x-button variant="danger" size="sm" :icon="$icon" @click="open = true">{{ $label }}</x-button>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak @keydown.escape.window="open = false"
             class="fixed inset-0 z-[60] flex items-center justify-center p-4">

            <div x-show="open" @click="open = false"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-xl ring-1 ring-black/5">

                <div class="mb-5 flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-rose-100 text-rose-600">
                        <x-icon name="warning" class="size-5" />
                    </span>
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $message }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button variant="secondary" size="sm" @click="open = false">Batal</x-button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm" icon="trash">Ya, Hapus</x-button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
