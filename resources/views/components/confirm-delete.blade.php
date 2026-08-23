@props([
    'action',
    'label' => 'Hapus',
    'title' => 'Hapus data ini?',
    'message' => 'Data yang dihapus tidak dapat dikembalikan. Lanjutkan?',
    'iconOnly' => false,
])

<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true"
            class="{{ $iconOnly
                ? 'rounded-lg p-1.5 text-rose-600 transition hover:bg-rose-50'
                : 'inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-2.5 py-1.5 text-xs font-medium text-white transition hover:bg-rose-700' }}"
            title="{{ $label }}">
        <x-icon name="trash" class="size-4" />
        @unless ($iconOnly) {{ $label }} @endunless
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak @keydown.escape.window="open = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-slate-900/50"></div>

            <div x-show="open" x-transition class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-rose-100 text-rose-600">
                        <x-icon name="warning" class="size-5" />
                    </span>
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $message }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <x-button variant="secondary" size="sm" @click="open = false">Batal</x-button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" size="sm">Ya, Hapus</x-button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
