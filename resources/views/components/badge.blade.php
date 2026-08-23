@props(['color' => 'slate'])

@php
    $colors = [
        'slate'   => 'bg-slate-100 text-slate-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'rose'    => 'bg-rose-100 text-rose-700',
        'amber'   => 'bg-amber-100 text-amber-700',
        'sky'     => 'bg-sky-100 text-sky-700',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.($colors[$color] ?? $colors['slate'])]) }}>
    {{ $slot }}
</span>
