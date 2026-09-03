@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconAfter' => null,
    'busyTarget' => null,   // nama method Livewire: tombol meredup & ikon berputar saat diproses
])

@php
    $variants = [
        'primary'   => 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-md shadow-emerald-600/20 hover:from-emerald-500 hover:to-teal-500 hover:-translate-y-0.5 active:translate-y-0 focus-visible:outline-emerald-600 transition-all dark:shadow-none',
        'secondary' => 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-sm ring-1 ring-slate-300 dark:ring-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:ring-slate-400 dark:hover:ring-slate-600 focus-visible:outline-slate-500',
        'warning'   => 'bg-amber-500 text-white font-bold shadow-sm hover:bg-amber-600 focus-visible:outline-amber-500 transition-all',
        'danger'    => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus-visible:outline-rose-600',
        'ghost'     => 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-200 focus-visible:outline-slate-500',
    ];

    $sizes = [
        'sm' => 'px-2.5 py-1.5 text-xs gap-1.5',
        'md' => 'px-3.5 py-2 text-sm gap-2',
    ];

    $classes = implode(' ', [
        'inline-flex items-center justify-center rounded-lg font-medium',
        'transition duration-150 ease-out active:scale-[0.97]',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2',
        'disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
    ]);

    $iconSize = $size === 'sm' ? 'size-3.5' : 'size-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" class="{{ $iconSize }} shrink-0" />@endif
        {{ $slot }}
        @if ($iconAfter)<x-icon :name="$iconAfter" class="{{ $iconSize }} shrink-0" />@endif
    </a>
@else
    <button
        @if ($busyTarget)
            wire:loading.attr="disabled" wire:target="{{ $busyTarget }}"
        @endif
        {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>

        @if ($busyTarget)
            <svg wire:loading wire:target="{{ $busyTarget }}"
                 class="{{ $iconSize }} shrink-0 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                <path class="opacity-80" fill="currentColor"
                      d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z" />
            </svg>
            @if ($icon)
                <span wire:loading.remove wire:target="{{ $busyTarget }}" class="contents">
                    <x-icon :name="$icon" class="{{ $iconSize }} shrink-0" />
                </span>
            @endif
        @elseif ($icon)
            <x-icon :name="$icon" class="{{ $iconSize }} shrink-0" />
        @endif

        {{ $slot }}

        @if ($iconAfter)<x-icon :name="$iconAfter" class="{{ $iconSize }} shrink-0" />@endif
    </button>
@endif
