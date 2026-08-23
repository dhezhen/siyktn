@props(['name', 'title' => null, 'width' => 'max-w-lg'])

<div x-data="{ open: false }"
     x-on:open-modal.window="$event.detail === '{{ $name }}' && (open = true)"
     x-on:close-modal.window="$event.detail === '{{ $name }}' && (open = false)"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">

    <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-slate-900/50"></div>

    <div x-show="open" x-transition
         class="relative w-full {{ $width }} overflow-hidden rounded-xl bg-white shadow-xl">
        @if ($title)
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
                <button type="button" @click="open = false" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="x-mark" class="size-5" />
                </button>
            </div>
        @endif

        <div class="px-5 py-4">{{ $slot }}</div>

        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">{{ $footer }}</div>
        @endisset
    </div>
</div>
