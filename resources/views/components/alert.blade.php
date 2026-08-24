@php
    $styles = [
        'success' => ['icon' => 'check-circle', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'iconClass' => 'text-emerald-600'],
        'error'   => ['icon' => 'x-circle',     'class' => 'border-rose-200 bg-rose-50 text-rose-800',        'iconClass' => 'text-rose-600'],
        'warning' => ['icon' => 'warning',      'class' => 'border-amber-200 bg-amber-50 text-amber-800',     'iconClass' => 'text-amber-600'],
        'info'    => ['icon' => 'info',         'class' => 'border-sky-200 bg-sky-50 text-sky-800',           'iconClass' => 'text-sky-600'],
    ];
@endphp

@foreach ($styles as $key => $style)
    @if (session()->has($key))
        {{-- Pesan sukses menutup sendiri setelah 6 detik; peringatan dibiarkan
             sampai dibaca dan ditutup manual. --}}
        <div x-data="{ show: false }"
             x-init="$nextTick(() => show = true); @js($key === 'success') && setTimeout(() => show = false, 6000)"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 max-h-40"
             x-transition:leave-end="opacity-0 max-h-0"
             class="mb-4 flex items-start gap-3 overflow-hidden rounded-lg border p-4 text-sm {{ $style['class'] }}">
            <x-icon :name="$style['icon']" class="mt-0.5 size-5 shrink-0 {{ $style['iconClass'] }}" />
            <div class="flex-1 leading-relaxed">{{ session($key) }}</div>
            <button type="button" @click="show = false"
                    class="shrink-0 rounded p-0.5 opacity-50 transition hover:opacity-100"
                    aria-label="Tutup pesan">
                <x-icon name="x-mark" class="size-4" />
            </button>
        </div>
    @endif
@endforeach

@if ($errors->any())
    <div x-data="{ show: false }" x-init="$nextTick(() => show = true)" x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-4 flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
        <x-icon name="x-circle" class="mt-0.5 size-5 shrink-0 text-rose-600" />
        <div class="flex-1">
            <p class="font-medium">Periksa kembali isian berikut:</p>
            <ul class="mt-1 list-inside list-disc space-y-0.5 leading-relaxed">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
