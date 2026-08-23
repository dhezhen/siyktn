@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
])

@php
    $variants = [
        'primary'   => 'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:outline-emerald-600',
        'secondary' => 'bg-white text-slate-700 ring-1 ring-slate-300 hover:bg-slate-50',
        'danger'    => 'bg-rose-600 text-white hover:bg-rose-700 focus-visible:outline-rose-600',
        'ghost'     => 'text-slate-600 hover:bg-slate-100',
    ];

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-lg font-medium transition',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon) <x-icon :name="$icon" class="size-4" /> @endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        @if ($icon) <x-icon :name="$icon" class="size-4" /> @endif
        {{ $slot }}
    </button>
@endif
