@php
    $styles = [
        'success' => ['icon' => 'check-circle', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        'error'   => ['icon' => 'x-circle',     'class' => 'border-rose-200 bg-rose-50 text-rose-800'],
        'warning' => ['icon' => 'warning',      'class' => 'border-amber-200 bg-amber-50 text-amber-800'],
        'info'    => ['icon' => 'info',         'class' => 'border-sky-200 bg-sky-50 text-sky-800'],
    ];
@endphp

@foreach ($styles as $key => $style)
    @if (session()->has($key))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="mb-4 flex items-start gap-3 rounded-lg border p-4 text-sm {{ $style['class'] }}">
            <x-icon :name="$style['icon']" class="mt-0.5 size-5 shrink-0" />
            <div class="flex-1">{{ session($key) }}</div>
            <button type="button" @click="show = false" class="opacity-50 transition hover:opacity-100">
                <x-icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! $errors->hasBag('__nothing'))
    <div class="mb-4 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
        <x-icon name="x-circle" class="mt-0.5 size-5 shrink-0" />
        <div class="flex-1">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
