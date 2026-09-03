@props(['name', 'title' => null, 'width' => 'max-w-lg'])

<div x-data="{ open: false }"
    x-on:open-modal.window="($event.detail === '{{ $name }}' || $event.detail?.[0] === '{{ $name }}') && (open = true)"
    x-on:close-modal.window="($event.detail === '{{ $name }}' || $event.detail?.[0] === '{{ $name }}') && (open = false)"
    x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
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
         class="relative w-full {{ $width }} overflow-hidden rounded-xl bg-white dark:bg-slate-900 shadow-xl ring-1 ring-black/5 dark:ring-white/10">

        @if ($title)
            <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 px-5 py-4">
                <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                <x-icon-button icon="x-mark" label="Tutup" @click="open = false" />
            </div>
        @endif

        <div class="px-5 py-4">{{ $slot }}</div>

        @isset($footer)
            <div class="flex justify-end gap-2 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 px-5 py-3">{{ $footer }}</div>
        @endisset
    </div>
</div>
