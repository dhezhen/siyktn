@props([
    'icon',
    'label',                 // wajib: jadi tooltip sekaligus label untuk pembaca layar
    'variant' => 'default',
    'href' => null,
])

@php
    $variants = [
        'default' => 'text-slate-400 hover:bg-slate-100 hover:text-slate-700',
        'primary' => 'text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700',
        'warning' => 'text-amber-600 hover:bg-amber-50 hover:text-amber-700',
        'danger' => 'text-rose-500 hover:bg-rose-50 hover:text-rose-700',
    ];

    $classes = implode(' ', [
        'inline-grid size-8 place-items-center rounded-lg',
        'transition duration-150 ease-out active:scale-90',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600',
        'disabled:pointer-events-none disabled:opacity-40',
        $variants[$variant] ?? $variants['default'],
    ]);
@endphp

<div x-data="{ tip: false }" class="inline-flex">
    @if ($href)
        <a href="{{ $href }}"
           x-ref="anchor"
           @mouseenter="tip = true" @mouseleave="tip = false"
           @focus="tip = true" @blur="tip = false"
           aria-label="{{ $label }}"
           {{ $attributes->merge(['class' => $classes]) }}>
            <x-icon :name="$icon" class="size-[1.15rem]" />
        </a>
    @else
        <button x-ref="anchor"
                @mouseenter="tip = true" @mouseleave="tip = false"
                @focus="tip = true" @blur="tip = false"
                aria-label="{{ $label }}"
                {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
            <x-icon :name="$icon" class="size-[1.15rem]" />
        </button>
    @endif

    {{--
        Tooltip dipindahkan ke <body> dan diposisikan dengan x-anchor, supaya
        tidak terpotong oleh tabel yang punya overflow-x-auto.
    --}}
    <template x-teleport="body">
        <div x-show="tip" x-cloak
             x-anchor.top.offset.8="$refs.anchor"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             role="tooltip"
             class="pointer-events-none z-[70] whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs font-medium text-white shadow-lg">
            {{ $label }}
        </div>
    </template>
</div>
