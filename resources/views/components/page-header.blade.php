@props(['title', 'subtitle' => null])

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
</div>
