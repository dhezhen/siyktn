@props(['title' => null, 'subtitle' => null, 'padding' => 'p-5'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title || $subtitle || isset($actions))
        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                @if ($title)<h2 class="font-semibold text-slate-900">{{ $title }}</h2>@endif
                @if ($subtitle)<p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif

    <div class="{{ $padding }}">{{ $slot }}</div>
</div>
